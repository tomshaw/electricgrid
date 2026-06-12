<?php

declare(strict_types=1);

namespace TomShaw\ElectricGrid\Tests;

use Livewire\Livewire;
use TomShaw\ElectricGrid\Tests\Components\TestComponent;

// No caption element should render while the text is unset
it('renders no caption by default', function () {
    Livewire::test(TestComponent::class)
        ->assertDontSeeHtml('<caption');
});

// caption() renders the escaped text in a top-aligned caption element
it('renders a top caption with the given text', function () {
    $component = Livewire::test(TestComponent::class)
        ->call('caption', 'Q2 sales by region');

    expect($component->instance()->captionSide)->toBe('top');
    $component->assertSeeHtml('caption-top')
        ->assertSeeHtml('Q2 sales by region');
});

// A 'bottom' side places the caption below the table via caption-side
it('renders a bottom caption when requested', function () {
    $component = Livewire::test(TestComponent::class)
        ->call('caption', 'Figures in USD', 'bottom');

    expect($component->instance()->captionSide)->toBe('bottom');
    $component->assertSeeHtml('caption-bottom');
});

// Any side other than 'bottom' normalizes to 'top'
it('normalizes an unknown side to top', function () {
    $component = Livewire::test(TestComponent::class)
        ->call('caption', 'Heading', 'sideways');

    expect($component->instance()->captionSide)->toBe('top');
});

// Caption text is escaped by Blade, preventing markup injection
it('escapes caption text to prevent injection', function () {
    $component = Livewire::test(TestComponent::class)
        ->call('caption', '<script>alert(1)</script>');

    $component->assertDontSeeHtml('<script>alert(1)</script>')
        ->assertSeeHtml('&lt;script&gt;');
});

// An empty string is treated as no caption
it('renders no caption for empty text', function () {
    Livewire::test(TestComponent::class)
        ->call('caption', '')
        ->assertDontSeeHtml('<caption');
});

// The fluent method returns the component for chaining
it('returns the component instance for chaining', function () {
    $component = Livewire::test(TestComponent::class)->instance();

    expect($component->caption('Heading'))->toBe($component);
});
