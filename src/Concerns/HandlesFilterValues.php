<?php

namespace TomShaw\ElectricGrid\Concerns;

use Closure;
use DateTime;
use TomShaw\ElectricGrid\Exceptions\{InvalidDateFormatHandler, InvalidDateTypeHandler};

trait HandlesFilterValues
{
    /** @var array<int, string> */
    public array $computedColumns = [];

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

    public function isComputedColumn(string $column): bool
    {
        return in_array($column, $this->computedColumns, true);
    }

    /**
     * Split a dotted column path into its relation path and column name.
     *
     * @return array{0: ?string, 1: string}
     */
    protected function splitColumn(string $columnName): array
    {
        $position = strrpos($columnName, '.');

        if ($position === false) {
            return [null, $columnName];
        }

        return [substr($columnName, 0, $position), substr($columnName, $position + 1)];
    }

    /**
     * Flatten nested wire payloads into dotted column paths, stopping at filter value leaves.
     *
     * @return array<string, mixed>
     */
    protected function flattenColumns(array $values, Closure $isLeaf, string $prefix = ''): array
    {
        $flattened = [];

        foreach ($values as $key => $value) {
            $path = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

            if ($isLeaf($value)) {
                $flattened[$path] = $value;
            } elseif (is_array($value)) {
                $flattened += $this->flattenColumns($value, $isLeaf, $path);
            }
        }

        return $flattened;
    }

    protected function isRangeLeaf(mixed $value): bool
    {
        return is_array($value) && (array_key_exists('start', $value) || array_key_exists('end', $value));
    }

    /**
     * @return array{0: mixed, 1: mixed}
     */
    protected function rangeBounds(array $range): array
    {
        $normalize = fn (mixed $value): mixed => ($value === '' || $value === null) ? null : $value;

        return [$normalize($range['start'] ?? null), $normalize($range['end'] ?? null)];
    }

    protected function isIgnoredValue(mixed $value): bool
    {
        return $value === null || $value === '' || $value === '-1' || $value === -1;
    }

    /**
     * @return array{start?: string, end?: string}
     */
    protected function normalizeDateTimeValues(array $values, string $type): array
    {
        [$inputFormat, $outputFormat] = match ($type) {
            'time' => ['H:i', 'H:i:s'],
            'date' => ['Y-m-d', 'Y-m-d'],
            'datetime' => ['Y-m-d\TH:i', 'Y-m-d H:i:s'],
            default => throw InvalidDateTypeHandler::make($type),
        };

        $normalized = [];

        foreach ($values as $key => $value) {
            if ($this->isIgnoredValue($value)) {
                continue;
            }

            $date = DateTime::createFromFormat($inputFormat, (string) $value);

            if ($date === false) {
                throw InvalidDateFormatHandler::make((string) $key, (string) $value);
            }

            $normalized[$key] = $date->format($outputFormat);
        }

        return $normalized;
    }
}
