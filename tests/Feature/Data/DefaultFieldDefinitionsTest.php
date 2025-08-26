<?php

use IGE\ChannelLister\Data\DefaultFieldDefinitions;

beforeEach(function (): void {
    // Reset config before each test
    config(['channel-lister.database.connection' => null]);
});

describe('DefaultFieldDefinitions database connection handling', function (): void {
    describe('getDatabaseConnection', function (): void {
        it('returns null when no connection is configured', function (): void {
            config(['channel-lister.database.connection' => null]);

            expect(DefaultFieldDefinitions::getDatabaseConnection())->toBeNull();
        });

        it('returns configured connection name', function (): void {
            config(['channel-lister.database.connection' => 'mysql']);

            expect(DefaultFieldDefinitions::getDatabaseConnection())->toBe('mysql');
        });

        it('returns connection from environment variable', function (): void {
            // Simulate environment variable being set
            config(['channel-lister.database.connection' => 'postgres']);

            expect(DefaultFieldDefinitions::getDatabaseConnection())->toBe('postgres');
        });
    });

    describe('getConnection', function (): void {
        it('returns default connection when no specific connection configured', function (): void {
            config(['channel-lister.database.connection' => null]);
            config(['database.default' => 'testbench']);

            $connection = DefaultFieldDefinitions::getConnection();

            expect($connection->getDatabaseName())->toBe(':memory:');
        });

        it('returns specific connection when configured', function (): void {
            // Set up a test connection
            config([
                'database.connections.test_connection' => [
                    'driver' => 'sqlite',
                    'database' => ':memory:',
                    'prefix' => '',
                ],
                'channel-lister.database.connection' => 'test_connection',
            ]);

            $connection = DefaultFieldDefinitions::getConnection();

            expect($connection->getName())->toBe('test_connection');
        });

        it('gracefully handles invalid connection names', function (): void {
            config(['channel-lister.database.connection' => 'nonexistent_connection']);

            // Should throw exception for invalid connection
            expect(fn () => DefaultFieldDefinitions::getConnection())
                ->toThrow(\InvalidArgumentException::class);
        });
    });

    describe('getFields', function (): void {
        it('returns array of field definitions', function (): void {
            $fields = DefaultFieldDefinitions::getFields();

            expect($fields)->toBeArray()
                ->and($fields)->toHaveCount(27)
                ->and($fields[0])->toHaveKeys([
                    'ordering', 'field_name', 'display_name', 'tooltip',
                    'example', 'marketplace', 'input_type', 'input_type_aux',
                    'required', 'grouping', 'type', 'created_at', 'updated_at',
                ]);
        });

        it('includes proper field data structure', function (): void {
            $fields = DefaultFieldDefinitions::getFields();

            // Test first field (Auction Title)
            expect($fields[0]['field_name'])->toBe('Auction Title')
                ->and($fields[0]['display_name'])->toBe('Title')
                ->and($fields[0]['ordering'])->toBe(1)
                ->and($fields[0]['required'])->toBe(1);

            // Test last field (Amazon Product Type)
            expect($fields[26]['field_name'])->toBe('amazon_product_type')
                ->and($fields[26]['display_name'])->toBe('Amazon Product Type')
                ->and($fields[26]['ordering'])->toBe(27)
                ->and($fields[26]['marketplace'])->toBe('amazon');
        });

        it('has consistent timestamp format', function (): void {
            $fields = DefaultFieldDefinitions::getFields();

            foreach ($fields as $field) {
                // Verify timestamp format (Y-m-d H:i:s)
                expect($field['created_at'])->toMatch('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/')
                    ->and($field['updated_at'])->toMatch('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/');
            }
        });

        it('has unique ordering values', function (): void {
            $fields = DefaultFieldDefinitions::getFields();
            $orderings = array_column($fields, 'ordering');

            expect($orderings)->toHaveCount(count(array_unique($orderings)))
                ->and(min($orderings))->toBe(1)
                ->and(max($orderings))->toBe(27);
        });

        it('includes all required field types', function (): void {
            $fields = DefaultFieldDefinitions::getFields();
            $inputTypes = array_unique(array_column($fields, 'input_type'));

            expect($inputTypes)->toContain('text', 'select', 'custom', 'currency', 'decimal', 'integer', 'textarea');
        });

        it('includes both common and marketplace-specific fields', function (): void {
            $fields = DefaultFieldDefinitions::getFields();
            $marketplaces = array_unique(array_column($fields, 'marketplace'));

            expect($marketplaces)->toContain('common', 'amazon');
        });
    });
});

describe('DefaultFieldDefinitions data integrity', function (): void {
    it('maintains consistent data between calls', function (): void {
        $fields1 = DefaultFieldDefinitions::getFields();
        $fields2 = DefaultFieldDefinitions::getFields();

        expect($fields1)->toHaveCount(count($fields2))
            ->and($fields1[0]['field_name'])->toBe($fields2[0]['field_name'])
            ->and($fields1[26]['field_name'])->toBe($fields2[26]['field_name']);
    });

    it('provides database-ready field data', function (): void {
        $fields = DefaultFieldDefinitions::getFields();

        foreach ($fields as $field) {
            // All required database columns should be present
            expect($field)->toHaveKeys([
                'ordering', 'field_name', 'display_name', 'marketplace',
                'input_type', 'required', 'grouping', 'type',
                'created_at', 'updated_at',
            ]);

            // Field names should not be empty
            expect($field['field_name'])->not->toBeEmpty();

            // Ordering should be positive integer
            expect($field['ordering'])->toBeGreaterThan(0);

            // Required should be 0 or 1
            expect($field['required'])->toBeIn([0, 1]);
        }
    });
});
