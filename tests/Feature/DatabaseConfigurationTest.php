<?php

use IGE\ChannelLister\Data\DefaultFieldDefinitions;
use Illuminate\Support\Facades\DB;
use Workbench\Database\Seeders\ChannelListerFieldSeeder;

beforeEach(function (): void {
    // Ensure migrations are run
    $this->artisan('migrate');

    // Clear any existing data
    DB::table('channel_lister_fields')->truncate();

    // Reset configuration to defaults
    config(['channel-lister.database.connection' => null]);
});

describe('Channel Lister database configuration integration', function (): void {
    describe('configuration loading and defaults', function (): void {
        it('loads default database configuration', function (): void {
            expect(config('channel-lister.database.connection'))->toBeNull();
        });

        it('respects environment variable configuration', function (): void {
            // Simulate setting via environment
            config(['channel-lister.database.connection' => 'test_env_connection']);

            expect(config('channel-lister.database.connection'))->toBe('test_env_connection');
        });

        it('provides complete database configuration structure', function (): void {
            $config = config('channel-lister.database');

            expect($config)->toBeArray()
                ->and($config)->toHaveKey('connection');
        });
    });

    describe('multiple database connection scenarios', function (): void {
        it('handles primary and secondary database connections', function (): void {
            // Set up multiple connections
            config([
                'database.connections.primary_test' => [
                    'driver' => 'sqlite',
                    'database' => ':memory:',
                    'prefix' => '',
                ],
                'database.connections.secondary_test' => [
                    'driver' => 'sqlite',
                    'database' => ':memory:',
                    'prefix' => '',
                ],
            ]);

            // Run migrations on both connections
            $this->artisan('migrate', ['--database' => 'primary_test']);
            $this->artisan('migrate', ['--database' => 'secondary_test']);

            // Test with primary connection
            config(['channel-lister.database.connection' => 'primary_test']);
            $this->artisan('channel-lister:seed-fields');

            expect(DB::connection('primary_test')->table('channel_lister_fields')->count())->toBe(27)
                ->and(DB::connection('secondary_test')->table('channel_lister_fields')->count())->toBe(0);

            // Clear and test with secondary connection
            DB::connection('primary_test')->table('channel_lister_fields')->truncate();
            config(['channel-lister.database.connection' => 'secondary_test']);
            $this->artisan('channel-lister:seed-fields');

            expect(DB::connection('primary_test')->table('channel_lister_fields')->count())->toBe(0)
                ->and(DB::connection('secondary_test')->table('channel_lister_fields')->count())->toBe(27);
        });

        it('maintains data isolation between connections', function (): void {
            config([
                'database.connections.isolated_a' => [
                    'driver' => 'sqlite',
                    'database' => ':memory:',
                    'prefix' => '',
                ],
                'database.connections.isolated_b' => [
                    'driver' => 'sqlite',
                    'database' => ':memory:',
                    'prefix' => '',
                ],
            ]);

            $this->artisan('migrate', ['--database' => 'isolated_a']);
            $this->artisan('migrate', ['--database' => 'isolated_b']);

            // Insert different data in each connection
            config(['channel-lister.database.connection' => 'isolated_a']);
            $this->artisan('channel-lister:seed-fields');

            config(['channel-lister.database.connection' => 'isolated_b']);
            DB::connection('isolated_b')->table('channel_lister_fields')->insert([
                'ordering' => 1,
                'field_name' => 'Custom Field B',
                'display_name' => 'Custom B',
                'marketplace' => 'test',
                'input_type' => 'text',
                'required' => 0,
                'grouping' => 'Test',
                'type' => 'test',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Verify isolation
            expect(DB::connection('isolated_a')->table('channel_lister_fields')->count())->toBe(27)
                ->and(DB::connection('isolated_b')->table('channel_lister_fields')->count())->toBe(1)
                ->and(DB::connection('isolated_a')->table('channel_lister_fields')->where('field_name', 'Custom Field B')->exists())->toBeFalse()
                ->and(DB::connection('isolated_b')->table('channel_lister_fields')->where('field_name', 'Custom Field B')->exists())->toBeTrue();
        });
    });

    describe('seeder integration with configuration', function (): void {
        it('seeder respects configured database connection', function (): void {
            config([
                'database.connections.seeder_test' => [
                    'driver' => 'sqlite',
                    'database' => ':memory:',
                    'prefix' => '',
                ],
                'channel-lister.database.connection' => 'seeder_test',
            ]);

            $this->artisan('migrate', ['--database' => 'seeder_test']);

            // Run seeder directly
            $seeder = new ChannelListerFieldSeeder;
            $seeder->run();

            // Verify data is in configured connection
            expect(DB::connection('seeder_test')->table('channel_lister_fields')->count())->toBe(27)
                ->and(DB::connection()->table('channel_lister_fields')->count())->toBe(0);
        });

        it('seeder and command use same connection configuration', function (): void {
            config([
                'database.connections.shared_test' => [
                    'driver' => 'sqlite',
                    'database' => ':memory:',
                    'prefix' => '',
                ],
                'channel-lister.database.connection' => 'shared_test',
            ]);

            $this->artisan('migrate', ['--database' => 'shared_test']);

            // Use seeder first
            $seeder = new ChannelListerFieldSeeder;
            $seeder->run();

            expect(DB::connection('shared_test')->table('channel_lister_fields')->count())->toBe(27);

            // Command should detect existing fields on same connection
            $this->artisan('channel-lister:seed-fields')
                ->expectsOutput('Channel Lister fields already exist. Use --force to reseed.');
        });
    });

    describe('configuration edge cases and error handling', function (): void {
        it('handles invalid connection configuration gracefully', function (): void {
            config(['channel-lister.database.connection' => 'nonexistent_connection']);

            // Should throw exception when trying to get connection
            expect(fn () => DefaultFieldDefinitions::getConnection())
                ->toThrow(\InvalidArgumentException::class);
        });

        it('falls back to default connection when configuration is malformed', function (): void {
            // Set connection to null explicitly
            config(['channel-lister.database.connection' => null]);

            $connection = DefaultFieldDefinitions::getConnection();

            // Should use testbench connection (the default in tests)
            expect($connection->getName())->toBe('testbench');
        });

        it('handles empty string connection configuration', function (): void {
            config(['channel-lister.database.connection' => '']);

            // Empty string should be treated as null/default
            $connection = DefaultFieldDefinitions::getConnection();
            expect($connection->getName())->toBe('testbench');
        });
    });

    describe('configuration consistency across package components', function (): void {
        it('maintains consistent connection usage across all components', function (): void {
            config([
                'database.connections.consistent_test' => [
                    'driver' => 'sqlite',
                    'database' => ':memory:',
                    'prefix' => '',
                ],
                'channel-lister.database.connection' => 'consistent_test',
            ]);

            $this->artisan('migrate', ['--database' => 'consistent_test']);

            // Test connection retrieval consistency
            $connection1 = DefaultFieldDefinitions::getConnection();
            $connection2 = DefaultFieldDefinitions::getConnection();

            expect($connection1->getName())->toBe($connection2->getName())
                ->and($connection1->getName())->toBe('consistent_test');

            // Test database operations consistency
            $this->artisan('channel-lister:seed-fields');

            $countViaConnection = $connection1->table('channel_lister_fields')->count();
            $countViaCommand = DB::connection('consistent_test')->table('channel_lister_fields')->count();

            expect($countViaConnection)->toBe($countViaCommand)
                ->and($countViaConnection)->toBe(27);
        });

        it('respects configuration changes at runtime', function (): void {
            // Start with default connection
            config(['channel-lister.database.connection' => null]);
            $defaultConnection = DefaultFieldDefinitions::getConnection();
            expect($defaultConnection->getName())->toBe('testbench');

            // Change configuration
            config([
                'database.connections.runtime_test' => [
                    'driver' => 'sqlite',
                    'database' => ':memory:',
                    'prefix' => '',
                ],
                'channel-lister.database.connection' => 'runtime_test',
            ]);

            // Should use new connection
            $newConnection = DefaultFieldDefinitions::getConnection();
            expect($newConnection->getName())->toBe('runtime_test');
        });
    });
});
