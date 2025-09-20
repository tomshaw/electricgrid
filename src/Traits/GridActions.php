<?php

namespace TomShaw\ElectricGrid\Traits;

use Illuminate\Database\Eloquent\Collection as DatabaseCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use TomShaw\ElectricGrid\{BuilderDataSource, CollectionDataSource, DataExport};

trait GridActions
{
    public string $selectedAction;

    public function handleSelectedAction(): Response|BinaryFileResponse|null
    {
        $where = collect($this->actions())->flatten()->where('field', $this->selectedAction);

        if ($where->isEmpty() || empty($this->checkboxValues)) {
            return null;
        }

        $action = collect((array) $where->first());

        $columns = $this->columns();

        if ($action->get('isExport')) {
            $hiddenColumns = array_filter($this->hiddenColumns);

            $exportables = collect($columns)
                ->filter->exportable
                ->reject(function ($column) use ($hiddenColumns) {
                    return in_array($column->field, $hiddenColumns);
                });

            if ($exportables->isEmpty()) {
                return null;
            }

            $action->put('headings', $exportables->pluck('title')->toArray());

            $builder = $this->builder();

            if ($builder instanceof DatabaseCollection) {
                $dataSource = CollectionDataSource::make($builder);
                $dataSource->filter($this->filter);
                $dataSource->orderBy($this->orderBy, $this->orderDir);

                // Filter collection by selected checkbox values
                $dataSource->collection = $dataSource->collection->whereIn($this->checkboxField, $this->checkboxValues);

                $columns = $dataSource->transformColumns($exportables->toArray());
                $collection = $dataSource->transformCollection($dataSource->collection, $columns);
            } else {
                $dataSource = BuilderDataSource::make($builder);
                $dataSource->filter($this->filter);
                $dataSource->orderBy($this->orderBy, $this->orderDir);
                $dataSource->query->whereIn("{$dataSource->query->from}.{$this->checkboxField}", $this->checkboxValues);

                $columns = $dataSource->transformColumns($exportables->toArray());
                $collection = $dataSource->transformCollection($dataSource->query->get(), $columns);
            }

            return $this->export($collection, $action);
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
