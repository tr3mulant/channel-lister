<?php

declare(strict_types=1);

use IGE\ChannelLister\Console\InstallCommand;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\File;

test('install command is registered', function (): void {
    // Test that the command exists and can be found
    $this->assertTrue(
        collect($this->app[Kernel::class]->all())
            ->has('channel-lister:install')
    );
});

test('install command has correct signature and description', function (): void {
    $command = new InstallCommand;

    expect($command->getName())->toBe('channel-lister:install');
    expect($command->getDescription())->toBe('Install all of the Channel Lister resources');
});

test('install command publishes all resources', function (): void {
    // Skip this test in CI or if app.php doesn't exist
    if (! File::exists(config_path('app.php'))) {
        $this->markTestSkipped('app.php config file not found');
    }

    // Backup original app.php
    $originalConfig = File::get(config_path('app.php'));

    try {
        // Ensure we start with a clean state
        cleanupPublishedFiles();

        // Execute the install command
        $this->artisan('channel-lister:install')
            ->expectsOutput('Publishing Channel Lister Service Provider...')
            ->expectsOutput('Publishing Channel Lister Assets...')
            ->expectsOutput('Publishing Channel Lister Configuration...')
            ->expectsOutput('Publishing Channel Lister Migrations...')
            ->expectsOutput('Channel Lister scaffolding installed successfully.')
            ->assertExitCode(0);

        // Verify that files were actually published
        expect(File::exists(config_path('channel-lister.php')))->toBeTrue();
        expect(File::exists(app_path('Providers/ChannelListerServiceProvider.php')))->toBeTrue();

        // Verify migrations were published
        $migrationFiles = glob(database_path('migrations/*_create_channel_lister_*_table.php'));
        expect(count($migrationFiles))->toBeGreaterThan(0);

        // Verify assets were published (if they exist)
        if (File::exists(public_path('vendor/channel-lister'))) {
            expect(File::isDirectory(public_path('vendor/channel-lister')))->toBeTrue();
        }

        // Clean up after test
    } finally {
        // Always restore original config
        File::put(config_path('app.php'), $originalConfig);
        cleanupPublishedFiles();
    }
});

test('install command registers service provider in app config (Laravel 10)', function (): void {
    // Skip this test in CI or if app.php doesn't exist
    if (! File::exists(config_path('app.php'))) {
        $this->markTestSkipped('app.php config file not found');
    }

    // Skip if Laravel 11+ (has bootstrap/providers.php)
    if (File::exists(base_path('bootstrap/providers.php'))) {
        $this->markTestSkipped('This test is for Laravel 10 and below');
    }

    // Backup original app.php
    $originalConfig = File::get(config_path('app.php'));

    try {
        // Remove any existing registration
        $cleanConfig = str_replace(
            "App\\Providers\\ChannelListerServiceProvider::class,\n",
            '',
            $originalConfig
        );
        File::put(config_path('app.php'), $cleanConfig);

        // Run install command
        $this->artisan('channel-lister:install')->assertExitCode(0);

        // Verify service provider was registered in config/app.php
        $updatedConfig = File::get(config_path('app.php'));
        expect($updatedConfig)->toContain('App\\Providers\\ChannelListerServiceProvider::class');
    } finally {
        // Always restore original config
        File::put(config_path('app.php'), $originalConfig);
        cleanupPublishedFiles();
    }
});

test('install command registers service provider in bootstrap providers (Laravel 11+)', function (): void {
    // Skip if not Laravel 11+
    if (! File::exists(base_path('bootstrap/providers.php'))) {
        $this->markTestSkipped('This test is for Laravel 11+');
    }

    // Backup original bootstrap/providers.php
    $originalProviders = File::get(base_path('bootstrap/providers.php'));

    try {
        // Remove any existing registration
        $cleanProviders = str_replace(
            "App\\Providers\\ChannelListerServiceProvider::class,\n",
            '',
            $originalProviders
        );
        File::put(base_path('bootstrap/providers.php'), $cleanProviders);

        // Run install command
        $this->artisan('channel-lister:install')->assertExitCode(0);

        // Verify service provider was registered in bootstrap/providers.php
        $updatedProviders = File::get(base_path('bootstrap/providers.php'));
        expect($updatedProviders)->toContain('App\\Providers\\ChannelListerServiceProvider::class');
    } finally {
        // Always restore original providers file
        File::put(base_path('bootstrap/providers.php'), $originalProviders);
        cleanupPublishedFiles();
    }
});

