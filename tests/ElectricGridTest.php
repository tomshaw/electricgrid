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
