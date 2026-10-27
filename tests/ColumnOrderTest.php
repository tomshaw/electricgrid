<?php

declare(strict_types=1);

namespace TomShaw\ElectricGrid\Tests;

use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\IOFactory;
use TomShaw\ElectricGrid\Column;
use TomShaw\ElectricGrid\Tests\Components\TestComponent;
use TomShaw\ElectricGrid\Tests\Models\TestModel;

beforeEach(function () {
    $this->artisan('migrate');

    TestModel::create(['name' => 'Alpha', 'status' => 1, 'invoiced' => true]);
});

/**
 * @return array<int, string>
 */
function orderedFields(TestComponent $component): array
{
    return array_map(fn (Column $column): string => $column->field, $component->getColumnsProperty());
}

// Columns keep their columns() order until the user drags one
it('uses the default column order when no order is set', function () {
    $component = Livewire::test(TestComponent::class);

    expect($component->get('columnOrder'))->toBe([]);
    expect(orderedFields($component->instance()))->toBe(['id', 'name', 'status', 'invoiced', 'created_at', 'updated_at']);
});

// Dropping a column at a new position moves it there and stores the full order
it('moves a column to the dropped position', function () {
    $component = Livewire::test(TestComponent::class)
        ->call('handleColumnSort', 'invoiced', 0);

    expect($component->get('columnOrder'))->toBe(['invoiced', 'id', 'name', 'status', 'created_at', 'updated_at']);
    expect(orderedFields($component->instance()))->toBe(['invoiced', 'id', 'name', 'status', 'created_at', 'updated_at']);
});

// Out-of-range positions are clamped to the ends of the list
it('clamps the dropped position to the column list', function () {
    $component = Livewire::test(TestComponent::class)
        ->call('handleColumnSort', 'id', 99);

    expect(orderedFields($component->instance()))->toBe(['name', 'status', 'invoiced', 'created_at', 'updated_at', 'id']);

    $component->call('handleColumnSort', 'id', -5);

    expect($component->get('columnOrder'))->toBe([]);
});

// Unknown fields are ignored
it('ignores sort requests for fields that are not columns', function () {
    $component = Livewire::test(TestComponent::class)
        ->call('handleColumnSort', 'obsolete', 0);

    expect($component->get('columnOrder'))->toBe([]);
});

// Dragging a column back to its default spot clears the custom order
it('clears the order when it matches the default again', function () {
    $component = Livewire::test(TestComponent::class)
        ->call('handleColumnSort', 'name', 3)
        ->call('handleColumnSort', 'name', 1);

    expect($component->get('columnOrder'))->toBe([]);
});

// The rendered header follows the user's order
it('renders table headers in the custom order', function () {
    Livewire::test(TestComponent::class)
        ->call('handleColumnSort', 'status', 0)
        ->assertSeeInOrder(['Status', 'ID', 'Name', 'Invoiced']);
});

// Stored fields that no longer exist are skipped and new columns slot in after their default neighbour
it('skips stale fields and slots new columns after their default neighbour', function () {
    $component = Livewire::test(TestComponent::class)
        ->set('columnOrder', ['status', 'obsolete', 'id', 'name']);

    expect(orderedFields($component->instance()))->toBe(['status', 'invoiced', 'created_at', 'updated_at', 'id', 'name']);
});

// resetColumns() restores both the default order and visibility
it('resets column order and hidden columns', function () {
    $component = Livewire::test(TestComponent::class)
        ->call('handleToggleColumns', 'name')
        ->call('handleColumnSort', 'status', 0)
        ->call('resetColumns');

    expect($component->get('columnOrder'))->toBe([]);
    expect($component->get('hiddenColumns'))->toBe([]);
});

// The order is written to the session alongside the rest of the grid state
it('persists the column order to the session', function () {
    Livewire::test(TestComponent::class, ['persistFilters' => true])
        ->call('handleColumnSort', 'status', 0);

    expect(session('electricgrid.'.TestComponent::class)['columnOrder'])
        ->toBe(['status', 'id', 'name', 'invoiced', 'created_at', 'updated_at']);
});

// The persisted order is restored on mount, minus malformed entries and removed fields
it('restores and prunes the persisted column order on mount', function () {
    session()->put('electricgrid.'.TestComponent::class, [
        'columnOrder' => ['status', 'obsolete', 42, null, 'id'],
    ]);

    $component = Livewire::test(TestComponent::class, ['persistFilters' => true]);

    expect($component->get('columnOrder'))->toBe(['status', 'id']);
});

// clearSessionState() also drops the custom order
it('clears the column order with the session state', function () {
    $component = Livewire::test(TestComponent::class, ['persistFilters' => true])
        ->call('handleColumnSort', 'status', 0)
        ->call('clearSessionState');

    expect($component->get('columnOrder'))->toBe([]);
});

// Exports keep the default order unless exportColumnOrder is enabled
it('exports in the default order unless exportColumnOrder is enabled', function (bool $exportColumnOrder, array $headings) {
    $response = Livewire::test(TestComponent::class)
        ->set('exportColumnOrder', $exportColumnOrder)
        ->call('handleColumnSort', 'invoiced', 0)
        ->set('selectedAction', 'export-xlsx')
        ->instance()
        ->handleSelectedAction();

    $rows = IOFactory::load($response->getFile()->getPathname())->getActiveSheet()->toArray();

    expect($rows[0])->toBe($headings);
})->with([
    'default order' => [false, ['ID', 'Name', 'Status', 'Invoiced']],
    'user order' => [true, ['Invoiced', 'ID', 'Name', 'Status']],
]);
