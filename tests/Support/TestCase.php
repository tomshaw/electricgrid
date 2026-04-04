<?php

namespace TomShaw\ElectricGrid\Tests\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\{Livewire, LivewireServiceProvider};
use Orchestra\Testbench\TestCase as Orchestra;
use TomShaw\ElectricGrid\Component;
use TomShaw\ElectricGrid\Providers\ElectricGridServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.key', 'base64:'.base64_encode(Str::random(32)));

        $this->registerLivewireComponents();

        Schema::create('test_models', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->tinyInteger('status')->default(0);
            $table->boolean('invoiced')->default(false);
            $table->timestamps();
        });
    }

    protected function getPackageProviders($app)
    {
        return [
            LivewireServiceProvider::class,
            ElectricGridServiceProvider::class,
        ];
    }

    private function registerLivewireComponents(): self
    {
        Livewire::component('electricgrid', Component::class);

        return $this;
    }
}
