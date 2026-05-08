<?php

namespace TomShaw\ElectricGrid\Tests;

use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use TomShaw\ElectricGrid\DataExport;
use TomShaw\ElectricGrid\Tests\Components\TestComponent;
use TomShaw\ElectricGrid\Tests\Models\TestModel;

beforeEach(function () {
    $this->artisan('migrate');

    TestModel::create(['name' => 'Alpha', 'status' => 1, 'invoiced' => true]);
    TestModel::create(['name' => 'Beta', 'status' => 2, 'invoiced' => false]);
    TestModel::create(['name' => 'Gamma', 'status' => 1, 'invoiced' => true]);
});

it('exports the entire filtered dataset when no rows are selected', function () {
    Excel::fake();

    Livewire::test(TestComponent::class)
        ->set('selectedAction', 'export-xlsx')
        ->call('handleSelectedAction');

    Excel::assertDownloaded('export.xlsx', function (DataExport $export) {
        return $export->collection()->count() === 3;
    });
});

it('respects active filters when exporting with no rows selected', function () {
    Excel::fake();

    Livewire::test(TestComponent::class)
        ->set('filter', ['boolean' => ['invoiced' => 'true']])
        ->set('selectedAction', 'export-xlsx')
        ->call('handleSelectedAction');

    Excel::assertDownloaded('export.xlsx', function (DataExport $export) {
        return $export->collection()->count() === 2;
    });
});

it('exports only selected rows when checkboxes are populated', function () {
    Excel::fake();

    $first = TestModel::query()->orderBy('id')->first();

    Livewire::test(TestComponent::class)
        ->set('checkboxValues', [$first->id])
        ->set('selectedAction', 'export-xlsx')
        ->call('handleSelectedAction');

    Excel::assertDownloaded('export.xlsx', function (DataExport $export) use ($first) {
        $rows = $export->collection();

        return $rows->count() === 1
            && $rows->first()->name === $first->name;
    });
});
