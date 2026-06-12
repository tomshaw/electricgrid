<?php

use Livewire\Livewire;
use TomShaw\ElectricGrid\Tests\Components\SearchableComponent;
use TomShaw\ElectricGrid\Tests\Models\TestModel;

it('starts ascending when sorting a new column', function () {
    $component = Livewire::test(SearchableComponent::class);

    $component->set('orderBy', 'id');
    $component->set('orderDir', 'DESC');

    $component->call('handleSortOrder', 'name', true);

    expect($component->get('orderBy'))->toBe('name');
    expect($component->get('orderDir'))->toBe('ASC');
});

it('toggles direction when sorting the same column again', function () {
    $component = Livewire::test(SearchableComponent::class);

    $component->call('handleSortOrder', 'name', true);
    expect($component->get('orderDir'))->toBe('ASC');

    $component->call('handleSortOrder', 'name', true);
    expect($component->get('orderDir'))->toBe('DESC');

    $component->call('handleSortOrder', 'name', true);
    expect($component->get('orderDir'))->toBe('ASC');
});

it('ignores sort requests for non-sortable columns', function () {
    $component = Livewire::test(SearchableComponent::class);

    $component->set('orderBy', 'id');
    $component->call('handleSortOrder', 'email', '');

    expect($component->get('orderBy'))->toBe('id');
});

it('searches across columns with OR semantics end to end', function () {
    TestModel::create(['name' => 'RowAlpha', 'email' => null]);
    TestModel::create(['name' => 'RowBeta', 'email' => 'alpha@example.com']);
    TestModel::create(['name' => 'RowGamma', 'email' => 'gamma@example.com']);

    $component = Livewire::test(SearchableComponent::class);
    $component->set('searchTerm', 'alpha');

    $component->assertSee('RowAlpha');
    $component->assertSee('RowBeta');
    $component->assertDontSee('RowGamma');
});

it('shows rows with null searchable values after clearing the search', function () {
    TestModel::create(['name' => 'RowAlpha', 'email' => null]);
    TestModel::create(['name' => 'RowGamma', 'email' => 'gamma@example.com']);

    $component = Livewire::test(SearchableComponent::class);

    $component->set('searchTerm', 'gamma');
    $component->assertDontSee('RowAlpha');

    $component->set('searchTerm', '');
    $component->assertSee('RowAlpha');
    $component->assertSee('RowGamma');
});

it('does not store search state inside the filter array', function () {
    $component = Livewire::test(SearchableComponent::class);

    $component->set('searchTerm', 'anything');
    $component->call('handleSelectedLetter', 'A');

    expect($component->get('filter'))->toBe([]);
});

it('toggles the letter search on and off', function () {
    TestModel::create(['name' => 'Apple']);
    TestModel::create(['name' => 'Banana']);

    $component = Livewire::test(SearchableComponent::class);

    $component->call('handleSelectedLetter', 'A');
    $component->assertSee('Apple');
    $component->assertDontSee('Banana');

    $component->call('handleSelectedLetter', 'A');
    $component->assertSee('Apple');
    $component->assertSee('Banana');
});

it('applies the search to pagination totals', function () {
    for ($i = 0; $i < 30; $i++) {
        TestModel::create(['name' => "Common {$i}", 'email' => null]);
    }
    TestModel::create(['name' => 'Unique', 'email' => null]);

    $component = Livewire::test(SearchableComponent::class);
    $component->set('searchTerm', 'Unique');

    expect($component->instance()->shouldShowPerPageSelector())->toBe(false);
});
