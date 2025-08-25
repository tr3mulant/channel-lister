<?php

use IGE\ChannelLister\Console\SeedFieldsCommand;
use IGE\ChannelLister\Data\DefaultFieldDefinitions;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    // Ensure migrations are run for tests
    $this->artisan('migrate');

    // Clear any existing field data
    DB::table('channel_lister_fields')->truncate();

    // Reset configuration
    config(['channel-lister.database.connection' => null]);
});

describe('SeedFieldsCommand registration and basic functionality', function (): void {
    it('is registered in the application', function (): void {
        expect(
            collect($this->app[Kernel::class]->all())
                ->has('channel-lister:seed-fields')
        )->toBeTrue();
    });

    it('has correct signature and description', function (): void {
        $command = new SeedFieldsCommand;

        expect($command->getName())->toBe('channel-lister:seed-fields')
            ->and($command->getDescription())->toBe('Seed the database with default Channel Lister field definitions');
    });

    it('accepts force option', function (): void {
        $command = new SeedFieldsCommand;
        $definition = $command->getDefinition();

        expect($definition->hasOption('force'))->toBeTrue();
    });
});

describe('SeedFieldsCommand database operations', function (): void {
    describe('basic seeding functionality', function (): void {
        it('seeds fields when database is empty', function (): void {
            expect(DB::table('channel_lister_fields')->count())->toBe(0);

            $this->artisan('channel-lister:seed-fields')
                ->expectsOutput('Seeding Channel Lister fields...')
                ->expectsOutput('Successfully seeded 27 Channel Lister fields.')
                ->assertExitCode(0);

            expect(DB::table('channel_lister_fields')->count())->toBe(27);
        });

        it('refuses to seed when fields already exist', function (): void {
            // Seed initial data
            $this->artisan('channel-lister:seed-fields');
            expect(DB::table('channel_lister_fields')->count())->toBe(27);

            // Try to seed again
            $this->artisan('channel-lister:seed-fields')
                ->expectsOutput('Channel Lister fields already exist. Use --force to reseed.')
                ->assertExitCode(0);

            // Should still have 27 fields
            expect(DB::table('channel_lister_fields')->count())->toBe(27);
        });

        it('reseeds when force flag is used', function (): void {
            // Seed initial data
            $this->artisan('channel-lister:seed-fields');
            expect(DB::table('channel_lister_fields')->count())->toBe(27);

            // Add a custom field to verify truncation
            DB::table('channel_lister_fields')->insert([
                'ordering' => 999,
                'field_name' => 'Custom Field',
                'display_name' => 'Custom',
                'marketplace' => 'test',
                'input_type' => 'text',
                'required' => 0,
                'grouping' => 'Test',
                'type' => 'test',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            expect(DB::table('channel_lister_fields')->count())->toBe(28);

            // Force reseed
            $this->artisan('channel-lister:seed-fields', ['--force' => true])
                ->expectsOutput('Force flag detected. Clearing existing fields...')
                ->expectsOutput('Seeding Channel Lister fields...')
                ->expectsOutput('Successfully seeded 27 Channel Lister fields.')
                ->assertExitCode(0);

            // Should have exactly 27 fields (custom field removed)
            expect(DB::table('channel_lister_fields')->count())->toBe(27);
            expect(DB::table('channel_lister_fields')->where('field_name', 'Custom Field')->exists())->toBeFalse();
        });
    });

    describe('seeded data validation', function (): void {
        it('seeds correct field data', function (): void {
            $this->artisan('channel-lister:seed-fields');

            // Check first field (Auction Title)
            $firstField = DB::table('channel_lister_fields')
                ->where('ordering', 1)
                ->first();

            expect($firstField->field_name)->toBe('Auction Title')
                ->and($firstField->display_name)->toBe('Title')
                ->and($firstField->marketplace)->toBe('common')
                ->and($firstField->input_type)->toBe('text')
                ->and($firstField->required)->toBe(1);

            // Check last field (Amazon Product Type)
            $lastField = DB::table('channel_lister_fields')
                ->where('ordering', 27)
                ->first();

            expect($lastField->field_name)->toBe('amazon_product_type')
                ->and($lastField->display_name)->toBe('Amazon Product Type')
                ->and($lastField->marketplace)->toBe('amazon')
                ->and($lastField->input_type)->toBe('custom')
                ->and($lastField->required)->toBe(0);
        });

        it('maintains data consistency with DefaultFieldDefinitions', function (): void {
            $this->artisan('channel-lister:seed-fields');

            $dbFields = DB::table('channel_lister_fields')
                ->orderBy('ordering')
                ->get()
                ->toArray();

            $definitionFields = DefaultFieldDefinitions::getFields();

            expect($dbFields)->toHaveCount(count($definitionFields));
            $counter = count($dbFields);

            for ($i = 0; $i < $counter; $i++) {
                $dbField = (array) $dbFields[$i];
                $defField = $definitionFields[$i];

                expect($dbField['field_name'])->toBe($defField['field_name'])
                    ->and($dbField['ordering'])->toBe($defField['ordering'])
                    ->and($dbField['marketplace'])->toBe($defField['marketplace']);
            }
        });
    });
});

describe('SeedFieldsCommand database connection handling', function (): void {
    it('uses default connection when none configured', function (): void {
        config(['channel-lister.database.connection' => null]);

        $this->artisan('channel-lister:seed-fields');

        // Verify data was inserted into default connection
        expect(DB::table('channel_lister_fields')->count())->toBe(27);
    });

    it('uses configured database connection', function (): void {
        // Set up a separate test connection
        config([
            'database.connections.test_seed_connection' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ],
            'channel-lister.database.connection' => 'test_seed_connection',
        ]);

        // Run migrations on the test connection
        $this->artisan('migrate', ['--database' => 'test_seed_connection']);

        // Seed should use the configured connection
        $this->artisan('channel-lister:seed-fields');

        // Verify data is in the configured connection
        expect(DB::connection('test_seed_connection')->table('channel_lister_fields')->count())->toBe(27);

        // Verify data is NOT in the default connection
        expect(DB::connection()->table('channel_lister_fields')->count())->toBe(0);
    });

    it('handles force flag with configured connection', function (): void {
        // Set up configured connection
        config([
            'database.connections.test_force_connection' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ],
            'channel-lister.database.connection' => 'test_force_connection',
        ]);

        $this->artisan('migrate', ['--database' => 'test_force_connection']);

        // Initial seed
        $this->artisan('channel-lister:seed-fields');
        expect(DB::connection('test_force_connection')->table('channel_lister_fields')->count())->toBe(27);

        // Force reseed
        $this->artisan('channel-lister:seed-fields', ['--force' => true])
            ->expectsOutput('Force flag detected. Clearing existing fields...')
            ->assertExitCode(0);

        expect(DB::connection('test_force_connection')->table('channel_lister_fields')->count())->toBe(27);
    });

    it('checks field existence on configured connection', function (): void {
        config([
            'database.connections.test_check_connection' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ],
            'channel-lister.database.connection' => 'test_check_connection',
        ]);

        $this->artisan('migrate', ['--database' => 'test_check_connection']);

        // Manually insert some test data
        DB::connection('test_check_connection')->table('channel_lister_fields')->insert([
            'ordering' => 1,
            'field_name' => 'Test Field',
            'display_name' => 'Test',
            'marketplace' => 'test',
            'input_type' => 'text',
            'required' => 0,
            'grouping' => 'Test',
            'type' => 'test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Command should detect existing fields on configured connection
        $this->artisan('channel-lister:seed-fields')
            ->expectsOutput('Channel Lister fields already exist. Use --force to reseed.');
    });
});

describe('SeedFieldsCommand error handling', function (): void {
    it('handles database errors gracefully', function (): void {
        // Drop the table to simulate database error
        DB::statement('DROP TABLE channel_lister_fields');

        // Command should fail gracefully
        $this->artisan('channel-lister:seed-fields')
            ->assertExitCode(1);
    });
});
