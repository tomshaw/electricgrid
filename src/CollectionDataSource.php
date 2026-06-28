<?php

declare(strict_types=1);

namespace TomShaw\ElectricGrid;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as DatabaseCollection;
use Illuminate\Pagination\{LengthAwarePaginator, Paginator};
use Illuminate\Support\Collection;
use TomShaw\ElectricGrid\Concerns\{ComputedColumns, FilterValues};

class CollectionDataSource
{
    use ComputedColumns;
    use FilterValues;

    /**
     * @param  Collection<int, mixed>  $collection
     */
    public function __construct(
        public Collection $collection,
    ) {}

    /**
     * @param  DatabaseCollection<int, covariant \Illuminate\Database\Eloquent\Model>|Collection<int, covariant mixed>|array<array-key, mixed>  $data
     */
    public static function make(DatabaseCollection|Collection|array $data): self
    {
        if (is_array($data)) {
            $data = collect($data)->map(fn ($item) => is_array($item) ? (object) $item : $item);
        }

        /** @var Collection<int, mixed> $collection */
        $collection = $data instanceof DatabaseCollection ? $data->toBase() : $data;

        return new self($collection);
    }

    public function count(): int
    {
        return $this->collection->count();
    }

    public function sum(string $field): float
    {
        $sum = $this->collection->sum($field);

        return is_numeric($sum) ? (float) $sum : 0.0;
    }

    public function avg(string $field): float
    {
        return (float) ($this->collection->avg($field) ?? 0);
    }

    public function orderBy(string $columnName, SortDirection|string $sortDirection): self
    {
        $direction = SortDirection::normalize($sortDirection);

        $this->collection = $this->collection->sortBy($columnName, SORT_REGULAR, $direction === SortDirection::Desc);

        return $this;
    }

    /**
     * @return LengthAwarePaginator<int, mixed>
     */
    public function paginate(int $perPage = 20): LengthAwarePaginator
    {
        $page = Paginator::resolveCurrentPage();
        $total = $this->collection->count();
        $items = ($perPage > 0)
            ? $this->collection->slice(($page - 1) * $perPage, $perPage)->values()
            : $this->collection;

        return new LengthAwarePaginator(
            $items,
            $total,
            $perPage > 0 ? $perPage : max($total, 1),
            $page,
            [
                'path' => request()->url(),
                'pageName' => 'page',
            ]
        );
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

        $this->collection = $this->collection->filter(function ($item) use ($term, $columns) {
            foreach ($columns as $column) {
                $value = $this->stringValue(data_get($item, $column));

                if ($value !== null && stripos($value, $term) !== false) {
                    return true;
                }
            }

            return false;
        });

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

        $this->collection = $this->collection->filter(function ($item) use ($letter, $columns) {
            foreach ($columns as $column) {
                $value = $this->stringValue(data_get($item, $column));

                if ($value !== null && stripos($value, $letter) === 0) {
                    return true;
                }
            }

            return false;
        });

        return $this;
    }

    /**
     * @param  array<array-key, mixed>  $filters
     */
    public function filter(array $filters): void
    {
        foreach ($filters as $type => $values) {
            if (! is_array($values)) {
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
                null => null,
            };
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

            $this->collection = $this->collection->filter(function ($item) use ($columnName, $value) {
                $itemValue = $this->stringValue(data_get($item, $columnName));

                return $itemValue !== null && stripos($itemValue, $value) !== false;
            });
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

            $this->collection = $this->collection->filter(function ($item) use ($columnName, $start, $end) {
                $itemValue = data_get($item, $columnName);

                return $itemValue !== null
                    && ($start === null || $itemValue >= $start)
                    && ($end === null || $itemValue <= $end);
            });
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

            $this->collection = $this->collection->filter(fn ($item) => data_get($item, $columnName) == $value);
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

            $this->collection = $this->collection->filter(fn ($item) => in_array(data_get($item, $columnName), $list));
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

            $expected = $value === 'true';

            $this->collection = $this->collection->filter(fn ($item) => (bool) data_get($item, $columnName) === $expected);
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

            $start = isset($range['start']) ? strtotime($range['start']) : null;
            $end = isset($range['end']) ? strtotime($range['end']) : null;

            if ($start === null && $end === null) {
                continue;
            }

            $this->collection = $this->collection->filter(function ($item) use ($columnName, $start, $end) {
                $itemValue = $this->stringValue(data_get($item, $columnName));
                $timestamp = $itemValue === null ? false : strtotime($itemValue);

                return $timestamp !== false
                    && ($start === null || $timestamp >= $start)
                    && ($end === null || $timestamp <= $end);
            });
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
