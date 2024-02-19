<?php

namespace TomShaw\ElectricGrid\Traits;

use TomShaw\ElectricGrid\Exceptions\RequiredColumnsHandler;

trait WithGridValidation
{
    public function validateColumns(): void
    {
        $selectedColumns = $this->builder->getQuery()->columns;

        if ($selectedColumns === null || in_array('*', $selectedColumns)) {
            return;
        }

        $selectedColumns = array_map(function ($column) {
            return substr($column, strpos($column, '.') + 1);
        }, $selectedColumns);

        // Include columns from eager loaded tables
        $eagerLoads = $this->builder->getEagerLoads();
        foreach ($eagerLoads as $relation => $constraint) {
            $relationModel = $this->builder->getModel()->$relation()->getRelated();
            $relationTable = $relationModel->getTable();
            $relationColumns = $relationModel->getConnection()->getSchemaBuilder()->getColumnListing($relationTable);
            $relationColumns = array_map(function ($column) use ($relation) {
                return $relation.'.'.$column;
            }, $relationColumns);
            $selectedColumns = array_merge($selectedColumns, $relationColumns);
        }

        // Treat columns selected in subqueries for relations as if they were selected in the main query
        foreach ($this->builder->getEagerLoads() as $relation => $constraint) {
            if ($constraint instanceof \Closure) {
                $relationQuery = $this->builder->getModel()->$relation()->getRelated()->newQuery();
                $constraint($relationQuery);
                $relationColumns = $relationQuery->getQuery()->columns;
                if ($relationColumns !== null) {
                    $selectedColumns = array_merge($selectedColumns, $relationColumns);
                }
            }
        }

        $missingColumns = array_diff($this->visibleColumns, $selectedColumns);

        if (! empty($missingColumns)) {
            throw RequiredColumnsHandler::make($missingColumns);
        }
    }
}
