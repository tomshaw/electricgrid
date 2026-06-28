<?php

declare(strict_types=1);

namespace TomShaw\ElectricGrid\Traits;

use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use TomShaw\ElectricGrid\{Action, CollectionDataSource, Column, DataExport};

trait GridActions
{
    public string $selectedAction = '';

    public function handleSelectedAction(): Response|BinaryFileResponse|null
    {
        /** @var Collection<int, Action> $actions */
        $actions = collect($this->actions())->flatten();

        $action = $actions->first(fn (Action $action) => $action->field === $this->selectedAction);

        if ($action === null) {
            return null;
        }

        if ($action->isExport) {
            $hiddenColumns = array_filter($this->hiddenColumns);

            $exportables = collect($this->columns())
                ->filter(fn (Column $column) => $column->exportable)
                ->reject(function (Column $column) use ($hiddenColumns) {
                    // A column's effective visibility in the grid is "visible XOR in hiddenColumns",
                    // because toggling a default-hidden column ($column->visible === false) reveals it.
                    // Reject the columns that are effectively hidden so exports mirror the grid.
                    return $column->visible === in_array($column->field, $hiddenColumns, true);
                });

            if ($exportables->isEmpty()) {
                return null;
            }

            $headings = $exportables->map(fn (Column $column) => $column->title)->values()->all();

            $dataSource = clone $this->dataSource();
            $dataSource->orderBy($this->orderBy, $this->orderDir);

            if ($dataSource instanceof CollectionDataSource) {
                if (! empty($this->checkboxValues)) {
                    $dataSource->collection = $dataSource->collection->whereIn($this->checkboxField, $this->checkboxValues);
                }

                $columns = $dataSource->transformColumnsForExport($exportables->values()->all());
                $collection = $dataSource->transformCollection($dataSource->collection, $columns);
            } else {
                if (! empty($this->checkboxValues)) {
                    $dataSource->query->whereIn($dataSource->query->qualifyColumn($this->checkboxField), $this->checkboxValues);
                }

                $columns = $dataSource->transformColumnsForExport($exportables->values()->all());
                $collection = $dataSource->transformCollection($dataSource->query->get(), $columns);
            }

            return $this->export($collection, $action, $headings);
        }

        if (empty($this->checkboxValues)) {
            return null;
        }

        if ($action->callable !== null) {
            ($action->callable)($this->selectedAction, $this->checkboxValues);
        }

        return null;
    }

    /**
     * @param  Collection<int, \stdClass>  $collection
     * @param  array<int, string>  $headings
     */
    public function export(Collection $collection, Action $action, array $headings): Response|BinaryFileResponse
    {
        $export = new DataExport($collection);

        $export->setHeadings($headings);

        $export->setFileName($action->fileName);

        $export->setStyles($action->styles);

        $export->setColumnWidths($action->columnWidths);

        return $export->download();
    }
}
