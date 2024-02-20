<?php

namespace TomShaw\ElectricGrid;

use DateTime;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany, HasMany, HasOne, MorphMany, MorphOne, MorphTo, MorphToMany, Relation};
use Illuminate\Database\Eloquent\{Builder, Model};
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\{DB, Schema};
use TomShaw\ElectricGrid\Exceptions\{InvalidDateFormatHandler, InvalidDateTypeHandler, InvalidFilterHandler, InvalidModelRelationsHandler};

class DataSource
{
    public $modelRelationTables = [];

    public $modelRelationFillables = [];

    public $modelRelationColumnListing = [];

    private const IGNORE_VALUE = -1;

    public function __construct(
        public Builder $query,
    ) {
        $relationships = $this->getRelationships();
        $modelInstance = $this->getModelInstance();

        $this->boot($relationships, $modelInstance);
    }

    public static function make(Builder $query): self
    {
        return new self($query);
    }

    public function boot(array $relationships, $modelInstance): void
    {
        try {
            $this->modelRelationTables = $this->getModelRelationTables($relationships, $modelInstance);
            $this->modelRelationFillables = $this->getModelRelationFillables($relationships, $modelInstance);
            $this->modelRelationColumnListing = $this->getModelRelationColumnListing($relationships, $modelInstance);
        } catch (Exception $e) {
            throw InvalidModelRelationsHandler::make($e->getMessage());
        }
    }

    private function getModelRelationTables(array $relationships, $modelInstance): array
    {
        $modelRelationTables = [];
        foreach ($relationships as $relationship) {
            $relatedModel = $modelInstance->$relationship()->getRelated();
            $tableName = $relatedModel->getTable();
            $modelRelationTables[$relationship] = $tableName;
        }

        return $modelRelationTables;
    }

    private function getModelRelationFillables(array $relationships, $modelInstance): array
    {
        $modelRelationFillables = [];
        foreach ($relationships as $relationship) {
            $relatedModel = $modelInstance->$relationship()->getRelated();
            $modelRelationFillables[$relationship] = $relatedModel->getFillable();
        }

        return $modelRelationFillables;
    }

    private function getModelRelationColumnListing(array $relationships, $modelInstance): array
    {
        $modelRelationColumnListing = [];
        foreach ($relationships as $relationship) {
            $relatedModel = $modelInstance->$relationship()->getRelated();
            $tableName = $relatedModel->getTable();
            $modelRelationColumnListing[$relationship] = DB::getSchemaBuilder()->getColumnListing($tableName);
        }

        return $modelRelationColumnListing;
    }

    private function getModelInstance(): Model
    {
        return $this->query->getModel();
    }

    private function getRelationships(): array
    {
        $eagerLoad = $this->query->getEagerLoads();

        return array_keys($eagerLoad);
    }

    private function getRelationFillables(string $column): ?string
    {
        foreach ($this->modelRelationFillables as $relation => $fields) {
            if (in_array($column, $fields)) {
                return $relation;
            }
        }

        return null;
    }

    private function getRelationColumnListing(string $column): ?string
    {
        foreach ($this->modelRelationColumnListing as $relation => $fields) {
            if (in_array($column, $fields)) {
                return $relation;
            }
        }

        return null;
    }

    public function isDirectRelation(Relation $relation): bool
    {
        return $relation instanceof BelongsTo || $relation instanceof HasOne || $relation instanceof HasMany || $relation instanceof MorphOne || $relation instanceof MorphMany || $relation instanceof MorphTo;
    }

    public function isManyToManyRelation(Relation $relation): bool
    {
        return $relation instanceof BelongsToMany || $relation instanceof MorphToMany;
    }

    private function resolveTableNames($columnName): ?string
    {
        $baseTable = $this->query->getModel()->getTable();

        if (Schema::hasColumn($baseTable, $columnName)) {
            return $baseTable.'.'.$columnName;
        }

        $joins = $this->query->getQuery()->joins;

        if ($joins) {
            foreach ($joins as $join) {
                if (Schema::hasColumn($join->table, $columnName)) {
                    return $join->table.'.'.$columnName;
                }
            }
        }

        return null;
    }

