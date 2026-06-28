<?php

declare(strict_types=1);

namespace TomShaw\ElectricGrid\Concerns;

/**
 * Tracks columns that are computed (e.g. SQL aliases / derived values) so a data
 * source can skip table qualification and other column-name assumptions for them.
 */
trait ComputedColumns
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
}
