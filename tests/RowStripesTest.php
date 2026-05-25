<?php

namespace TomShaw\ElectricGrid\Tests;

use Livewire\Livewire;
use TomShaw\ElectricGrid\Tests\Components\TestComponent;

// No stripe custom properties should be emitted while all colors are unset
it('emits no stripe custom properties by default', function () {
    $component = Livewire::test(TestComponent::class);

    expect($component->instance()->wrapperStyle())->toBe('');
    $component->assertDontSeeHtml('--eg-row-odd');
    $component->assertDontSeeHtml('--eg-row-even');
});

// A single named argument tints only the even rows
it('emits the even-row custom property when set', function () {
    $component = Livewire::test(TestComponent::class)
        ->call('rowStripes', null, '#f9fafb');

    expect($component->instance()->wrapperStyle())->toBe('--eg-row-even: #f9fafb');
    $component->assertSeeHtml('style="--eg-row-even: #f9fafb"');
});

// The odd-row custom property is independent of even
it('emits the odd-row custom property when set', function () {
    $component = Livewire::test(TestComponent::class)
        ->call('rowStripes', '#ffffff');

    expect($component->instance()->wrapperStyle())->toBe('--eg-row-odd: #ffffff');
});

// All four colors are emitted together in a stable order
it('emits odd, even, and both dark custom properties together', function () {
    $component = Livewire::test(TestComponent::class)
        ->call('rowStripes', '#ffffff', '#f3f4f6', '#1f2937', '#111827');

    expect($component->instance()->wrapperStyle())
        ->toBe('--eg-row-odd: #ffffff; --eg-row-even: #f3f4f6; --eg-row-odd-dark: #1f2937; --eg-row-even-dark: #111827');
});

// Stripes and hover share the wrapper and are emitted together
it('emits stripe and hover custom properties together', function () {
    $component = Livewire::test(TestComponent::class)->instance();
    $component->rowHover('#fee2e2');
    $component->rowStripes(even: '#f9fafb');

    expect($component->wrapperStyle())
        ->toBe('--eg-row-hover: #fee2e2; --eg-row-even: #f9fafb');
});

// Shorthand and 8-digit (alpha) hex values are accepted
it('accepts 3, 6, and 8 digit hex values', function (string $hex) {
    $component = Livewire::test(TestComponent::class)
        ->call('rowStripes', null, $hex);

    expect($component->instance()->wrapperStyle())->toBe("--eg-row-even: {$hex}");
})->with(['#fff', '#ffffff', '#ffffff80']);

// Anything that is not a strict hex value is dropped, preventing CSS injection
it('rejects non-hex stripe values to prevent style injection', function (string $malicious) {
    $component = Livewire::test(TestComponent::class)
        ->call('rowStripes', $malicious, $malicious);

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

    expect($component->rowStripes('#ffffff'))->toBe($component);
});
