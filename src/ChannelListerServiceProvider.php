<?php

declare(strict_types=1);

namespace IGE\ChannelLister;

use IGE\ChannelLister\Console\AmazonTokenStatusCommand;
use IGE\ChannelLister\Console\InstallCommand;
use IGE\ChannelLister\Console\SeedFieldsCommand;
use IGE\ChannelLister\Contracts\MarketplaceListingProvider;
use IGE\ChannelLister\Http\Middleware\AmazonSpApiAuth;
use IGE\ChannelLister\Services\AmazonDataTransformer;
use IGE\ChannelLister\Services\AmazonListingFormProcessor;
use IGE\ChannelLister\Services\AmazonSpApiService;
use IGE\ChannelLister\Services\AmazonTokenManager;
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

        $this->registerMiddleware();
        $this->registerRoutes();
        $this->registerResources();
        $this->registerBladeComponents();
    }

    /**
     * Register any package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/channel-lister.php', 'channel-lister');

        $this->registerServices();
    }

    protected function registerServices(): void
    {
        // Register token manager
        $this->app->singleton(AmazonTokenManager::class, fn ($app): AmazonTokenManager => new AmazonTokenManager);

        // Register Amazon SP-API service
        $this->app->singleton(AmazonSpApiService::class, fn ($app): AmazonSpApiService => new AmazonSpApiService($app->make(AmazonTokenManager::class)));

        // Bind the marketplace provider interface
        $this->app->bind(MarketplaceListingProvider::class, AmazonSpApiService::class);

        // Register form processor
        $this->app->singleton(AmazonListingFormProcessor::class, fn ($app): AmazonListingFormProcessor => new AmazonListingFormProcessor($app->make(AmazonSpApiService::class)));

        // Register data transformer
        $this->app->singleton(AmazonDataTransformer::class, fn ($app): AmazonDataTransformer => new AmazonDataTransformer);

        // Register middleware
        $this->app->singleton(AmazonSpApiAuth::class, fn ($app): AmazonSpApiAuth => new AmazonSpApiAuth($app->make(AmazonTokenManager::class)));

        // Register shipping calculator service
        $this->app->singleton(\IGE\ChannelLister\Services\ShippingCalculatorService::class);
    }

    protected function registerMiddleware(): void
    {
        /**
         * @var array<string, string> $middleware
         */
        $middleware = config('channel-lister.middleware', []);
        Route::middlewareGroup('channel-lister', $middleware);
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
            'namespace' => 'IGE\ChannelLister\Http\Controllers\Api',
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
            AmazonTokenStatusCommand::class,
            SeedFieldsCommand::class,
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
