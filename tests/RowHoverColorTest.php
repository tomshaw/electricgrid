<?php

declare(strict_types=1);

namespace TomShaw\ElectricGrid\Tests;

use Livewire\Livewire;
use TomShaw\ElectricGrid\Tests\Components\TestComponent;

// No custom properties should be emitted while both hover colors are unset
it('emits no wrapper style by default', function () {
    $component = Livewire::test(TestComponent::class);

    expect($component->instance()->wrapperStyle())->toBe('');
    $component->assertDontSeeHtml('--eg-row-hover');
});

// rowHover() sets the light custom property on the wrapper
it('emits the light-mode hover custom property when set', function () {
    $component = Livewire::test(TestComponent::class)
        ->call('rowHover', '#fee2e2');

    expect($component->instance()->wrapperStyle())->toBe('--eg-row-hover: #fee2e2');
    $component->assertSeeHtml('style="--eg-row-hover: #fee2e2"');
});

// rowHover() accepts a dark-only value, consumed by the .dark rule
it('emits the dark-mode hover custom property when set', function () {
    $component = Livewire::test(TestComponent::class)
        ->call('rowHover', null, '#7f1d1d');

    expect($component->instance()->wrapperStyle())->toBe('--eg-row-hover-dark: #7f1d1d');
});

// Both modes can be configured in one call and are emitted together
it('emits both light and dark hover custom properties together', function () {
    $component = Livewire::test(TestComponent::class)
        ->call('rowHover', '#fee2e2', '#7f1d1d');

    expect($component->instance()->wrapperStyle())
        ->toBe('--eg-row-hover: #fee2e2; --eg-row-hover-dark: #7f1d1d');
});

// Shorthand and 8-digit (alpha) hex values are accepted
it('accepts 3, 6, and 8 digit hex values', function (string $hex) {
    $component = Livewire::test(TestComponent::class)
        ->call('rowHover', $hex);

    expect($component->instance()->wrapperStyle())->toBe("--eg-row-hover: {$hex}");
})->with(['#fff', '#ffffff', '#ffffff80']);

// Anything that is not a strict hex value is dropped, preventing CSS injection
it('rejects non-hex values to prevent style injection', function (string $malicious) {
    $component = Livewire::test(TestComponent::class)
        ->call('rowHover', $malicious);

    expect($component->instance()->wrapperStyle())->toBe('');
})->with([
    'red; } body { display: none }',
    'url(javascript:alert(1))',
    'bg-red-500',
    'rgb(255,0,0)',
    '#xyz',
    '#12',
]);

// The fluent method returns the component for chaining
it('returns the component instance for chaining', function () {
    $component = Livewire::test(TestComponent::class)->instance();

    expect($component->rowHover('#fee2e2'))->toBe($component);
});