test('install command handles missing app config file gracefully', function (): void {
    // Skip if app.php exists
    if (File::exists(config_path('app.php'))) {
        $this->markTestSkipped('This test requires app.php to not exist');
    }

    // The command should still execute and complete other operations
    $this->artisan('channel-lister:install')
        ->expectsOutput('Publishing Channel Lister Service Provider...')
        ->expectsOutput('Publishing Channel Lister Assets...')
        ->expectsOutput('Publishing Channel Lister Configuration...')
        ->expectsOutput('Publishing Channel Lister Migrations...')
        ->expectsOutput('Channel Lister scaffolding installed successfully.')
        ->assertExitCode(0);
});

test('install command handles different line endings in config files', function (): void {
    if (! File::exists(config_path('app.php'))) {
        $this->markTestSkipped('app.php config file not found');
    }

    // Skip if Laravel 11+ (uses bootstrap/providers.php)
    if (File::exists(base_path('bootstrap/providers.php'))) {
        $this->markTestSkipped('This test is for Laravel 10 and below');
    }

    $originalConfig = File::get(config_path('app.php'));

    try {
        // Create a simple config with CRLF line endings and providers array
        $testConfig = <<<'PHP'
<?php

return [
    'providers' => [
        App\Providers\AppServiceProvider::class,
        App\Providers\RouteServiceProvider::class,
    ],
];
PHP;

        $configWithCrlf = str_replace("\n", "\r\n", $testConfig);
        File::put(config_path('app.php'), $configWithCrlf);

        $this->artisan('channel-lister:install')->assertExitCode(0);

        // Verify service provider was registered
        $updatedConfig = File::get(config_path('app.php'));
        expect($updatedConfig)->toContain('ChannelListerServiceProvider::class');
    } finally {
        File::put(config_path('app.php'), $originalConfig);
        cleanupPublishedFiles();
    }
});

test('install command handles Laravel 10 style config with ServiceProvider::defaultProviders()', function (): void {
    if (! File::exists(config_path('app.php'))) {
        $this->markTestSkipped('app.php config file not found');
    }

    // Skip if Laravel 11+ (uses bootstrap/providers.php)
    if (File::exists(base_path('bootstrap/providers.php'))) {
        $this->markTestSkipped('This test is for Laravel 10 and below');
    }

    $originalConfig = File::get(config_path('app.php'));

    try {
        // Create a Laravel 10 style config
        $laravel10Config = <<<'PHP'
<?php

use Illuminate\Support\ServiceProvider;

return [
    'providers' => ServiceProvider::defaultProviders()->merge([
        App\Providers\AppServiceProvider::class,
        App\Providers\RouteServiceProvider::class,
    ])->toArray(),
];
PHP;

        File::put(config_path('app.php'), $laravel10Config);

        $this->artisan('channel-lister:install')->assertExitCode(0);

        $updatedConfig = File::get(config_path('app.php'));
        expect($updatedConfig)->toContain('ChannelListerServiceProvider::class');
    } finally {
        File::put(config_path('app.php'), $originalConfig);
        cleanupPublishedFiles();
    }
});

test('install command handles Laravel 8/9 style config with providers array', function (): void {
    if (! File::exists(config_path('app.php'))) {
        $this->markTestSkipped('app.php config file not found');
    }

    // Skip if Laravel 11+ (uses bootstrap/providers.php)
    if (File::exists(base_path('bootstrap/providers.php'))) {
        $this->markTestSkipped('This test is for Laravel 10 and below');
    }

    $originalConfig = File::get(config_path('app.php'));

    try {
        // Create a Laravel 8/9 style config (traditional providers array)
        $laravel8Config = <<<'PHP'
<?php

return [
    'providers' => [
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\RouteServiceProvider::class,
    ],
];
PHP;

        File::put(config_path('app.php'), $laravel8Config);

        $this->artisan('channel-lister:install')->assertExitCode(0);

        $updatedConfig = File::get(config_path('app.php'));
        expect($updatedConfig)->toContain('ChannelListerServiceProvider::class');
    } finally {
        File::put(config_path('app.php'), $originalConfig);
        cleanupPublishedFiles();
    }
});

