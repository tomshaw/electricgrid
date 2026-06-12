<?php

declare(strict_types=1);

namespace TomShaw\ElectricGrid\Traits;

use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use TomShaw\ElectricGrid\{CollectionDataSource, DataExport};

trait GridActions
{
    public string $selectedAction = '';

    public function handleSelectedAction(): Response|BinaryFileResponse|null
    {
        $where = collect($this->actions())->flatten()->where('field', $this->selectedAction);

        if ($where->isEmpty()) {
            return null;
        }

        $action = collect((array) $where->first());

        $columns = $this->columns();

        if ($action->get('isExport')) {
            $hiddenColumns = array_filter($this->hiddenColumns);

            $exportables = collect($columns)
                ->filter->exportable
                ->reject(function ($column) use ($hiddenColumns) {
                    // A column's effective visibility in the grid is "visible XOR in hiddenColumns",
                    // because toggling a default-hidden column ($column->visible === false) reveals it.
                    // Reject the columns that are effectively hidden so exports mirror the grid.
                    return $column->visible === in_array($column->field, $hiddenColumns, true);
                });

            if ($exportables->isEmpty()) {
                return null;
            }

            $action->put('headings', $exportables->pluck('title')->toArray());

            $dataSource = clone $this->dataSource();
            $dataSource->orderBy($this->orderBy, $this->orderDir);

            if ($dataSource instanceof CollectionDataSource) {
                if (! empty($this->checkboxValues)) {
                    $dataSource->collection = $dataSource->collection->whereIn($this->checkboxField, $this->checkboxValues);
                }

                $columns = $dataSource->transformColumnsForExport($exportables->toArray());
                $collection = $dataSource->transformCollection($dataSource->collection, $columns);
            } else {
                if (! empty($this->checkboxValues)) {
                    $dataSource->query->whereIn($dataSource->query->qualifyColumn($this->checkboxField), $this->checkboxValues);
                }

                $columns = $dataSource->transformColumnsForExport($exportables->toArray());
                $collection = $dataSource->transformCollection($dataSource->query->get(), $columns);
            }

            return $this->export($collection, $action);
        }

        if (empty($this->checkboxValues)) {
            return null;
        }

        if ($action->has('callable') && is_callable($action->get('callable'))) {
            $callable = $action->get('callable');
            $callable($this->selectedAction, $this->checkboxValues);
        }

        return null;
    }

    public function export(Collection $collection, Collection $action): Response|BinaryFileResponse
    {
        $export = new DataExport($collection);

        $export->setHeadings($action->get('headings'));

        $export->setFileName($action->get('fileName'));

        $export->setStyles($action->get('styles'));

        $export->setColumnWidths($action->get('columnWidths'));

        return $export->download();
    }
}
