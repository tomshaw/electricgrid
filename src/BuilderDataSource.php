<?php

declare(strict_types=1);

namespace TomShaw\ElectricGrid;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\{Builder, Model};
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasOne, HasOneThrough, MorphOne, MorphTo, Relation};
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use TomShaw\ElectricGrid\Concerns\HandlesFilterValues;
use TomShaw\ElectricGrid\Exceptions\{InvalidFilterHandler, InvalidModelRelationsHandler};

class BuilderDataSource
{
    use HandlesFilterValues;

    private const LIKE_ESCAPE = '!';

    /**
     * @param  Builder<covariant Model>  $query
     */
    public function __construct(
        public Builder $query,
    ) {}

    /**
     * @param  Builder<covariant Model>  $query
     */
    public static function make(Builder $query): self
    {
        return new self($query);
    }

    public function __clone(): void
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

    /**
     * @return LengthAwarePaginator<int, Model>
     */
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
     * sorting never joins and therefore never duplicates parent rows. The
     * dotted path is walked one relation at a time, nesting a scalar subquery
     * per hop so arbitrarily deep to-one paths can be sorted.
     *
     * @return Builder<covariant Model>
     */
    private function relationOrderSubquery(string $relationPath, string $column, SortDirection $direction): Builder
    {
        /** @var Builder<Model> $parentQuery */
        $parentQuery = $this->query;

        return $this->buildRelationOrderSubquery($this->query->getModel(), $parentQuery, explode('.', $relationPath), $column, $direction);
    }

    /**
     * @param  Builder<Model>  $parentQuery
     * @param  non-empty-array<int, string>  $segments
     * @return Builder<covariant Model>
     */
    private function buildRelationOrderSubquery(Model $parentModel, Builder $parentQuery, array $segments, string $column, SortDirection $direction): Builder
    {
        $relationName = array_shift($segments);

        if (! method_exists($parentModel, $relationName)) {
            throw InvalidModelRelationsHandler::make("Relation `{$relationName}` is not defined on `".$parentModel::class.'`.');
        }

        $relation = $parentModel->{$relationName}();

        $isLeaf = $segments === [];

        if ($isLeaf) {
            if (! $relation instanceof Relation || $relation instanceof MorphTo) {
                throw InvalidModelRelationsHandler::make("Relation `{$relationName}` cannot be used for sorting.");
            }
        } elseif (! $relation instanceof HasOne
            && ! $relation instanceof BelongsTo
            && ! $relation instanceof HasOneThrough
            && ! $relation instanceof MorphOne) {
            throw InvalidModelRelationsHandler::make("Relation `{$relationName}` cannot be used for sorting through a nested path.");
        }

        $related = $relation->getRelated();

        $query = $relation->getRelationExistenceQuery($related->newQueryWithoutRelationships(), $parentQuery);

        if ($isLeaf) {
            $target = $related->qualifyColumn($column);

            return $query->select($target)->orderBy($target, $direction->value)->limit(1);
        }

        $inner = $this->buildRelationOrderSubquery($related, $query, $segments, $column, $direction);

        return $query->select(['__eg_sort' => $inner])->limit(1);
    }

    /**
     * @param  array<int, string>  $columns
     */
    public function search(string $term, array $columns): self
    {
        $term = trim($term);

        if ($term === '' || $columns === []) {
            return $this;
        }

        $this->applyGroupedLike($columns, '%'.$this->escapeLike($term).'%');

        return $this;
    }

    /**
     * @param  array<int, string>  $columns
     */
    public function searchLetter(string $letter, array $columns): self
    {
        $letter = trim($letter);

        if ($letter === '' || $columns === []) {
            return $this;
        }

        $this->applyGroupedLike($columns, $this->escapeLike($letter).'%');

        return $this;
    }

    /**
     * @param  array<int, string>  $columns
     */
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

    /**
     * @param  Builder<covariant Model>  $query
     */
    private function whereLikePattern(Builder $query, string $column, string $pattern, bool $or = false): void
    {
        $grammar = $query->getQuery()->getGrammar();
        $sql = $grammar->wrap($column)." like ? escape '".self::LIKE_ESCAPE."'";

        // @phpstan-ignore argument.type (the column name is grammar-quoted and the pattern is a bound parameter)
        $query->whereRaw($sql, [$pattern], $or ? 'or' : 'and');
    }

    private function escapeLike(string $value): string
    {
        return str_replace(
            [self::LIKE_ESCAPE, '%', '_'],
            [self::LIKE_ESCAPE.self::LIKE_ESCAPE, self::LIKE_ESCAPE.'%', self::LIKE_ESCAPE.'_'],
            $value
        );
    }

    /**
     * @param  array<array-key, mixed>  $filters
     */
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

