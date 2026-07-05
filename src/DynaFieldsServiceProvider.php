<?php

namespace RSE\DynaFields;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use RSE\DynaFields\Livewire\CustomFieldsForm;
use RSE\DynaFields\Support\Commands\InstallCommand;

class DynaFieldsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/dynafields.php', 'dynafields');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'dynafields');
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'dynafields');

        if (config('dynafields.livewire.enabled', true) && class_exists(Livewire::class) && app()->bound('livewire')) {
            Livewire::component('dynafields::form', CustomFieldsForm::class);
        }

        if (config('dynafields.routes.enabled', true)) {
            $this->registerRoutes();
        }

        $this->publishes([
            __DIR__ . '/../config/dynafields.php' => config_path('dynafields.php'),
        ], 'dynafields-config');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/dynafields'),
        ], 'dynafields-views');

        $this->publishes([
            __DIR__ . '/../resources/lang' => lang_path('vendor/dynafields'),
        ], 'dynafields-lang');

        $this->commands([InstallCommand::class]);
    }

    private function registerRoutes(): void
    {
        $prefix     = config('dynafields.routes.prefix', 'dynafields');
        $name       = config('dynafields.routes.name', 'dynafields.');
        $middleware = config('dynafields.routes.middleware', ['web', 'auth']);

        Route::middleware($middleware)
            ->prefix($prefix)
            ->name($name)
            ->group(__DIR__ . '/../routes/web.php');
    }
}
