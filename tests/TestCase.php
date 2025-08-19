<?php

namespace IGE\ChannelLister\Tests;

use IGE\ChannelLister\ChannelListerServiceProvider;
use IGE\ChannelLister\Services\AmazonSpApiService;
use IGE\ChannelLister\Services\AmazonTokenManager;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Testing\Concerns\InteractsWithViews;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\Concerns\WithLaravelMigrations;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as TestbenchTestCase;

/**
 * Common functionality for all Feature Tests
 */
#[WithMigration('laravel', 'session')]
abstract class TestCase extends TestbenchTestCase
{
    use InteractsWithViews, RefreshDatabase, WithLaravelMigrations, WithWorkbench;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the AmazonTokenManager service to avoid real authentication
        $mockTokenManager = \Mockery::mock(AmazonTokenManager::class);
        $mockTokenManager->shouldReceive('getAccessToken')->andReturn('fake-access-token');
        $mockTokenManager->shouldReceive('validateConfiguration')->andReturn([]);
        $mockTokenManager->shouldReceive('getTokenInfo')->andReturn([
            'access_token' => 'fake-access-token',
            'expires_at' => now()->addHour(),
        ]);

        $this->app->singleton(AmazonTokenManager::class, fn () => $mockTokenManager);

        $this->app->singleton(
            AmazonSpApiService::class,
            fn () => new AmazonSpApiService($this->app->make(AmazonTokenManager::class))
        );
    }

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string<\Illuminate\Support\ServiceProvider>>
     */
    protected function getPackageProviders($app)
    {
        return [
            ChannelListerServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function defineEnvironment($app)
    {
        // Setup default database to use sqlite :memory:
        tap($app['config'], function (Repository $config): void {
            $config->set('database.default', 'testbench');
            $config->set('database.connections.testbench', [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ]);
            $config->set('session.driver', 'array');
            $config->set('session.cookie', 'testbench_session');

            // Setup queue database connections.
            $config->set('queue.batching.database', 'testbench');
            $config->set('queue.failed.database', 'testbench');

            // Ensure channel-lister config is enabled and middleware is configured
            $config->set('channel-lister.enabled', true);
            $config->set('channel-lister.middleware', ['web']);
        });
    }
}