    private function parseColumnString(string $columnString): array
    {
        $parts = explode('.', $columnString);

        $relation = $parts[0] ?? null;
        $column = $parts[1] ?? null;

        return [$relation, $column];
    }

    public function paginate(int $perPage = 20): LengthAwarePaginator
    {
        return $this->query->paginate(($perPage > 0) ? $perPage : $this->query->count());
    }

    public function transform(LengthAwarePaginator $paginator, array $columns): LengthAwarePaginator
    {
        $transformedColumns = $this->transformColumns($columns);

        $transformedCollection = $this->transformCollection($paginator->getCollection(), $transformedColumns);

        return $paginator->setCollection($transformedCollection);
    }

    public function transformColumns(array $columns): Collection
    {
        return collect($columns)->mapWithKeys(fn ($column) => [$column->field => $column->closure ?? $this->createDefaultClosure($column->field)]);
    }

    public function transformCollection(Collection $results, Collection $columns): Collection
    {
        return $results->map(fn ($row) => (object) $columns->mapWithKeys(fn ($column, $columnName) => [$columnName => $column($row)])->toArray());
    }

    private function createDefaultClosure(string $field): \Closure
    {
        if (strpos($field, '.')) {
            [$relation, $field] = $this->parseColumnString($field);

            return fn ($model) => $model->$relation ? $model->$relation->$field : $model->$field;
        }

        return fn ($model) => $model->$field;
    }

    private function normalizeDateTimeValues(array $values, string $type): array
    {
        $normalizedValues = [];
        foreach ($values as $key => $value) {
            switch ($type) {
                case 'time':
                    $date = DateTime::createFromFormat('H:i', $value);
                    $format = 'H:i:s';
                    break;
                case 'date':
                    $date = DateTime::createFromFormat('Y-m-d', $value);
                    $format = 'Y-m-d';
                    break;
                case 'datetime':
                    $date = DateTime::createFromFormat('Y-m-d\TH:i', $value);
                    $format = 'Y-m-d H:i:s';
                    break;
                default:
                    throw InvalidDateTypeHandler::make($type);
            }
            if ($date !== false) {
                $normalizedValues[$key] = $date->format($format);
            } else {
                throw InvalidDateFormatHandler::make($key, $value);
            }
        }

        return $normalizedValues;
    }

    private function hasStartOrEndKey(array $value): bool
    {
        return isset($value['start']) || isset($value['end']);
    }

    public function filter(array $filters): void
    {
        foreach ($filters as $type => $values) {
            match ($type) {
                'text' => $this->handleText($values),
                'number' => $this->handleNumber($values),
                'select' => $this->handleSelect($values),
                'multiselect' => $this->handleMultiSelect($values),
                'boolean' => $this->handleBoolean($values),
                'timepicker' => $this->handleTimePicker($values),
                'datepicker' => $this->handleDatePicker($values),
                'datetimepicker' => $this->handleDateTimePicker($values),
                'search_term' => $this->handleSearchTerm($values),
                'search_letter' => $this->handleSelectLetter($values),
                default => throw InvalidFilterHandler::make($type),
            };
        }
    }

    private function handleSearchTerm(array $values): self
    {
        foreach ($values as $columnName => $searchTerm) {
            if (strpos($columnName, '.')) {
                [$relation, $subColumnName] = $this->parseColumnString($columnName);
                if ($relation !== null) {
                    $this->query->whereHas($relation, function ($query) use ($subColumnName, $searchTerm) {
                        $query->where($subColumnName, 'like', '%'.$searchTerm.'%');
                    });
                }
            } else {
                $relation = $this->getRelationFillables($columnName);
                $qualifiedColumnName = $this->resolveTableNames($columnName);
                if ($relation !== null) {
                    $this->query->whereHas($relation, function ($query) use ($columnName, $searchTerm) {
                        $query->where($columnName, 'like', '%'.$searchTerm.'%');
                    });
                } else {
                    $this->query->where($qualifiedColumnName, 'like', '%'.$searchTerm.'%');
                }
            }
        }

        return $this;
    }

