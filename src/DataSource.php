<?php

namespace TomShaw\ElectricGrid;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use TomShaw\ElectricGrid\Exceptions\InvalidFilterHandler;

class DataSource
{
    public function __construct(
        public Builder $query,
    ) {
    }

    public static function make(Builder $query): self
    {
        return new self($query);
    }

    public function search(string $searchTerm, array $searchColumns): self
    {
        if (! empty($searchTerm)) {
            foreach ($searchColumns as $key => $column) {
                $this->query->where($column, 'like', '%'.$searchTerm.'%');
            }
        }

        return $this;
    }

    public function orderBy(string $columnName, string $sortDirection): self
    {
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
        return collect($columns)->mapWithKeys(fn ($column) => [$column->field => $column->closure ?? fn ($model) => $model->{$column->field}]);
    }

    public function transformCollection(Collection $results, Collection $columns): Collection
    {
        return $results->map(fn ($row) => (object) $columns->mapWithKeys(fn ($column, $columnName) => [$columnName => $column($row)])->toArray());
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
        foreach ($values as $key => $value) {
            $this->query->where($key, 'like', '%'.$value.'%');
        }
    }

    private function handleNumber(array $values): void
    {
        foreach ($values as $key => $value) {
            if (isset($value['start']) && ! isset($value['end'])) {
                $this->query->where($this->query->from.'.'.$key, '>', $value['start']);
            } elseif (! isset($value['start']) && isset($value['end'])) {
                $this->query->where($this->query->from.'.'.$key, '<', $value['end']);
            } elseif (isset($value['start']) && isset($value['end'])) {
                $this->query->where($this->query->from.'.'.$key, '>', $value['start'])->where($this->query->from.'.'.$key, '<=', $value['end']);
            }
        }
    }

    private function handleSelect(array $values): void
    {
        foreach ($values as $key => $value) {
            if ($value !== '-1') {
                $this->query->where($key, $value);
            }
        }
    }

    private function handleMultiSelect(array $values): void
    {
        foreach ($values as $key => $value) {
            if (! in_array('-1', $value)) {
                $this->query->whereIn($key, $value);
            }
        }
    }

    private function handleBoolean(array $values): void
    {
        foreach ($values as $key => $value) {
            if ($value === 'true' || $value === 'false') {
                $this->query->where($key, $value === 'true' ? 1 : 0);
            }
        }
    }

    private function handleTimePicker(array $values): void
    {
        foreach ($values as $key => $value) {
            if (isset($value['start']) && ! isset($value['end'])) {
                $this->query->whereTime($key, '>=', $value['start']);
            } elseif (! isset($value['start']) && isset($value['end'])) {
                $this->query->whereTime($key, '<=', $value['end']);
            } elseif (isset($value['start']) && isset($value['end'])) {
                $this->query->whereTime($key, '>=', $value['start'])->whereTime($key, '<=', $value['end']);
            }
        }
    }

    private function handleDatePicker(array $values): void
    {
        foreach ($values as $key => $value) {
            if (isset($value['start']) && ! isset($value['end'])) {
                $this->query->whereDate($key, '>=', $value['start']);
            } elseif (! isset($value['start']) && isset($value['end'])) {
                $this->query->whereDate($key, '<=', $value['end']);
            } elseif (isset($value['start']) && isset($value['end'])) {
                $this->query->whereDate($key, '>=', $value['start'])->whereDate($key, '<=', $value['end']);
            }
        }
    }

    private function handleDateTimePicker(array $values): void
    {
        foreach ($values as $key => $value) {
            if (isset($value['start']) && ! isset($value['end'])) {
                $this->query->where($key, '>=', $value['start']);
            } elseif (! isset($value['start']) && isset($value['end'])) {
                $this->query->where($key, '<=', $value['end']);
            } elseif (isset($value['start']) && isset($value['end'])) {
                $this->query->whereBetween($key, [$value['start'], $value['end']]);
            }
        }
    }

    private function handleSelectLetter($values): void
    {
        foreach ($values as $key => $value) {
            $this->query->where($key, 'like', $value.'%');
        }
    }
}
