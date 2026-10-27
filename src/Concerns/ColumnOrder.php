<?php

declare(strict_types=1);

namespace TomShaw\ElectricGrid\Concerns;

use TomShaw\ElectricGrid\Column;

/**
 * Lets users drag columns into their own order. The order is stored as a list
 * of fields; an empty list means the default order from columns(). Fields that
 * are no longer columns are skipped, and new columns slot in after the column
 * that precedes them by default.
 *
 * Relies on members declared by the host component:
 *
 * @property array<int, string> $hiddenColumns
 *
 * @method array<int, Column> columns()
 * @method array<int, Column> getColumnsProperty()
 * @method void saveSessionState()
 */
trait ColumnOrder
{
    /** @var array<int, string> */
    public array $columnOrder = [];

    /**
     * When true, exports follow the user's column order instead of the default order.
     */
    public bool $exportColumnOrder = false;

    public function handleColumnSort(string $field, int $position): void
    {
        $fields = $this->columnFields($this->getColumnsProperty());
        $from = array_search($field, $fields, true);

        if ($from === false) {
            return;
        }

        array_splice($fields, $from, 1);
        array_splice($fields, max(0, min(count($fields), $position)), 0, [$field]);

        $this->columnOrder = $fields === $this->columnFields($this->columns()) ? [] : $fields;

        $this->saveSessionState();
    }

    public function resetColumns(): void
    {
        $this->hiddenColumns = [];
        $this->columnOrder = [];

        $this->saveSessionState();
    }

    /**
     * @param  array<int, Column>  $columns
     * @return array<int, Column>
     */
    protected function orderColumns(array $columns): array
    {
        if ($this->columnOrder === []) {
            return $columns;
        }

        $byField = [];

        foreach ($columns as $column) {
            $byField[$column->field] = $column;
        }

        $defaults = array_keys($byField);
        $ordered = array_values(array_filter($this->columnOrder, fn (string $field): bool => isset($byField[$field])));

        foreach ($defaults as $index => $field) {
            if (in_array($field, $ordered, true)) {
                continue;
            }

            $previous = $index > 0 ? array_search($defaults[$index - 1], $ordered, true) : false;
            array_splice($ordered, $previous === false ? 0 : $previous + 1, 0, [$field]);
        }

        return array_map(fn (string $field): Column => $byField[$field], $ordered);
    }

    /**
     * @param  array<int, Column>  $columns
     * @return array<int, string>
     */
    protected function columnFields(array $columns): array
    {
        return array_values(array_map(fn (Column $column): string => $column->field, $columns));
    }
}
