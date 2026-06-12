<?php

declare(strict_types=1);

namespace TomShaw\ElectricGrid\Concerns;

use Closure;
use DateTime;
use TomShaw\ElectricGrid\Exceptions\{InvalidDateFormatHandler, InvalidDateTypeHandler};

trait HandlesFilterValues
{
    /** @var array<int, string> */
    public array $computedColumns = [];

    /**
     * @param  array<int, string>  $columns
     */
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
     * @param  array<array-key, mixed>  $values
     * @param  Closure(mixed): bool  $isLeaf
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
     * @param  array<array-key, mixed>  $range
     * @return array{0: int|float|string|null, 1: int|float|string|null}
     */
    protected function rangeBounds(array $range): array
    {
        $normalize = function (mixed $value): int|float|string|null {
            if ($value === '' || ! (is_int($value) || is_float($value) || is_string($value))) {
                return null;
            }

            return $value;
        };

        return [$normalize($range['start'] ?? null), $normalize($range['end'] ?? null)];
    }

    protected function isIgnoredValue(mixed $value): bool
    {
        return $value === null || $value === '' || $value === '-1' || $value === -1;
    }

    /**
     * Coerce a filter or row value to a string for comparison, or null when it has no string form.
     */
    protected function stringValue(mixed $value): ?string
    {
        if (is_scalar($value) || $value instanceof \Stringable) {
            return (string) $value;
        }

        return null;
    }

    /**
     * @param  array<array-key, mixed>  $values
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

            $value = $this->stringValue($value);

            $date = $value === null ? false : DateTime::createFromFormat($inputFormat, $value);

            if ($date === false) {
                throw InvalidDateFormatHandler::make((string) $key, (string) $value);
            }

            $normalized[$key] = $date->format($outputFormat);
        }

        return $normalized;
    }
}
