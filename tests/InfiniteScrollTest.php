<?php

namespace TomShaw\ElectricGrid\Tests;

use Livewire\Livewire;
use TomShaw\ElectricGrid\Tests\Components\TestComponent;
use TomShaw\ElectricGrid\Tests\Models\TestModel;

beforeEach(function () {
    $this->artisan('migrate');
});

function seedTestModels(int $count): void
{
    for ($i = 1; $i <= $count; $i++) {
        TestModel::create(['name' => "Row $i", 'status' => 1, 'invoiced' => true]);
    }
}

// loadMore() should be a no-op when infinite scroll is disabled
it('does not increment loadedPages when infinite scroll is disabled', function () {
    seedTestModels(30);

    $component = Livewire::test(TestComponent::class)
        ->set('perPage', 10);

    $component->call('loadMore');

    expect($component->get('infiniteScroll'))->toBe(false);
    expect($component->get('loadedPages'))->toBe(1);
    expect($component->viewData('data')->count())->toBe(10);
});

// loadMore() should expand the result window when infinite scroll is enabled
it('grows the result window on successive loadMore calls', function () {
    seedTestModels(50);

    $component = Livewire::test(TestComponent::class)
        ->set('perPage', 10)
        ->set('infiniteScroll', true);

    expect($component->viewData('data')->count())->toBe(10);

    $component->call('loadMore');
    expect($component->get('loadedPages'))->toBe(2);
    expect($component->viewData('data')->count())->toBe(20);

    $component->call('loadMore');
    expect($component->get('loadedPages'))->toBe(3);
    expect($component->viewData('data')->count())->toBe(30);
});

// The sentinel row should render only when infinite scroll is on AND more rows remain
it('renders the sentinel only in infinite mode and hides the pager', function () {
    seedTestModels(30);

    $component = Livewire::test(TestComponent::class)
        ->set('perPage', 10)
        ->set('infiniteScroll', true);

    $component->assertSeeHtml('electricgrid-infinite-scroll');
    $component->assertDontSee('aria-label="Pagination Navigation"', false);

    $component->set('infiniteScroll', false);

    $component->assertDontSeeHtml('electricgrid-infinite-scroll');
    $component->assertSee('aria-label="Pagination Navigation"', false);
});

// The sentinel should disappear once all rows have been loaded
it('hides the sentinel when every row has been loaded', function () {
    seedTestModels(12);

    $component = Livewire::test(TestComponent::class)
        ->set('perPage', 10)
        ->set('infiniteScroll', true);

    $component->assertSeeHtml('electricgrid-infinite-scroll');

    $component->call('loadMore');

    expect($component->viewData('data')->count())->toBe(12);
    expect($component->viewData('data')->total())->toBe(12);
    $component->assertDontSeeHtml('electricgrid-infinite-scroll');
});

// State changes that reset pagination should also reset the infinite scroll accumulator
it('resets loadedPages when filters, sort, search, or per-page change', function (string $action) {
    seedTestModels(60);

    $component = Livewire::test(TestComponent::class)
        ->set('perPage', 10)
        ->set('infiniteScroll', true);

    $component->call('loadMore');
    $component->call('loadMore');
    expect($component->get('loadedPages'))->toBe(3);

    match ($action) {
        'searchTerm' => $component->set('searchTerm', 'Row 1'),
        'searchLetter' => $component->set('searchLetter', 'R'),
        'sort' => $component->call('handleSortOrder', 'name', true),
        'perPage' => $component->set('perPage', 15),
        'clearSession' => $component->call('clearSessionState'),
    };

    expect($component->get('loadedPages'))->toBe(1);
})->with([
    'searchTerm',
    'searchLetter',
    'sort',
    'perPage',
    'clearSession',
]);
