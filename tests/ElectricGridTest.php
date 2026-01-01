<?php

namespace TomShaw\ElectricGrid\Tests;

use Livewire\Livewire;
use TomShaw\ElectricGrid\Tests\Components\TestComponent;
use TomShaw\ElectricGrid\Tests\Models\TestModel;

beforeEach(function () {
    $this->artisan('migrate');

    $this->theme = config('electricgrid.theme');

    $this->component = Livewire::test(TestComponent::class);

    TestModel::create(['name' => 'Test', 'status' => 1, 'invoiced' => true]);
});

// Test that the component renders successfully without throwing any exceptions
it('can render component successfully', function () {
    $this->component->assertSuccessful();
});

// Test that the component has the correct instance
it('has the correct component instance', function () {
    expect(get_class($this->component->instance()))->toBe(TestComponent::class);
});

// Test that the component has the correct view
it('has the correct view', function () {
    Livewire::test(TestComponent::class)->assertViewIs('electricgrid::'.$this->theme.'.table');
});

// Test that the component has the correct initial properties
it('has the correct initial properties', function () {
    expect($this->component->get('theme'))->toBe('tailwind');
});

// Test that session persistence is disabled by default
it('does not persist filters to session when persistFilters is false', function () {
    $component = Livewire::test(TestComponent::class);

    $component->set('searchTerm', 'test search');
    $component->set('perPage', 50);
    $component->set('orderBy', 'name');

    expect($component->get('persistFilters'))->toBe(false);
    expect(session()->has('electricgrid.'.TestComponent::class))->toBe(false);
});

// Test that session persistence works when enabled
it('persists filters to session when persistFilters is true', function () {
    $component = Livewire::test(TestComponent::class);

    $component->set('persistFilters', true);
    $component->set('searchTerm', 'test search');
    $component->set('perPage', 50);
    $component->set('orderBy', 'name');
    $component->set('orderDir', 'DESC');

    $sessionData = session('electricgrid.'.TestComponent::class);

    expect($sessionData)->not->toBeNull();
    expect($sessionData['searchTerm'])->toBe('test search');
    expect($sessionData['perPage'])->toBe(50);
    expect($sessionData['orderBy'])->toBe('name');
    expect($sessionData['orderDir'])->toBe('DESC');
});

// Test that session state is loaded on component mount
it('loads persisted state from session on mount', function () {
    session()->put('electricgrid.'.TestComponent::class, [
        'filter' => ['text' => ['name' => 'test']],
        'searchTerm' => 'persisted search',
        'searchLetter' => 'A',
        'perPage' => 100,
        'orderBy' => 'status',
        'orderDir' => 'DESC',
        'hiddenColumns' => ['invoiced'],
    ]);

    $component = Livewire::test(TestComponent::class, ['persistFilters' => true]);

    expect($component->get('searchTerm'))->toBe('persisted search');
    expect($component->get('searchLetter'))->toBe('A');
    expect($component->get('perPage'))->toBe(100);
    expect($component->get('orderBy'))->toBe('status');
    expect($component->get('orderDir'))->toBe('DESC');
    expect($component->get('filter'))->toBe(['text' => ['name' => 'test']]);
    expect($component->get('hiddenColumns'))->toBe(['invoiced']);
});

// Test that clearSessionState() clears the session and resets properties
it('clears session state when clearSessionState is called', function () {
    $component = Livewire::test(TestComponent::class);

    $component->set('persistFilters', true);
    $component->set('searchTerm', 'test search');
    $component->set('searchLetter', 'B');
    $component->set('filter', ['text' => ['name' => 'test']]);
    $component->set('hiddenColumns', ['status']);

    expect(session()->has('electricgrid.'.TestComponent::class))->toBe(true);

    $component->call('clearSessionState');

    expect(session()->has('electricgrid.'.TestComponent::class))->toBe(false);
    expect($component->get('searchTerm'))->toBe('');
    expect($component->get('searchLetter'))->toBe('');
    expect($component->get('filter'))->toBe([]);
    expect($component->get('hiddenColumns'))->toBe([]);
});

