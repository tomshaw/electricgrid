<?php

namespace TomShaw\ElectricGrid;

use DateTime;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\{Builder, Model};
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\{DB, Schema};
use InvalidArgumentException;
use TomShaw\ElectricGrid\Exceptions\InvalidFilterHandler;

class DataSource
{
    public $modelTables = [];

    public $modelFields = [];

    public $modelColumns = [];

    public array $relationTypes = [];

    public function __construct(
        public Builder $query,
    ) {
        $relationships = $this->getRelationships();
        $modelInstance = $this->getModelInstance();
        $modelTables = [];
        $modelFields = [];
        $modelColumns = [];
        foreach ($relationships as $relationship) {
            $relatedModel = $modelInstance->$relationship()->getRelated();
            $tableName = $relatedModel->getTable();
            $modelTables[$relationship] = $tableName;
            $modelFields[$relationship] = $relatedModel->getFillable();
            $modelColumns[$relationship] = DB::getSchemaBuilder()->getColumnListing($tableName);
        }

        $this->modelTables = $modelTables;
        $this->modelFields = $modelFields;
        $this->modelColumns = $modelColumns;

        $this->setRelationTypes();
    }

    public static function make(Builder $query): self
    {
        return new self($query);
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

    private function getRelationsForFields(string $column): ?string
    {
        foreach ($this->modelFields as $relation => $fields) {
            if (in_array($column, $fields)) {
                return $relation;
            }
        }

        return null;
    }

    private function getRelationsForColumns(string $column): ?string
    {
        foreach ($this->modelColumns as $relation => $fields) {
            if (in_array($column, $fields)) {
                return $relation;
            }
        }

        return null;
    }

    public function setRelationTypes(): void
    {
        $this->relationTypes = [];
        foreach ($this->modelFields as $relation => $fields) {
            $relationType = (new \ReflectionClass($this->query->getModel()->$relation()))->getShortName();
            if (in_array($relationType, ['HasOne', 'BelongsTo', 'HasMany', 'BelongsToMany', 'HasOneThrough', 'HasManyThrough', 'MorphOne', 'MorphMany', 'MorphTo', 'MorphToMany', 'MorphedByMany'])) {
                $this->relationTypes[$relation] = $relationType;
            }
        }
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

    public function search(string $searchTerm, array $searchColumns): self
    {
        if (empty($searchTerm)) {
            return $this;
        }

        foreach ($searchColumns as $columnName) {
            $relation = $this->getRelationsForFields($columnName);
            $qualifiedColumnName = $this->resolveTableNames($columnName);
            if ($relation !== null) {
                $this->query->whereHas($relation, function ($query) use ($columnName, $searchTerm) {
                    $query->where($columnName, 'like', '%'.$searchTerm.'%');
                });
            } else {
                $this->query->where($qualifiedColumnName, 'like', '%'.$searchTerm.'%');
            }
        }

        return $this;
    }

    public function orderBy(string $columnName, string $sortDirection): self
    {
        $isRelations = strpos($columnName, '.');

        if ($isRelations) {
            $parts = explode('.', $columnName);
            $column = $parts[1];
            foreach ($this->modelColumns as $relation => $fields) {
                if (in_array($column, $fields)) {
                    $pivotTable = $this->query->getModel()->$relation()->getTable();
                    $relatedTable = $this->modelTables[$relation];
                    $foreignKey = $this->query->getModel()->$relation()->getQualifiedForeignPivotKeyName();
                    $relatedKey = $this->query->getModel()->$relation()->getRelatedPivotKeyName();

                    $this->query->join($pivotTable, $this->query->getModel()->getTable().'.id', '=', $foreignKey)
                        ->join($relatedTable, "$pivotTable.$relatedKey", '=', "$relatedTable.id")
                        ->orderBy("$relatedTable.$column", $sortDirection);

                    return $this;
                }
            }
        }

        foreach ($this->modelFields as $relation => $fields) {
            if (in_array($columnName, $fields)) {
                $tableName = $this->modelTables[$relation];
                $model = $this->query->getModel();
                if (method_exists($model, $relation)) {
                    $foreignKey = $model->$relation()->getForeignKeyName();
                    $ownerKey = $model->$relation()->getOwnerKeyName();
                    $this->query->join($tableName, $foreignKey, '=', "$tableName.$ownerKey")
                        ->orderBy("$tableName.$columnName", $sortDirection);
                } else {
                    throw new Exception("The method '$relation' does not exist on the model.");
                }

                return $this;
            }
        }

        $this->query->orderBy($columnName, $sortDirection);

        return $this;
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
        foreach ($this->modelFields as $relation => $fields) {
            if (in_array($field, $fields)) {
                return fn ($model) => $model->$relation ? $model->$relation->$field : null;
            }
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
                    throw new InvalidArgumentException("Invalid type: $type");
            }
            if ($date !== false) {
                $normalizedValues[$key] = $date->format($format);
            } else {
                throw new InvalidArgumentException("Invalid date format for $key: $value");
            }
        }

        return $normalizedValues;
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
                'letter' => $this->handleSelectLetter($values),
                default => throw InvalidFilterHandler::make($type),
            };
        }
    }

    private function handleText(array $values): void
    {
        foreach ($values as $columnName => $value) {
            $relation = $this->getRelationsForFields($columnName);
            $qualifiedColumnName = $this->resolveTableNames($columnName);
            if ($relation !== null) {
                $this->query->whereHas($relation, function ($query) use ($columnName, $value) {
                    $query->where($columnName, 'like', '%'.$value.'%');
                });
            } else {
                $this->query->where($qualifiedColumnName, 'like', '%'.$value.'%');
            }
        }
    }

    private function handleNumber(array $values): void
    {
        foreach ($values as $columnName => $value) {
            $relation = $this->getRelationsForFields($columnName);
            $qualifiedColumnName = $this->resolveTableNames($columnName);
            if ($relation !== null) {
                $this->query->whereHas($relation, function ($query) use ($columnName, $value) {
                    if (isset($value['start']) && ! isset($value['end'])) {
                        $query->where($columnName, '>=', $value['start']);
                    } elseif (! isset($value['start']) && isset($value['end'])) {
                        $query->where($columnName, '=<', $value['end']);
                    } elseif (isset($value['start']) && isset($value['end'])) {
                        $query->whereBetween($columnName, [$value['start'], $value['end']]);
                    }
                });
            } else {
                if (isset($value['start']) && ! isset($value['end'])) {
                    $this->query->where($qualifiedColumnName, '>=', $value['start']);
                } elseif (! isset($value['start']) && isset($value['end'])) {
                    $this->query->where($qualifiedColumnName, '=<', $value['end']);
                } elseif (isset($value['start']) && isset($value['end'])) {
                    $this->query->whereBetween($qualifiedColumnName, [$value['start'], $value['end']]);
                }
            }
        }
    }

    private function handleSelect(array $values): void
    {
        foreach ($values as $columnName => $value) {
            if (is_array($value)) {
                foreach ($value as $subColumnName => $subValue) {
                    $relation = $this->getRelationsForColumns($subColumnName);
                    if ($subValue !== '-1' && $relation !== null) {
                        $this->query->whereHas($relation, function ($query) use ($subColumnName, $subValue) {
                            $query->where($subColumnName, $subValue);
                        });
                    }
                }
            } else {
                $relation = $this->getRelationsForFields($columnName);
                $qualifiedColumnName = $this->resolveTableNames($columnName);
                if ($value !== '-1') {
                    if ($relation !== null) {
                        $this->query->whereHas($relation, function ($query) use ($columnName, $value) {
                            $query->where($columnName, $value);
                        });
                    } else {
                        $this->query->where($qualifiedColumnName, $value);
                    }
                }
            }
        }
    }

    private function handleMultiSelect(array $values): void
    {
        foreach ($values as $columnName => $value) {
            if (is_array($value)) {
                foreach ($value as $subColumnName => $subValue) {
                    $relation = $this->getRelationsForColumns($subColumnName);
                    if (! in_array('-1', $subValue) && $relation !== null) {
                        $this->query->whereHas($relation, function ($query) use ($subColumnName, $subValue) {
                            $query->whereIn($subColumnName, $subValue);
                        });
                    }
                }
            } else {
                $relation = $this->getRelationsForFields($columnName);
                $qualifiedColumnName = $this->resolveTableNames($columnName);
                if (! in_array('-1', $value)) {
                    if ($relation !== null) {
                        $this->query->whereHas($relation, function ($query) use ($columnName, $value) {
                            $query->whereIn($columnName, $value);
                        });
                    } else {
                        $this->query->whereIn($qualifiedColumnName, $value);
                    }
                }
            }
        }
    }

    private function handleBoolean(array $values): void
    {
        foreach ($values as $columnName => $value) {
            $relation = $this->getRelationsForFields($columnName);
            $qualifiedColumnName = $this->resolveTableNames($columnName);
            if ($value === 'true' || $value === 'false') {
                if ($relation !== null) {
                    $this->query->whereHas($relation, function ($query) use ($columnName, $value) {
                        $query->where($columnName, $value === 'true' ? 1 : 0);
                    });
                } else {
                    $this->query->where($qualifiedColumnName, $value === 'true' ? 1 : 0);
                }
            }
        }
    }

    private function handleTimePicker(array $values): void
    {
        foreach ($values as $columnName => $filter) {
            $values = $this->normalizeDateTimeValues($filter, 'time');
            $relation = $this->getRelationsForFields($columnName);
            $qualifiedColumnName = $this->resolveTableNames($columnName);
            if ($relation !== null) {
                $this->query->whereHas($relation, function ($query) use ($columnName, $values) {
                    if (isset($values['start']) && ! isset($values['end'])) {
                        $query->whereTime($columnName, '>=', $values['start']);
                    } elseif (! isset($values['start']) && isset($values['end'])) {
                        $query->whereTime($columnName, '<=', $values['end']);
                    } elseif (isset($values['start']) && isset($values['end'])) {
                        $query->whereTime($columnName, '>=', $values['start'])->whereTime($columnName, '<=', $values['end']);
                    }
                });
            } else {
                if (isset($values['start']) && ! isset($values['end'])) {
                    $this->query->whereTime($qualifiedColumnName, '>=', $values['start']);
                } elseif (! isset($values['start']) && isset($values['end'])) {
                    $this->query->whereTime($qualifiedColumnName, '<=', $values['end']);
                } elseif (isset($values['start']) && isset($values['end'])) {
                    $this->query->whereTime($qualifiedColumnName, '>=', $values['start'])->whereTime($qualifiedColumnName, '<=', $values['end']);
                }
            }
        }
    }

    private function handleDatePicker(array $values): void
    {
        foreach ($values as $columnName => $filter) {
            $values = $this->normalizeDateTimeValues($filter, 'date');
            $relation = $this->getRelationsForFields($columnName);
            $qualifiedColumnName = $this->resolveTableNames($columnName);
            if ($relation !== null) {
                $this->query->whereHas($relation, function ($query) use ($columnName, $values) {
                    if (isset($values['start']) && ! isset($values['end'])) {
                        $query->whereDate($columnName, '>=', $values['start']);
                    } elseif (! isset($values['start']) && isset($values['end'])) {
                        $query->whereDate($columnName, '<=', $values['end']);
                    } elseif (isset($values['start']) && isset($values['end'])) {
                        $query->whereBetween($columnName, [$values['start'], $values['end']]);
                    }
                });
            } else {
                if (isset($values['start']) && ! isset($values['end'])) {
                    $this->query->whereDate($qualifiedColumnName, '>=', $values['start']);
                } elseif (! isset($values['start']) && isset($values['end'])) {
                    $this->query->whereDate($qualifiedColumnName, '<=', $values['end']);
                } elseif (isset($values['start']) && isset($values['end'])) {
                    $this->query->whereBetween($qualifiedColumnName, [$values['start'], $values['end']]);
                }
            }
        }
    }

    private function handleDateTimePicker(array $values): void
    {
        foreach ($values as $columnName => $filter) {
            $values = $this->normalizeDateTimeValues($filter, 'datetime');
            $relation = $this->getRelationsForFields($columnName);
            $qualifiedColumnName = $this->resolveTableNames($columnName);
            if ($relation !== null) {
                $this->query->whereHas($relation, function ($query) use ($columnName, $values) {
                    if (isset($values['start']) && ! isset($values['end'])) {
                        $query->where($columnName, '>=', $values['start']);
                    } elseif (! isset($values['start']) && isset($values['end'])) {
                        $query->where($columnName, '<=', $values['end']);
                    } elseif (isset($values['start']) && isset($values['end'])) {
                        $query->whereBetween($columnName, [$values['start'], $values['end']]);
                    }
                });
            } else {
                if (isset($values['start']) && ! isset($values['end'])) {
                    $this->query->where($qualifiedColumnName, '>=', $values['start']);
                } elseif (! isset($values['start']) && isset($value['end'])) {
                    $this->query->where($qualifiedColumnName, '<=', $values['end']);
                } elseif (isset($values['start']) && isset($values['end'])) {
                    $this->query->whereBetween($qualifiedColumnName, [$values['start'], $values['end']]);
                }
            }
        }
    }

    private function handleSelectLetter($values): void
    {
        foreach ($values as $columnName => $value) {
            $relation = $this->getRelationsForFields($columnName);
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
