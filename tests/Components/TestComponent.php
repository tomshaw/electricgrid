<?php

namespace TomShaw\ElectricGrid\Tests\Components;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use TomShaw\ElectricGrid\Filters\Filter;
use TomShaw\ElectricGrid\Tests\Models\TestModel;
use TomShaw\ElectricGrid\{Column, Component};

class TestComponent extends Component
{
    public bool $showCheckbox = true;

    public bool $showPagination = true;

    public bool $showPerPage = true;

    public function builder(): Builder
    {
        return TestModel::select('*');
    }

    public function columns(): array
    {
        return [
            Column::add('id', __('ID'))
                ->sortable()
                ->stylable('text-start')
                ->exportable(),

            Column::add('name', __('Name'))
                ->searchable()
                ->sortable(true)
                ->exportable(),

            Column::add('status', __('Status'))
                ->searchable()
                ->sortable()
                ->exportable(),

            Column::add('invoiced', __('Invoiced'))
                ->searchable()
                ->sortable()
                ->exportable(),

            Column::add('created_at', __('Created At'))
                ->callback(fn (TestModel $model) => Carbon::parse($model->created_at)->format('Y-m-d H:i'))
                ->sortable(),

            Column::add('updated_at', __('Updated At'))
                ->callback(fn (TestModel $model) => Carbon::parse($model->updated_at)->format('d/m/Y'))
                ->sortable()
                ->visible(false),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::number('id'),
            Filter::text('name'),
            Filter::multiselect('status')->options($this->statusOptions()),
            Filter::boolean('invoiced')->labels('Yes', 'No'),
            Filter::datepicker('created_at')->addDataAttribute('format', 'H:i'),
            Filter::datetimepicker('updated_at'),
        ];
    }

    public function statusOptions(): array
    {
        return ['New', 'Completed', 'Cancelled'];
    }
}