    private function handleSelectLetter(array $values): void
    {
        foreach ($values as $columnName => $value) {
            if (strpos($columnName, '.')) {
                [$relation, $subColumnName] = $this->parseColumnString($columnName);
                if ($relation !== null) {
                    $this->query->whereHas($relation, function ($query) use ($subColumnName, $value) {
                        $query->where($subColumnName, 'like', $value.'%');
                    });
                }
            } else {
                $relation = $this->getRelationFillables($columnName);
                $qualifiedColumnName = $this->resolveTableNames($columnName);
                if ($relation !== null) {
                    $this->query->whereHas($relation, function ($query) use ($columnName, $value) {
                        $query->where($columnName, 'like', $value.'%');
                    });
                } else {
                    $this->query->where($qualifiedColumnName, 'like', $value.'%');
                }
            }
        }
    }

    public function orderBy(string $columnName, string $sortDirection): self
    {
        if (strpos($columnName, '.')) {
            [, $columnName] = $this->parseColumnString($columnName);
            $this->orderByWithRelation($columnName, $sortDirection);
        } else {
            $this->orderByWithoutRelation($columnName, $sortDirection);
        }

        return $this;
    }

    private function orderByWithRelation(string $columnName, string $sortDirection): void
    {
        foreach ($this->modelRelationColumnListing as $relation => $fields) {
            if (in_array($columnName, $fields)) {
                $tableName = $this->query->getModel()->getTable();
                $relationQuery = $this->query->getModel()->$relation();

                // Added to not modify the result set
                $this->query->select("$tableName.*");

                if ($this->isDirectRelation($relationQuery)) {
                    $this->orderByDirectRelation($relationQuery, $tableName, $columnName, $sortDirection);
                } elseif ($this->isManyToManyRelation($relationQuery)) {
                    $this->orderByManyToManyRelation($relationQuery, $tableName, $relation, $columnName, $sortDirection);
                }

                return;
            }
        }
    }

    private function orderByWithoutRelation(string $columnName, string $sortDirection): void
    {
        foreach ($this->modelRelationFillables as $relation => $fields) {
            if (in_array($columnName, $fields)) {
                $this->orderByFillableRelation($relation, $columnName, $sortDirection);

                return;
            }
        }

        $this->query->orderBy($columnName, $sortDirection);
    }

    private function orderByDirectRelation($relationQuery, string $tableName, string $columnName, string $sortDirection): void
    {
        $relatedTable = $relationQuery->getRelated()->getTable();
        $foreignKey = $relationQuery->getForeignKeyName();

        if ($relationQuery instanceof BelongsTo) {
            $ownerKey = $relationQuery->getOwnerKeyName();
            $this->query->join($relatedTable, "$tableName.$foreignKey", '=', "$relatedTable.$ownerKey");
        } else { // HasOne or HasMany
            $ownerKey = $relationQuery->getQualifiedParentKeyName();
            $this->query->join($relatedTable, $ownerKey, '=', "$relatedTable.$foreignKey");
        }

        $this->query->orderBy("$relatedTable.$columnName", $sortDirection);
    }

    private function orderByManyToManyRelation($relationQuery, string $tableName, string $relation, string $columnName, string $sortDirection): void
    {
        $pivotTable = $relationQuery->getTable();
        $relatedTable = $this->modelRelationTables[$relation];
        $foreignKey = $relationQuery->getQualifiedForeignPivotKeyName();
        $relatedKey = $relationQuery->getRelatedPivotKeyName();

        $this->query->join($pivotTable, "$tableName.id", '=', $foreignKey)
            ->join($relatedTable, "$pivotTable.$relatedKey", '=', "$relatedTable.id")
            ->orderBy("$relatedTable.$columnName", $sortDirection);
    }

    private function orderByFillableRelation(string $relation, string $columnName, string $sortDirection): void
    {
        $tableName = $this->modelRelationTables[$relation];
        $model = $this->query->getModel();
        if (method_exists($model, $relation)) {
            $foreignKey = $model->$relation()->getForeignKeyName();
            $ownerKey = $model->$relation()->getOwnerKeyName();
            $this->query->join($tableName, $foreignKey, '=', "$tableName.$ownerKey")
                ->orderBy("$tableName.$columnName", $sortDirection);
        } else {
            throw InvalidModelRelationsHandler::make("The method '$relation' does not exist on the model.");
        }
    }

