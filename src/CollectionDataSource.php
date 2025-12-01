<?php

namespace TomShaw\ElectricGrid;

use Illuminate\Database\Eloquent\Collection as DatabaseCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CollectionDataSource
{
    public $computedColumns = [];

    public function __construct(
        public DatabaseCollection $collection,
    ) {}

    public static function make(DatabaseCollection $collection): self
    {
        return new self($collection);
    }

    public function addComputedColumns(array $columns): void
    {
        foreach ($columns as $column) {
            $this->addComputedColumn($column);
        }
    }

    public function addComputedColumn(string $columnName): void
    {
        $this->computedColumns[] = $columnName;
    }

    public function isComputedColumn($column): bool
    {
        return in_array($column, $this->computedColumns);
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
                'search_term' => $this->handleSearchTerm($values),
                'search_letter' => $this->handleSelectLetter($values),
                default => null, // Skip unsupported filters for collections
            };
        }
    }

    public function orderBy(string $columnName, string $sortDirection): self
    {
        $this->collection = $this->collection->sortBy($columnName, SORT_REGULAR, $sortDirection === 'DESC');

        return $this;
    }

    public function paginate(int $perPage = 20): LengthAwarePaginator
    {
        $page = request()->get('page', 1);
        $total = $this->collection->count();
        $items = ($perPage > 0)
            ? $this->collection->slice(($page - 1) * $perPage, $perPage)->values()
            : $this->collection;

        return new LengthAwarePaginator(
            $items,
            $total,
            $perPage > 0 ? $perPage : $total,
            $page,
            [
                'path' => request()->url(),
                'pageName' => 'page',
            ]
        );
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
        return $results->map(fn ($row) => (object) $columns->mapWithKeys(function ($column, $columnName) use ($row) {
            $value = $column($row);
            // Render View objects to strings
            if ($value instanceof \Illuminate\Contracts\View\View || $value instanceof \Illuminate\Contracts\View\Factory) {
                $value = $value->render();
            }

            return [$columnName => $value];
        })->toArray());
    }

    private function createDefaultClosure(string $field): \Closure
    {
        if (strpos($field, '.')) {
            [$relation, $field] = explode('.', $field, 2);

            return fn ($model) => $model->$relation ? $model->$relation->$field : $model->$field;
        }

        return fn ($model) => $model->$field;
    }

    private function handleSearchTerm(array $values): void
    {
        foreach ($values as $columnName => $searchTerm) {
            $this->collection = $this->collection->filter(function ($item) use ($columnName, $searchTerm) {
                $value = data_get($item, $columnName);

                return stripos($value, $searchTerm) !== false;
            });
        }
    }

    private function handleSelectLetter(array $values): void
    {
        foreach ($values as $columnName => $letter) {
            $this->collection = $this->collection->filter(function ($item) use ($columnName, $letter) {
                $value = data_get($item, $columnName);

                return stripos($value, $letter) === 0;
            });
        }
    }

    private function handleText(array $values): void
    {
        foreach ($values as $columnName => $value) {
            $this->collection = $this->collection->filter(function ($item) use ($columnName, $value) {
                $itemValue = data_get($item, $columnName);

                return stripos($itemValue, $value) !== false;
            });
        }
    }

    private function handleNumber(array $values): void
    {
        foreach ($values as $columnName => $value) {
            if (isset($value['start']) || isset($value['end'])) {
                $this->collection = $this->collection->filter(function ($item) use ($columnName, $value) {
                    $itemValue = data_get($item, $columnName);

                    if (isset($value['start']) && ! isset($value['end'])) {
                        return $itemValue >= $value['start'];
                    } elseif (! isset($value['start']) && isset($value['end'])) {
                        return $itemValue <= $value['end'];
                    } elseif (isset($value['start']) && isset($value['end'])) {
                        return $itemValue >= $value['start'] && $itemValue <= $value['end'];
                    }

                    return true;
                });
            }
        }
    }

    private function handleSelect(array $values): void
    {
        foreach ($values as $columnName => $value) {
            if ($value !== '-1') {
                $this->collection = $this->collection->filter(function ($item) use ($columnName, $value) {
                    return data_get($item, $columnName) == $value;
                });
            }
        }
    }

    private function handleMultiSelect(array $values): void
    {
        foreach ($values as $columnName => $valueArray) {
            if (! in_array('-1', $valueArray)) {
                $this->collection = $this->collection->filter(function ($item) use ($columnName, $valueArray) {
                    return in_array(data_get($item, $columnName), $valueArray);
                });
            }
        }
    }

    private function handleBoolean(array $values): void
    {
        foreach ($values as $columnName => $value) {
            if ($value !== '-1') {
                $this->collection = $this->collection->filter(function ($item) use ($columnName, $value) {
                    $itemValue = data_get($item, $columnName);

                    return ($value === 'true') ? (bool) $itemValue : ! (bool) $itemValue;
                });
            }
        }
    }

    public function sum(string $field): float
    {
        return $this->collection->sum($field);
    }

    public function avg(string $field): float
    {
        return $this->collection->avg($field) ?? 0;
    }
}
