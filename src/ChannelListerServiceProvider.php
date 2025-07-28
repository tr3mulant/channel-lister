<?php

declare(strict_types=1);

namespace IGE\ChannelLister;

use IGE\ChannelLister\Console\InstallCommand;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ChannelListerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        $this->registerPublishing();
        $this->registerCommands();

        if (! config('channel-lister.enabled')) {
            return;
        }

        $this->registerRoutes();
        $this->registerResources();
        $this->registerBladeComponents();

        /**
         * @var array<string, string> $middleware
         */
        $middleware = config('channel-lister.middleware', []);
        Route::middlewareGroup('channel-lister', $middleware);
    }

    /**
     * Register any package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/channel-lister.php', 'channel-lister');
    }

    protected function registerRoutes(): void
    {
        Route::group([
            'domain' => config('channel-lister.domain', null),
            'namespace' => 'IGE\ChannelLister\Http\Controllers',
            'prefix' => config('channel-lister.path'),
            'middleware' => 'channel-lister',
        ], function (): void {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });

        Route::group([
            'domain' => config('channel-lister.domain', null),
            'namespace' => 'App\Http\Controllers\Api',
            'prefix' => config('channel-lister.api_path'),
            'middleware' => 'api',
        ], function (): void {
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        });
    }

    protected function registerResources(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'channel-lister');
    }

    protected function registerBladeComponents(): void
    {
        Blade::componentNamespace('IGE\\ChannelLister\\View\\Components', 'channel-lister');
    }

    protected function registerCommands(): void
    {
        $this->commands([
            InstallCommand::class,
        ]);
    }

    /**
     * Register the package's publishable resources.
     *
     * @return void
     */
    protected function registerPublishing()
    {
        if ($this->app->runningInConsole()) {
            $publishesMigrationsMethod = method_exists($this, 'publishesMigrations')
                ? 'publishesMigrations'
                : 'publishes';

            $this->{$publishesMigrationsMethod}([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'channel-lister-migrations');

            $this->publishes([
                __DIR__.'/../resources' => resource_path('vendor/channel-lister'),
            ], ['channel-lister-resources', 'laravel-resources']);

            $this->publishes([
                __DIR__.'/../public' => public_path('vendor/channel-lister'),
            ], ['channel-lister-assets', 'laravel-assets']);

            $this->publishes([
                __DIR__.'/../config/channel-lister.php' => config_path('channel-lister.php'),
            ], 'channel-lister-config');

            $this->publishes([
                __DIR__.'/../stubs/ChannelListerServiceProvider.stub' => app_path('Providers/ChannelListerServiceProvider.php'),
            ], 'channel-lister-provider');
        }
    }
}