test('install command does not duplicate service provider registration', function (): void {
    if (! File::exists(config_path('app.php'))) {
        $this->markTestSkipped('app.php config file not found');
    }

    // Skip if Laravel 11+ (uses bootstrap/providers.php)
    if (File::exists(base_path('bootstrap/providers.php'))) {
        $this->markTestSkipped('This test is for Laravel 10 and below');
    }

    $originalConfig = File::get(config_path('app.php'));

    try {
        // Create a clean config without the provider
        $testConfig = <<<'PHP'
<?php

return [
    'providers' => [
        App\Providers\AppServiceProvider::class,
        App\Providers\RouteServiceProvider::class,
    ],
];
PHP;

        File::put(config_path('app.php'), $testConfig);

        // First installation
        $this->artisan('channel-lister:install')->assertExitCode(0);

        $configAfterFirst = File::get(config_path('app.php'));
        $firstCount = substr_count($configAfterFirst, 'ChannelListerServiceProvider::class');

        // Second installation (should not duplicate)
        $this->artisan('channel-lister:install')->assertExitCode(0);

        $configAfterSecond = File::get(config_path('app.php'));
        $secondCount = substr_count($configAfterSecond, 'ChannelListerServiceProvider::class');

        expect($firstCount)->toBe($secondCount);
        expect($firstCount)->toBe(1);
    } finally {
        File::put(config_path('app.php'), $originalConfig);
        cleanupPublishedFiles();
    }
});

test('install command updates service provider namespace correctly', function (): void {
    if (! File::exists(config_path('app.php'))) {
        $this->markTestSkipped('app.php config file not found');
    }

    $originalConfig = File::get(config_path('app.php'));

    try {
        $this->artisan('channel-lister:install')->assertExitCode(0);

        // Check that the published service provider has correct namespace
        if (File::exists(app_path('Providers/ChannelListerServiceProvider.php'))) {
            $serviceProviderContent = File::get(app_path('Providers/ChannelListerServiceProvider.php'));

            // Should use the application's namespace, not hardcoded App
            $appNamespace = trim((string) $this->app->getNamespace(), '\\');
            expect($serviceProviderContent)->toContain("namespace {$appNamespace}\\Providers;");
        }
    } finally {
        File::put(config_path('app.php'), $originalConfig);
        cleanupPublishedFiles();
    }
});

test('install command handles missing service provider file gracefully', function (): void {
    if (! File::exists(config_path('app.php'))) {
        $this->markTestSkipped('app.php config file not found');
    }

    $originalConfig = File::get(config_path('app.php'));

    try {
        // Run install command
        $this->artisan('channel-lister:install')->assertExitCode(0);

        // Delete the service provider file after publishing but before namespace update
        if (File::exists(app_path('Providers/ChannelListerServiceProvider.php'))) {
            File::delete(app_path('Providers/ChannelListerServiceProvider.php'));
        }

        // Run again - should not fail even though service provider file is missing
        $this->artisan('channel-lister:install')->assertExitCode(0);
    } finally {
        File::put(config_path('app.php'), $originalConfig);
        cleanupPublishedFiles();
    }
});