    /**
     * @param  array<array-key, mixed>  $values
     */
    private function applyTextFilters(array $values): void
    {
        foreach ($this->flattenColumns($values, fn ($value) => ! is_array($value)) as $columnName => $value) {
            $value = $this->stringValue($value);

            if ($value === null || $value === '') {
                continue;
            }

            $pattern = '%'.$this->escapeLike($value).'%';

            $this->applyToColumn($columnName, fn (Builder $query, string $column) => $this->whereLikePattern($query, $column, $pattern));
        }
    }

    /**
     * @param  array<array-key, mixed>  $values
     */
    private function applyRangeFilters(array $values): void
    {
        foreach ($this->flattenColumns($values, $this->isRangeLeaf(...)) as $columnName => $range) {
            if (! is_array($range)) {
                continue;
            }

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

    /**
     * @param  Builder<covariant Model>  $query
     */
    private function applyRange(Builder $query, string $column, int|float|string|null $start, int|float|string|null $end, bool $having = false): void
    {
        $method = $having ? 'having' : 'where';

        if ($start !== null) {
            $query->{$method}($column, '>=', $start);
        }

        if ($end !== null) {
            $query->{$method}($column, '<=', $end);
        }
    }

    /**
     * @param  array<array-key, mixed>  $values
     */
    private function applySelectFilters(array $values): void
    {
        foreach ($this->flattenColumns($values, fn ($value) => ! is_array($value)) as $columnName => $value) {
            if ($this->isIgnoredValue($value)) {
                continue;
            }

            $this->applyToColumn($columnName, fn (Builder $query, string $column) => $query->where($column, $value));
        }
    }

    /**
     * @param  array<array-key, mixed>  $values
     */
    private function applyMultiSelectFilters(array $values): void
    {
        foreach ($this->flattenColumns($values, fn ($value) => is_array($value) && array_is_list($value)) as $columnName => $list) {
            if (! is_array($list) || $list === [] || in_array('-1', $list) || in_array(-1, $list, true)) {
                continue;
            }

            $this->applyToColumn($columnName, fn (Builder $query, string $column) => $query->whereIn($column, $list));
        }
    }

    /**
     * @param  array<array-key, mixed>  $values
     */
    private function applyBooleanFilters(array $values): void
    {
        foreach ($this->flattenColumns($values, fn ($value) => ! is_array($value)) as $columnName => $value) {
            if ($this->isIgnoredValue($value)) {
                continue;
            }

            $this->applyToColumn($columnName, fn (Builder $query, string $column) => $query->where($column, $value === 'true' ? 1 : 0));
        }
    }

    /**
     * @param  array<array-key, mixed>  $values
     */
    private function applyDateTimeFilters(array $values, string $type): void
    {
        foreach ($this->flattenColumns($values, $this->isRangeLeaf(...)) as $columnName => $range) {
            if (! is_array($range)) {
                continue;
            }

            $range = $this->normalizeDateTimeValues($range, $type);

            $start = $range['start'] ?? null;
            $end = $range['end'] ?? null;

            if ($start === null && $end === null) {
                continue;
            }

            $this->applyToColumn($columnName, fn (Builder $query, string $column) => $this->applyRange($query, $column, $start, $end));
        }
    }

    /**
     * @param  LengthAwarePaginator<int, covariant mixed>  $paginator
     * @param  array<int, Column>  $columns
     * @return LengthAwarePaginator<int, covariant mixed>
     */
    public function transform(LengthAwarePaginator $paginator, array $columns, ?Closure $rowClick = null): LengthAwarePaginator
    {
        $transformedColumns = $this->transformColumns($columns);

        /** @var Collection<int, mixed> $transformedCollection */
        $transformedCollection = $this->transformCollection($paginator->getCollection(), $transformedColumns, $rowClick);

        return $paginator->setCollection($transformedCollection);
    }

    /**
     * @param  array<int, Column>  $columns
     * @return Collection<string, Closure>
     */
    public function transformColumns(array $columns): Collection
    {
        return collect($columns)->mapWithKeys(fn (Column $column) => [$column->field => $column->closure ?? $this->createDefaultClosure($column->field)]);
    }

    /**
     * @param  array<int, Column>  $columns
     * @return Collection<string, Closure>
     */
    public function transformColumnsForExport(array $columns): Collection
    {
        return collect($columns)->mapWithKeys(fn (Column $column) => [$column->field => $column->exportClosure ?? $this->createDefaultClosure($column->field)]);
    }

    /**
     * @param  Collection<int, covariant mixed>  $results
     * @param  Collection<string, Closure>  $columns
     * @return Collection<int, \stdClass>
     */
    public function transformCollection(Collection $results, Collection $columns, ?Closure $rowClick = null): Collection
    {
        return $results->map(function ($row) use ($columns, $rowClick) {
            $transformed = (object) $columns->mapWithKeys(function (Closure $column, string $columnName) use ($row) {
                $value = $column($row);

                if ($value instanceof View) {
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