    private function handleText(array $values): void
    {
        foreach ($values as $columnName => $value) {
            if (is_array($value)) {
                $this->handleTextArray($value);
            } else {
                $this->handleTextDefault($columnName, $value);
            }
        }
    }

    private function handleTextArray(array $values): void
    {
        foreach ($values as $subColumnName => $subValue) {
            $relation = $this->getRelationColumnListing($subColumnName);
            if ($relation !== null) {
                $this->query->whereHas($relation, function ($query) use ($subColumnName, $subValue) {
                    $query->where($subColumnName, 'like', '%'.$subValue.'%');
                });
            }
        }
    }

    private function handleTextDefault(string $columnName, $value): void
    {
        $relation = $this->getRelationFillables($columnName);
        $qualifiedColumnName = $this->resolveTableNames($columnName);
        if ($relation !== null) {
            $this->query->whereHas($relation, function ($query) use ($columnName, $value) {
                $query->where($columnName, 'like', '%'.$value.'%');
            });
        } else {
            $this->query->where($qualifiedColumnName, 'like', '%'.$value.'%');
        }
    }

    private function handleNumber(array $values): void
    {
        foreach ($values as $columnName => $value) {
            if (! $this->hasStartOrEndKey($value)) {
                $this->handleNumberArray($value);
            } else {
                $this->handleNumberDefault($columnName, $value);
            }
        }
    }

    private function handleNumberArray(array $values): void
    {
        foreach ($values as $subColumnName => $subValue) {
            $relation = $this->getRelationColumnListing($subColumnName);
            if ($relation !== null) {
                $this->query->whereHas($relation, function ($query) use ($subColumnName, $subValue) {
                    $this->applyWhereConditions($query, $subColumnName, $subValue);
                });
            }
        }
    }

    private function handleNumberDefault(string $columnName, $value): void
    {
        $relation = $this->getRelationFillables($columnName);
        $qualifiedColumnName = $this->resolveTableNames($columnName);
        if ($relation !== null) {
            $this->query->whereHas($relation, function ($query) use ($columnName, $value) {
                $this->applyWhereConditions($query, $columnName, $value);
            });
        } else {
            $this->applyWhereConditions($this->query, $qualifiedColumnName, $value);
        }
    }

    private function handleSelect(array $values): void
    {
        foreach ($values as $columnName => $value) {
            if (is_array($value)) {
                $this->handleSelectArray($value);
            } else {
                $this->handleSelectDefault($columnName, $value);
            }
        }
    }

    private function handleSelectArray(array $values): void
    {
        foreach ($values as $subColumnName => $subValue) {
            $relation = $this->getRelationColumnListing($subColumnName);
            if ($relation !== null && $subValue !== strval(self::IGNORE_VALUE)) {
                $this->query->whereHas($relation, function ($query) use ($subColumnName, $subValue) {
                    $query->where($subColumnName, '=', $subValue);
                });
            }
        }
    }

    private function handleSelectDefault(string $columnName, $value): void
    {
        $relation = $this->getRelationFillables($columnName);
        $qualifiedColumnName = $this->resolveTableNames($columnName);
        if ($value !== strval(self::IGNORE_VALUE)) {
            if ($relation !== null) {
                $this->query->whereHas($relation, function ($query) use ($columnName, $value) {
                    $query->where($columnName, '=', $value);
                });
            } else {
                $this->query->where($qualifiedColumnName, $value);
            }
        }
    }

    private function handleMultiSelect(array $values): void
    {
        foreach ($values as $columnName => $value) {
            if (is_array($value)) {
                $this->handleMultiSelectArray($value);
            } else {
                $this->handleMultiSelectDefault($columnName, $value);
            }
        }
    }

    private function handleMultiSelectArray(array $values): void
    {
        foreach ($values as $subColumnName => $subValue) {
            if ($subValue[0] !== strval(self::IGNORE_VALUE)) {
                $relation = $this->getRelationColumnListing($subColumnName);
                if ($relation !== null) {
                    $this->query->whereHas($relation, function ($query) use ($subColumnName, $subValue) {
                        $query->whereIn($subColumnName, $subValue);
                    });
                }
            }
        }
    }

