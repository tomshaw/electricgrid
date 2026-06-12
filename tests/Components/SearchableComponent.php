<?php

namespace TomShaw\ElectricGrid\Tests\Components;

use Illuminate\Database\Eloquent\Builder;
use TomShaw\ElectricGrid\{Column, Component};
use TomShaw\ElectricGrid\Tests\Models\TestModel;

class SearchableComponent extends Component
{
    public bool $showCheckbox = false;

    public array $searchTermColumns = ['name', 'email'];

    public array $letterSearchColumns = ['name'];

    public function builder(): Builder
    {
        return TestModel::query();
    }

    public function columns(): array
    {
        return [
            Column::add('id', 'ID')->sortable(),
            Column::add('name', 'Name')->sortable(),
            Column::add('email', 'Email'),
        ];
    }
}
