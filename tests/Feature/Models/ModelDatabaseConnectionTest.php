<?php

use IGE\ChannelLister\Models\AmazonListing;
use IGE\ChannelLister\Models\ChannelListerField;
use IGE\ChannelLister\Models\ProductDraft;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    // Ensure migrations are run
    $this->artisan('migrate');

    // Clear configuration
    config(['channel-lister.database.connection' => null]);
});

describe('Model database connection configuration', function (): void {
    describe('ChannelListerField model connection handling', function (): void {
        it('uses default connection when none configured', function (): void {
            config(['channel-lister.database.connection' => null]);

            $model = new ChannelListerField;
            expect($model->getConnectionName())->toBeNull();
        });

        it('uses configured database connection', function (): void {
            config(['channel-lister.database.connection' => 'test_connection']);

            $model = new ChannelListerField;
            expect($model->getConnectionName())->toBe('test_connection');
        });

        it('queries use configured connection', function (): void {
            // Set up test connection
            config([
                'database.connections.model_test_connection' => [
                    'driver' => 'sqlite',
                    'database' => ':memory:',
                    'prefix' => '',
                ],
                'channel-lister.database.connection' => 'model_test_connection',
            ]);

            $this->artisan('migrate', ['--database' => 'model_test_connection']);

            // Insert test data directly into configured connection
            DB::connection('model_test_connection')->table('channel_lister_fields')->insert([
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

            // Model should find the data on configured connection
            $field = ChannelListerField::first();
            expect($field)->not->toBeNull()
                ->and($field->field_name)->toBe('Test Field');

            // Should not find data on default connection
            expect(DB::connection()->table('channel_lister_fields')->count())->toBe(0);
        });
    });

    describe('All models consistent connection handling', function (): void {
        it('all models respect configured connection', function (): void {
            config(['channel-lister.database.connection' => 'test_configured']);

            $models = [
                new ChannelListerField,
                new AmazonListing,
                new ProductDraft,
            ];

            foreach ($models as $model) {
                expect($model->getConnectionName())->toBe('test_configured');
            }
        });

        it('all models fall back to default when no config', function (): void {
            config(['channel-lister.database.connection' => null]);

            $models = [
                new ChannelListerField,
                new AmazonListing,
                new ProductDraft,
            ];

            foreach ($models as $model) {
                expect($model->getConnectionName())->toBeNull();
            }
        });
    });

    describe('Model operations with configured connections', function (): void {
        it('can create and retrieve models on configured connection', function (): void {
            config([
                'database.connections.full_test_connection' => [
                    'driver' => 'sqlite',
                    'database' => ':memory:',
                    'prefix' => '',
                ],
                'channel-lister.database.connection' => 'full_test_connection',
            ]);

            $this->artisan('migrate', ['--database' => 'full_test_connection']);

            // Create model using Eloquent
            $field = ChannelListerField::create([
                'ordering' => 1,
                'field_name' => 'Test Field',
                'display_name' => 'Test',
                'marketplace' => 'test',
                'input_type' => 'text',
                'required' => false,
                'grouping' => 'Test',
                'type' => 'custom',
            ]);

            expect($field->id)->not->toBeNull();

            // Retrieve using Eloquent
            $retrieved = ChannelListerField::find($field->id);
            expect($retrieved)->not->toBeNull()
                ->and($retrieved->field_name)->toBe('Test Field');

            // Verify data is in configured connection
            expect(DB::connection('full_test_connection')->table('channel_lister_fields')->count())->toBe(1);
            expect(DB::connection()->table('channel_lister_fields')->count())->toBe(0);
        });

        it('model relationships work with configured connections', function (): void {
            config([
                'database.connections.relationship_test' => [
                    'driver' => 'sqlite',
                    'database' => ':memory:',
                    'prefix' => '',
                ],
                'channel-lister.database.connection' => 'relationship_test',
            ]);

            $this->artisan('migrate', ['--database' => 'relationship_test']);

            // Create related models
            $draft = ProductDraft::create([
                'form_data' => ['test' => 'data'],
                'status' => 'draft',
                'title' => 'Test Product',
                'sku' => 'TEST-001',
            ]);

            expect($draft->id)->not->toBeNull();

            // Query should work on configured connection
            $found = ProductDraft::where('sku', 'TEST-001')->first();
            expect($found)->not->toBeNull()
                ->and($found->title)->toBe('Test Product');
        });
    });

    describe('Configuration changes affect all models', function (): void {
        it('runtime configuration changes apply to all models', function (): void {
            // Start with no configuration
            config(['channel-lister.database.connection' => null]);

            $field = new ChannelListerField;
            $listing = new AmazonListing;

            expect($field->getConnectionName())->toBeNull()
                ->and($listing->getConnectionName())->toBeNull();

            // Change configuration at runtime
            config(['channel-lister.database.connection' => 'runtime_changed']);

            // New instances should use new configuration
            $newField = new ChannelListerField;
            $newListing = new AmazonListing;

            expect($newField->getConnectionName())->toBe('runtime_changed')
                ->and($newListing->getConnectionName())->toBe('runtime_changed');
        });
    });
});
