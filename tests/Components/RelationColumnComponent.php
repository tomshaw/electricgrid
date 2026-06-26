<?php

declare(strict_types=1);

namespace TomShaw\ElectricGrid\Tests\Components;

use Illuminate\Database\Eloquent\Builder;
use TomShaw\ElectricGrid\{Column, Component};
use TomShaw\ElectricGrid\Filters\Filter;
use TomShaw\ElectricGrid\Tests\Models\TestModel;

class RelationColumnComponent extends Component
{
    public string $theme = 'tailwind';

    public function builder(): Builder
    {
        return TestModel::select('*');
    }

    public function columns(): array
    {
        return [
            Column::add('id', __('ID'))
                ->sortable(),

            Column::add('name', __('Name'))
                ->sortable(),

            Column::add('author.name', __('Author'))
                ->sortable(),

            Column::add('author.company.name', __('Company'))
                ->sortable(),
        ];
    }

    public function filters(): array
    {
        return [
            Filter::number('id'),
            Filter::text('name'),
            Filter::text('author.name'),
            Filter::text('author.company.name'),
        ];
    }
}