// Test that per-page selector is hidden when there are no records
it('hides per-page selector when there are no records', function () {
    TestModel::truncate();

    $component = Livewire::test(TestComponent::class);

    expect($component->instance()->shouldShowPerPageSelector())->toBe(false);
});

// Test that per-page selector is hidden when records are less than minimum per-page value
it('hides per-page selector when records are less than or equal to minimum per-page value', function () {
    TestModel::truncate();
    for ($i = 0; $i < 10; $i++) {
        TestModel::create(['name' => "Test $i", 'status' => 1, 'invoiced' => true]);
    }

    $component = Livewire::test(TestComponent::class);
    $component->set('perPageValues', [15, 30, 50, 100]);

    expect($component->instance()->shouldShowPerPageSelector())->toBe(false);
});

// Test that per-page selector is shown when there are enough records
it('shows per-page selector when there are enough records', function () {
    TestModel::truncate();
    for ($i = 0; $i < 20; $i++) {
        TestModel::create(['name' => "Test $i", 'status' => 1, 'invoiced' => true]);
    }

    $component = Livewire::test(TestComponent::class);
    $component->set('perPageValues', [15, 30, 50, 100]);

    expect($component->instance()->shouldShowPerPageSelector())->toBe(true);
});

// Test that per-page selector can be force hidden via showPerPage property
it('respects showPerPage property when hiding per-page selector', function () {
    TestModel::truncate();
    for ($i = 0; $i < 20; $i++) {
        TestModel::create(['name' => "Test $i", 'status' => 1, 'invoiced' => true]);
    }

    $component = Livewire::test(TestComponent::class);
    $component->set('showPerPage', false);

    expect($component->instance()->shouldShowPerPageSelector())->toBe(false);
});

// Test that available per-page values excludes options >= total records
it('filters out per-page values that are greater than or equal to total records', function () {
    TestModel::truncate();
    for ($i = 0; $i < 25; $i++) {
        TestModel::create(['name' => "Test $i", 'status' => 1, 'invoiced' => true]);
    }

    $component = Livewire::test(TestComponent::class);
    $component->set('perPageValues', [15, 30, 50, 100]);

    $availableValues = $component->instance()->getAvailablePerPageValues();

    expect($availableValues)->toBe([15]);
});

// Test that "All" option is shown when records are below threshold
it('shows "All" option when total records are below threshold', function () {
    TestModel::truncate();
    for ($i = 0; $i < 50; $i++) {
        TestModel::create(['name' => "Test $i", 'status' => 1, 'invoiced' => true]);
    }

    $component = Livewire::test(TestComponent::class);
    $component->set('showAllThreshold', 100);

    expect($component->instance()->shouldShowAllOption())->toBe(true);
});

// Test that "All" option is hidden when records exceed threshold
it('hides "All" option when total records exceed threshold', function () {
    TestModel::truncate();
    for ($i = 0; $i < 150; $i++) {
        TestModel::create(['name' => "Test $i", 'status' => 1, 'invoiced' => true]);
    }

    $component = Livewire::test(TestComponent::class);
    $component->set('showAllThreshold', 100);

    expect($component->instance()->shouldShowAllOption())->toBe(false);
});

// Test that "All" option can be force hidden via showAllOption property
it('respects showAllOption property when hiding "All" option', function () {
    TestModel::truncate();
    for ($i = 0; $i < 50; $i++) {
        TestModel::create(['name' => "Test $i", 'status' => 1, 'invoiced' => true]);
    }

    $component = Livewire::test(TestComponent::class);
    $component->set('showAllOption', false);

    expect($component->instance()->shouldShowAllOption())->toBe(false);
});

// Test that "All" option is hidden when there are no records
it('hides "All" option when there are no records', function () {
    TestModel::truncate();

    $component = Livewire::test(TestComponent::class);

    expect($component->instance()->shouldShowAllOption())->toBe(false);
});
