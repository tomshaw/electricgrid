<?php

namespace TomShaw\ElectricGrid\Tests;

use Livewire\Livewire;
use TomShaw\ElectricGrid\{BuilderDataSource, CollectionDataSource, Column};
use TomShaw\ElectricGrid\Tests\Components\{TestComponent, TestComponentWithRowClick};
use TomShaw\ElectricGrid\Tests\Models\TestModel;

beforeEach(function () {
    $this->artisan('migrate');
});

it('defaults rowClick to null on the base component', function () {
    expect((new TestComponent)->rowClick())->toBeNull();
});

it('does not set __route when no rowClick is provided', function () {
    TestModel::create(['name' => 'Alpha', 'status' => 1, 'invoiced' => true]);

    $source = BuilderDataSource::make(TestModel::query());
    $paginator = $source->paginate(15);
    $columns = [Column::add('name', 'Name')];

    $result = $source->transform($paginator, $columns);
    $row = $result->getCollection()->first();

    expect(property_exists($row, '__route'))->toBeFalse();
});

it('stashes __route on each row via BuilderDataSource', function () {
    TestModel::create(['name' => 'Alpha', 'status' => 1, 'invoiced' => true]);
    TestModel::create(['name' => 'Beta', 'status' => 2, 'invoiced' => false]);

    $source = BuilderDataSource::make(TestModel::query()->orderBy('id'));
    $paginator = $source->paginate(15);
    $columns = [Column::add('name', 'Name')];

    $result = $source->transform(
        $paginator,
        $columns,
        fn (TestModel $model) => "/items/{$model->id}/{$model->name}",
    );

    $rows = $result->getCollection();
    expect($rows->first()->__route)->toBe('/items/1/Alpha');
    expect($rows->last()->__route)->toBe('/items/2/Beta');
});

it('stashes __route on each row via CollectionDataSource', function () {
    $data = collect([
        (object) ['id' => 1, 'name' => 'Alpha'],
        (object) ['id' => 2, 'name' => 'Beta'],
    ]);

    $source = CollectionDataSource::make($data);
    $paginator = $source->paginate(15);
    $columns = [Column::add('name', 'Name')];

    $result = $source->transform(
        $paginator,
        $columns,
        fn ($row) => "/items/{$row->id}",
    );

    $rows = $result->getCollection();
    expect($rows->first()->__route)->toBe('/items/1');
    expect($rows->last()->__route)->toBe('/items/2');
});

it('stashes __route when source is an array', function () {
    $source = CollectionDataSource::make([
        ['id' => 1, 'name' => 'Alpha'],
        ['id' => 2, 'name' => 'Beta'],
    ]);
    $paginator = $source->paginate(15);
    $columns = [Column::add('name', 'Name')];

    $result = $source->transform(
        $paginator,
        $columns,
        fn ($row) => "/items/{$row->id}",
    );

    expect($result->getCollection()->first()->__route)->toBe('/items/1');
});

it('honors per-row null returned from rowClick closure', function () {
    TestModel::create(['name' => 'Active', 'status' => 1, 'invoiced' => true]);
    TestModel::create(['name' => 'Inactive', 'status' => 2, 'invoiced' => false]);

    $source = BuilderDataSource::make(TestModel::query()->orderBy('id'));
    $paginator = $source->paginate(15);
    $columns = [Column::add('name', 'Name')];

    $result = $source->transform(
        $paginator,
        $columns,
        fn (TestModel $model) => $model->status === 1 ? '/items/'.$model->id : null,
    );

    $rows = $result->getCollection();
    expect($rows->first()->__route)->toBe('/items/1');
    expect($rows->last()->__route)->toBeNull();
});

it('renders the click handler on rows when rowClick is overridden', function () {
    TestModel::create(['name' => 'Active', 'status' => 1, 'invoiced' => true]);

    $component = Livewire::test(TestComponentWithRowClick::class);

    $component->assertSee("window.location.href='/test/1'", false);
});

it('skips rendering the handler for rows where rowClick returns null', function () {
    TestModel::create(['name' => 'Active', 'status' => 1, 'invoiced' => true]);
    TestModel::create(['name' => 'Inactive', 'status' => 2, 'invoiced' => false]);

    $component = Livewire::test(TestComponentWithRowClick::class);

    $component->assertSee("window.location.href='/test/1'", false);
    $component->assertDontSee("window.location.href='/test/2'", false);
});

it('does not render any row click handler when rowClick is not overridden', function () {
    TestModel::create(['name' => 'Active', 'status' => 1, 'invoiced' => true]);

    $component = Livewire::test(TestComponent::class);

    $component->assertDontSee('window.location.href', false);
});