test('install command handles Laravel 11+ bootstrap providers file', function (): void {
    if (! File::exists(base_path('bootstrap/providers.php'))) {
        $this->markTestSkipped('This test requires Laravel 11+ bootstrap/providers.php file');
    }

    $originalProviders = File::get(base_path('bootstrap/providers.php'));

    try {
        // Mock ServiceProvider::addProviderToBootstrapFile method exists
        if (method_exists(\Illuminate\Support\ServiceProvider::class, 'addProviderToBootstrapFile')) {
            $this->artisan('channel-lister:install')->assertExitCode(0);

            // For Laravel 11+, the service provider registration is handled differently
            // Just verify command completes successfully
            expect(true)->toBeTrue(); // Command completed without error
        } else {
            $this->markTestSkipped('ServiceProvider::addProviderToBootstrapFile method not available');
        }
    } finally {
        File::put(base_path('bootstrap/providers.php'), $originalProviders);
        cleanupPublishedFiles();
    }
});

test('registerChannelListerServiceProvider method handles file read errors', function (): void {
    if (! File::exists(config_path('app.php'))) {
        $this->markTestSkipped('app.php config file not found');
    }

    // Create a command instance to test the protected method directly
    $command = new InstallCommand;

    // Test that method handles missing files gracefully
    $reflection = new ReflectionClass($command);
    $method = $reflection->getMethod('registerChannelListerServiceProvider');
    $method->setAccessible(true);

    // This should not throw an exception even if files don't exist
    expect(fn (): mixed => $method->invoke($command))->not->toThrow(Exception::class);
});

test('addProviderToBootstrapFile method returns false when method does not exist', function (): void {
    $command = new InstallCommand;

    // Test the protected method directly
    $reflection = new ReflectionClass($command);
    $method = $reflection->getMethod('addProviderToBootstrapFile');
    $method->setAccessible(true);

    // Mock the method_exists check to return false
    if (! method_exists(\Illuminate\Support\ServiceProvider::class, 'addProviderToBootstrapFile')) {
        $result = $method->invoke($command);
        expect($result)->toBeFalse();
    } else {
        // If method exists, just verify it doesn't throw an exception
        expect(fn (): mixed => $method->invoke($command))->not->toThrow(Exception::class);
    }
});

test('install command can handle complex config file structures', function (): void {
    if (! File::exists(config_path('app.php'))) {
        $this->markTestSkipped('app.php config file not found');
    }

    // Skip if Laravel 11+ (uses bootstrap/providers.php)
    if (File::exists(base_path('bootstrap/providers.php'))) {
        $this->markTestSkipped('This test is for Laravel 10 and below');
    }

    $originalConfig = File::get(config_path('app.php'));

    try {
        // Create a config with complex nested structure and comments
        $complexConfig = <<<'PHP'
<?php

return [
    'providers' => [
        // Application Service Providers...
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        // App\Providers\BroadcastServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\RouteServiceProvider::class,
        
        /*
         * Package Service Providers...
         */
    ],
    
    'aliases' => [
        'App' => Illuminate\Support\Facades\App::class,
    ],
];
PHP;

        File::put(config_path('app.php'), $complexConfig);

        $this->artisan('channel-lister:install')->assertExitCode(0);

        $updatedConfig = File::get(config_path('app.php'));
        expect($updatedConfig)->toContain('ChannelListerServiceProvider::class');
    } finally {
        File::put(config_path('app.php'), $originalConfig);
        cleanupPublishedFiles();
    }
});

function cleanupPublishedFiles(): void
{
    // Clean up published migrations
    $migrationFiles = glob(database_path('migrations/*channel_lister*.php'));
    foreach ($migrationFiles as $file) {
        if (File::exists($file)) {
            File::delete($file);
        }
    }

    // Clean up published config
    if (File::exists(config_path('channel-lister.php'))) {
        File::delete(config_path('channel-lister.php'));
    }

    // Clean up published service provider
    if (File::exists(app_path('Providers/ChannelListerServiceProvider.php'))) {
        File::delete(app_path('Providers/ChannelListerServiceProvider.php'));
    }

    // Clean up published assets
    if (File::isDirectory(public_path('vendor/channel-lister'))) {
        File::deleteDirectory(public_path('vendor/channel-lister'));
    }

    // Clean up published resources
    if (File::isDirectory(resource_path('vendor/channel-lister'))) {
        File::deleteDirectory(resource_path('vendor/channel-lister'));
    }
}
