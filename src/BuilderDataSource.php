<?php

namespace TomShaw\ElectricGrid;

use Closure;
use Illuminate\Contracts\View\{Factory, View};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\{MorphTo, Relation};
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use TomShaw\ElectricGrid\Concerns\HandlesFilterValues;
use TomShaw\ElectricGrid\Exceptions\{InvalidFilterHandler, InvalidModelRelationsHandler};

class BuilderDataSource
{
    use HandlesFilterValues;

    private const LIKE_ESCAPE = '!';

    public function __construct(
        public Builder $query,
    ) {}

    public static function make(Builder $query): self
    {
        return new self($query);
    }

    public function __clone()
    {
        $this->query = clone $this->query;
    }

    public function count(): int
    {
        return $this->query->count();
    }

    public function sum(string $field): float
    {
        return (float) $this->query->sum($field);
    }

    public function avg(string $field): float
    {
        return (float) ($this->query->avg($field) ?? 0);
    }

    public function paginate(int $perPage = 20): LengthAwarePaginator
    {
        return $this->query->paginate($perPage > 0 ? $perPage : max($this->query->count(), 1));
    }

    public function orderBy(string $columnName, SortDirection|string $sortDirection): self
    {
        $direction = SortDirection::normalize($sortDirection);

        [$relation, $column] = $this->splitColumn($columnName);

        if ($relation === null) {
            $target = $this->isComputedColumn($column) ? $column : $this->query->qualifyColumn($column);
            $this->query->orderBy($target, $direction->value);

            return $this;
        }

        $this->query->orderBy($this->relationOrderSubquery($relation, $column, $direction), $direction->value);

        return $this;
    }

    /**
     * Build a correlated subquery selecting one related value, so relation
     * sorting never joins and therefore never duplicates parent rows.
     */
    private function relationOrderSubquery(string $relationName, string $column, SortDirection $direction): Builder
    {
        if (str_contains($relationName, '.')) {
            throw InvalidModelRelationsHandler::make("Sorting through nested relations is not supported: `{$relationName}.{$column}`.");
        }

        $model = $this->query->getModel();

        if (! method_exists($model, $relationName)) {
            throw InvalidModelRelationsHandler::make("Relation `{$relationName}` is not defined on `".$model::class.'`.');
        }

        $relation = $model->{$relationName}();

        if (! $relation instanceof Relation || $relation instanceof MorphTo) {
            throw InvalidModelRelationsHandler::make("Relation `{$relationName}` cannot be used for sorting.");
        }

        $related = $relation->getRelated();

        return $relation
            ->getRelationExistenceQuery($related->newQueryWithoutRelationships(), $this->query)
            ->select($related->qualifyColumn($column))
            ->orderBy($related->qualifyColumn($column), $direction->value)
            ->limit(1);
    }

    public function search(string $term, array $columns): self
    {
        $term = trim($term);

        if ($term === '' || $columns === []) {
            return $this;
        }

        $this->applyGroupedLike($columns, '%'.$this->escapeLike($term).'%');

        return $this;
    }

    public function searchLetter(string $letter, array $columns): self
    {
        $letter = trim($letter);

        if ($letter === '' || $columns === []) {
            return $this;
        }

        $this->applyGroupedLike($columns, $this->escapeLike($letter).'%');

        return $this;
    }

    private function applyGroupedLike(array $columns, string $pattern): void
    {
        $this->query->where(function (Builder $query) use ($columns, $pattern) {
            foreach ($columns as $columnName) {
                [$relation, $column] = $this->splitColumn($columnName);

                if ($relation === null) {
                    $this->whereLikePattern($query, $query->qualifyColumn($column), $pattern, or: true);
                } else {
                    $query->orWhereHas($relation, fn (Builder $related) => $this->whereLikePattern($related, $related->qualifyColumn($column), $pattern));
                }
            }
        });
    }

    private function whereLikePattern(Builder $query, string $column, string $pattern, bool $or = false): void
    {
        $grammar = $query->getQuery()->getGrammar();
        $sql = $grammar->wrap($column)." like ? escape '".self::LIKE_ESCAPE."'";

        $or ? $query->orWhereRaw($sql, [$pattern]) : $query->whereRaw($sql, [$pattern]);
    }

    private function escapeLike(string $value): string
    {
        return str_replace(
            [self::LIKE_ESCAPE, '%', '_'],
            [self::LIKE_ESCAPE.self::LIKE_ESCAPE, self::LIKE_ESCAPE.'%', self::LIKE_ESCAPE.'_'],
            $value
        );
    }

    public function filter(array $filters): void
    {
        foreach ($filters as $type => $values) {
            if (in_array($type, ['search_term', 'search_letter'], true) || ! is_array($values)) {
                continue;
            }

            match (FilterType::tryFrom($type)) {
                FilterType::Text => $this->applyTextFilters($values),
                FilterType::Number => $this->applyRangeFilters($values),
                FilterType::Select => $this->applySelectFilters($values),
                FilterType::MultiSelect => $this->applyMultiSelectFilters($values),
                FilterType::Boolean => $this->applyBooleanFilters($values),
                FilterType::TimePicker => $this->applyDateTimeFilters($values, 'time'),
                FilterType::DatePicker => $this->applyDateTimeFilters($values, 'date'),
                FilterType::DateTimePicker => $this->applyDateTimeFilters($values, 'datetime'),
                null => throw InvalidFilterHandler::make($type),
            };
        }
    }

