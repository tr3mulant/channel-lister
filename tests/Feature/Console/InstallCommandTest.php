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

test('install command registers service provider in app config', function (): void {
    // Skip this test in CI or if app.php doesn't exist
    if (! File::exists(config_path('app.php'))) {
        $this->markTestSkipped('app.php config file not found');
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

        // Verify service provider was registered
        $updatedConfig = File::get(config_path('app.php'));
        expect($updatedConfig)->toContain('App\\Providers\\ChannelListerServiceProvider::class');
    } finally {
        // Always restore original config
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