    private function handleMultiSelectDefault(string $columnName, $value): void
    {
        $relation = $this->getRelationFillables($columnName);
        $qualifiedColumnName = $this->resolveTableNames($columnName);

        if ($value !== strval(self::IGNORE_VALUE)) {
            if ($relation !== null) {
                $this->query->whereHas($relation, function ($query) use ($columnName, $value) {
                    $query->whereIn($columnName, $value);
                });
            } else {
                $this->query->whereIn($qualifiedColumnName, $value);
            }
        }
    }

    private function handleBoolean(array $values): void
    {
        foreach ($values as $columnName => $value) {
            if (is_array($value)) {
                $this->handleBooleanArray($value);
            } else {
                $this->handleBooleanDefault($columnName, $value);
            }
        }
    }

    private function handleBooleanArray(array $values): void
    {
        foreach ($values as $subColumnName => $subValue) {
            $relation = $this->getRelationColumnListing($subColumnName);
            if ($relation !== null && $subValue !== strval(self::IGNORE_VALUE)) {
                $this->query->whereHas($relation, function ($query) use ($subColumnName, $subValue) {
                    $query->where($subColumnName, $subValue === 'true' ? 1 : 0);
                });
            }
        }
    }

    private function handleBooleanDefault(string $columnName, $value): void
    {
        $relation = $this->getRelationFillables($columnName);
        $qualifiedColumnName = $this->resolveTableNames($columnName);

        if ($value !== strval(self::IGNORE_VALUE)) {
            if ($relation !== null) {
                $this->query->whereHas($relation, function ($query) use ($columnName, $value) {
                    $query->where($columnName, $value === 'true' ? 1 : 0);
                });
            } else {
                $this->query->where($qualifiedColumnName, $value === 'true' ? 1 : 0);
            }
        }
    }

    private function handleTimePicker(array $values): void
    {
        foreach ($values as $key => $value) {
            if ($this->hasStartOrEndKey($value)) {
                $this->applyDateTimeFilter($key, $value, 'time');
            } else {
                foreach ($value as $columnName => $columnValue) {
                    $this->applyDateTimeFilter("{$key}.{$columnName}", $columnValue, 'time');
                }
            }
        }
    }

    private function handleDatePicker(array $values): void
    {
        foreach ($values as $key => $value) {
            if ($this->hasStartOrEndKey($value)) {
                $this->applyDateTimeFilter($key, $value, 'date');
            } else {
                foreach ($value as $columnName => $columnValue) {
                    $this->applyDateTimeFilter("{$key}.{$columnName}", $columnValue, 'date');
                }
            }
        }
    }

    private function handleDateTimePicker(array $values): void
    {
        foreach ($values as $key => $value) {
            if ($this->hasStartOrEndKey($value)) {
                $this->applyDateTimeFilter($key, $value, 'datetime');
            } else {
                foreach ($value as $columnName => $columnValue) {
                    $this->applyDateTimeFilter("{$key}.{$columnName}", $columnValue, 'datetime');
                }
            }
        }
    }

    private function applyDateTimeFilter(string $key, array $values, string $filterType): void
    {
        $values = $this->normalizeDateTimeValues($values, $filterType);

        if (strpos($key, '.') !== false) {
            [$relation, $columnName] = explode('.', $key);
            $this->query->whereHas($relation, function ($query) use ($columnName, $values) {
                $this->applyWhereConditions($query, $columnName, $values);
            });
        } else {
            $this->applyWhereConditions($this->query, $key, $values);
        }
    }

    private function applyWhereConditions($query, string $columnName, array $values): void
    {
        if (isset($values['start']) && ! isset($values['end'])) {
            $query->where($columnName, '>=', $values['start']);
        } elseif (! isset($values['start']) && isset($values['end'])) {
            $query->where($columnName, '<=', $values['end']);
        } elseif (isset($values['start']) && isset($values['end'])) {
            $query->whereBetween($columnName, [$values['start'], $values['end']]);
        }
    }
}