    /**
     * Apply a constraint to a base, computed, or dotted relation column.
     * The dotted path is the source of truth: everything before the last
     * dot is treated as a (possibly nested) relation path.
     */
    private function applyToColumn(string $columnName, Closure $constraint): void
    {
        [$relation, $column] = $this->splitColumn($columnName);

        if ($relation === null) {
            $constraint($this->query, $this->isComputedColumn($column) ? $column : $this->query->qualifyColumn($column));
        } else {
            $this->query->whereHas($relation, fn (Builder $query) => $constraint($query, $query->qualifyColumn($column)));
        }
    }

    private function applyTextFilters(array $values): void
    {
        foreach ($this->flattenColumns($values, fn ($value) => ! is_array($value)) as $columnName => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $pattern = '%'.$this->escapeLike((string) $value).'%';

            $this->applyToColumn($columnName, fn (Builder $query, string $column) => $this->whereLikePattern($query, $column, $pattern));
        }
    }

    private function applyRangeFilters(array $values): void
    {
        foreach ($this->flattenColumns($values, $this->isRangeLeaf(...)) as $columnName => $range) {
            [$start, $end] = $this->rangeBounds($range);

            if ($start === null && $end === null) {
                continue;
            }

            if ($this->isComputedColumn($columnName)) {
                if (empty($this->query->getQuery()->groups)) {
                    $this->query->groupBy($this->query->qualifyColumn($this->query->getModel()->getKeyName()));
                }

                $this->applyRange($this->query, $columnName, $start, $end, having: true);

                continue;
            }

            $this->applyToColumn($columnName, fn (Builder $query, string $column) => $this->applyRange($query, $column, $start, $end));
        }
    }

    private function applyRange(Builder $query, string $column, mixed $start, mixed $end, bool $having = false): void
    {
        $method = $having ? 'having' : 'where';

        if ($start !== null) {
            $query->{$method}($column, '>=', $start);
        }

        if ($end !== null) {
            $query->{$method}($column, '<=', $end);
        }
    }

    private function applySelectFilters(array $values): void
    {
        foreach ($this->flattenColumns($values, fn ($value) => ! is_array($value)) as $columnName => $value) {
            if ($this->isIgnoredValue($value)) {
                continue;
            }

            $this->applyToColumn($columnName, fn (Builder $query, string $column) => $query->where($column, $value));
        }
    }

    private function applyMultiSelectFilters(array $values): void
    {
        foreach ($this->flattenColumns($values, fn ($value) => is_array($value) && array_is_list($value)) as $columnName => $list) {
            if ($list === [] || in_array('-1', $list) || in_array(-1, $list, true)) {
                continue;
            }

            $this->applyToColumn($columnName, fn (Builder $query, string $column) => $query->whereIn($column, $list));
        }
    }

    private function applyBooleanFilters(array $values): void
    {
        foreach ($this->flattenColumns($values, fn ($value) => ! is_array($value)) as $columnName => $value) {
            if ($this->isIgnoredValue($value)) {
                continue;
            }

            $this->applyToColumn($columnName, fn (Builder $query, string $column) => $query->where($column, $value === 'true' ? 1 : 0));
        }
    }

    private function applyDateTimeFilters(array $values, string $type): void
    {
        foreach ($this->flattenColumns($values, $this->isRangeLeaf(...)) as $columnName => $range) {
            $range = $this->normalizeDateTimeValues($range, $type);

            $start = $range['start'] ?? null;
            $end = $range['end'] ?? null;

            if ($start === null && $end === null) {
                continue;
            }

            $this->applyToColumn($columnName, fn (Builder $query, string $column) => $this->applyRange($query, $column, $start, $end));
        }
    }

    public function transform(LengthAwarePaginator $paginator, array $columns, ?Closure $rowClick = null): LengthAwarePaginator
    {
        $transformedColumns = $this->transformColumns($columns);

        $transformedCollection = $this->transformCollection($paginator->getCollection(), $transformedColumns, $rowClick);

        return $paginator->setCollection($transformedCollection);
    }

    public function transformColumns(array $columns): Collection
    {
        return collect($columns)->mapWithKeys(fn ($column) => [$column->field => $column->closure ?? $this->createDefaultClosure($column->field)]);
    }

    public function transformColumnsForExport(array $columns): Collection
    {
        return collect($columns)->mapWithKeys(fn ($column) => [$column->field => $column->exportClosure ?? $this->createDefaultClosure($column->field)]);
    }

    public function transformCollection(Collection $results, Collection $columns, ?Closure $rowClick = null): Collection
    {
        return $results->map(function ($row) use ($columns, $rowClick) {
            $transformed = (object) $columns->mapWithKeys(function ($column, $columnName) use ($row) {
                $value = $column($row);

                if ($value instanceof View || $value instanceof Factory) {
                    $value = $value->render();
                }

                return [$columnName => $value];
            })->toArray();

            if ($rowClick) {
                $transformed->__route = $rowClick($row);
            }

            return $transformed;
        });
    }

    private function createDefaultClosure(string $field): Closure
    {
        return fn ($model) => data_get($model, $field);
    }
}
