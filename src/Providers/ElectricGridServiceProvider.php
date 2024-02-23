<?php

namespace TomShaw\ElectricGrid\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use TomShaw\ElectricGrid\Assets\{Scripts, Styles};
use TomShaw\ElectricGrid\Component;
use TomShaw\ElectricGrid\Console\Commands\{InstallCommand, UpdateCommand};

class ElectricGridServiceProvider extends ServiceProvider
{
    /**
     * Package name.
     */
    private string $packageName = 'electricgrid';

    /**
     * Perform post-registration booting of services.
     */
    public function boot(): void
    {
        $this->loadViews();
        $this->loadLocalization();
        $this->registerLivewireComponents();
        $this->registerBladeComponents();
        $this->registerPublishableResources();
        $this->registerBladeDirectives();
    }

    /**
     * Register bindings in the container.
     */
    public function register(): void
    {
        $this->mergeConfig();
        $this->registerCommands();
    }

    /**
     * Load views.
     */
    protected function loadViews(): void
    {
        $this->loadViewsFrom(__DIR__.'/../../resources/views', $this->packageName);
    }

    /**
     * Load localization.
     */
    protected function loadLocalization(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../../resources/lang', $this->packageName);
    }

    /**
     * Register Livewire components.
     */
    protected function registerLivewireComponents(): void
    {
        Livewire::component($this->packageName, Component::class);
    }

    /**
     * Register Blade components.
     */
    protected function registerBladeComponents(): void
    {
        Blade::component('electricgrid::scripts', Scripts::class);
        Blade::component('electricgrid::styles', Styles::class);
    }

    /**
     * Register publishable resources.
     */
    protected function registerPublishableResources(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../resources/config/config.php' => config_path('electricgrid.php'),
            ], 'electricgrid.config');
            $this->publishes([
                __DIR__.'/../../resources/views' => resource_path('views/vendor/electricgrid'),
            ], 'electricgrid.views');
        }
    }

    /**
     * Register Blade directives.
     */
    protected function registerBladeDirectives(): void
    {
        Blade::directive('electricgridStyles', function () {
            return "<?php echo view('electricgrid::assets.styles')->render(); ?>";
        });

        Blade::directive('electricgridScripts', function () {
            return "<?php echo view('electricgrid::assets.scripts')->render(); ?>";
        });
    }

    /**
     * Merge configuration.
     */
    protected function mergeConfig(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../resources/config/config.php', $this->packageName);
    }

    /**
     * Register console commands.
     */
    protected function registerCommands(): void
    {
        $this->commands([
            InstallCommand::class,
            UpdateCommand::class,
        ]);
    }
}
