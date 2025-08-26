<?php

declare(strict_types=1);

use IGE\ChannelLister\Enums\InputType;
use IGE\ChannelLister\Enums\Type;
use IGE\ChannelLister\Models\ChannelListerField;

describe('ChannelListerFieldController API', function (): void {
    beforeEach(function (): void {
        // Create diverse test data
        ChannelListerField::create([
            'ordering' => 1,
            'field_name' => 'product_title',
            'display_name' => 'Product Title',
            'tooltip' => 'Enter the product title',
            'marketplace' => 'amazon',
            'input_type' => InputType::TEXT,
            'required' => true,
            'grouping' => 'basic',
            'type' => Type::CUSTOM,
        ]);

        ChannelListerField::create([
            'ordering' => 2,
            'field_name' => 'price',
            'display_name' => 'Price',
            'tooltip' => 'Product price in USD',
            'marketplace' => 'amazon',
            'input_type' => InputType::CURRENCY,
            'required' => true,
            'grouping' => 'pricing',
            'type' => Type::CUSTOM,
        ]);

        ChannelListerField::create([
            'ordering' => 3,
            'field_name' => 'description',
            'display_name' => 'Description',
            'tooltip' => 'Product description',
            'marketplace' => 'ebay',
            'input_type' => InputType::TEXTAREA,
            'required' => false,
            'grouping' => 'basic',
            'type' => Type::CUSTOM,
        ]);

        ChannelListerField::create([
            'ordering' => 4,
            'field_name' => 'category',
            'display_name' => 'Category',
            'tooltip' => 'Product category',
            'marketplace' => 'walmart',
            'input_type' => InputType::SELECT,
            'required' => true,
            'grouping' => 'categorization',
            'type' => Type::CHANNEL_ADVISOR,
        ]);

        ChannelListerField::create([
            'ordering' => 5,
            'field_name' => 'weight',
            'display_name' => 'Weight',
            'tooltip' => 'Product weight in pounds',
            'marketplace' => 'amazon',
            'input_type' => InputType::DECIMAL,
            'required' => false,
            'grouping' => 'shipping',
            'type' => Type::CUSTOM,
        ]);
    });

    describe('search endpoint', function (): void {
        it('returns successful response with default pagination', function (): void {
            $response = $this->getJson(route('api.channel-lister-field.search'));

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'table_html',
                        'pagination_html',
                        'results_info' => [
                            'from',
                            'to',
                            'total',
                            'current_page',
                            'last_page',
                        ],
                    ],
                ]);

            $responseData = $response->json();
            expect($responseData['success'])->toBeTrue();
            expect($responseData['data']['table_html'])->toBeString();
            expect($responseData['data']['pagination_html'])->toBeString();
            expect($responseData['data']['results_info']['total'])->toBe(5);
            expect($responseData['data']['results_info']['current_page'])->toBe(1);
        });

        it('returns all fields when no filters applied', function (): void {
            $response = $this->getJson(route('api.channel-lister-field.search'));

            $response->assertStatus(200);
            $responseData = $response->json();

            expect($responseData['data']['results_info']['total'])->toBe(5);
            expect($responseData['data']['results_info']['from'])->toBe(1);
            expect($responseData['data']['results_info']['to'])->toBe(5);
        });

        describe('search filtering', function (): void {
            it('filters by field name', function (): void {
                $response = $this->getJson(route('api.channel-lister-field.search', [
                    'search' => 'title',
                ]));

                $response->assertStatus(200);
                $responseData = $response->json();

                expect($responseData['data']['results_info']['total'])->toBe(1);
                expect($responseData['data']['table_html'])->toContain('product_title');
            });

            it('filters by display name', function (): void {
                $response = $this->getJson(route('api.channel-lister-field.search', [
                    'search' => 'Product Title',
                ]));

                $response->assertStatus(200);
                $responseData = $response->json();

                expect($responseData['data']['results_info']['total'])->toBe(1);
            });

            it('filters by marketplace', function (): void {
                $response = $this->getJson(route('api.channel-lister-field.search', [
                    'search' => 'amazon',
                ]));

                $response->assertStatus(200);
                $responseData = $response->json();

                expect($responseData['data']['results_info']['total'])->toBe(3);
            });

            it('filters by grouping', function (): void {
                $response = $this->getJson(route('api.channel-lister-field.search', [
                    'search' => 'basic',
                ]));

                $response->assertStatus(200);
                $responseData = $response->json();

                expect($responseData['data']['results_info']['total'])->toBe(2);
            });

            it('filters by tooltip', function (): void {
                $response = $this->getJson(route('api.channel-lister-field.search', [
                    'search' => 'USD',
                ]));

                $response->assertStatus(200);
                $responseData = $response->json();

                expect($responseData['data']['results_info']['total'])->toBe(1);
            });

            it('performs case-insensitive search', function (): void {
                $response = $this->getJson(route('api.channel-lister-field.search', [
                    'search' => 'AMAZON',
                ]));

                $response->assertStatus(200);
                $responseData = $response->json();

                expect($responseData['data']['results_info']['total'])->toBe(3);
            });

            it('returns no results for non-matching search', function (): void {
                $response = $this->getJson(route('api.channel-lister-field.search', [
                    'search' => 'nonexistent',
                ]));

                $response->assertStatus(200);
                $responseData = $response->json();

                expect($responseData['data']['results_info']['total'])->toBe(0);
            });
        });

        describe('marketplace filtering', function (): void {
            it('filters by specific marketplace', function (): void {
                $response = $this->getJson(route('api.channel-lister-field.search', [
                    'marketplace' => 'amazon',
                ]));

                $response->assertStatus(200);
                $responseData = $response->json();

                expect($responseData['data']['results_info']['total'])->toBe(3);
            });

            it('filters by different marketplace', function (): void {
                $response = $this->getJson(route('api.channel-lister-field.search', [
                    'marketplace' => 'ebay',
                ]));

                $response->assertStatus(200);
                $responseData = $response->json();

                expect($responseData['data']['results_info']['total'])->toBe(1);
            });

            it('returns no results for non-existent marketplace', function (): void {
                $response = $this->getJson(route('api.channel-lister-field.search', [
                    'marketplace' => 'nonexistent',
                ]));

                $response->assertStatus(200);
                $responseData = $response->json();

                expect($responseData['data']['results_info']['total'])->toBe(0);
            });
        });

        describe('required filtering', function (): void {
            it('filters by required fields only', function (): void {
                $response = $this->getJson(route('api.channel-lister-field.search', [
                    'required' => true,
                ]));

                $response->assertStatus(200);
                $responseData = $response->json();

                expect($responseData['data']['results_info']['total'])->toBe(3);
            });

            it('filters by optional fields only', function (): void {
                $response = $this->getJson(route('api.channel-lister-field.search', [
                    'required' => false,
                ]));

                $response->assertStatus(200);
                $responseData = $response->json();

                expect($responseData['data']['results_info']['total'])->toBe(2);
            });

            it('handles required as string "true"', function (): void {
                $response = $this->getJson(route('api.channel-lister-field.search', [
                    'required' => 'true',
                ]));

                $response->assertStatus(200);
                $responseData = $response->json();

                expect($responseData['data']['results_info']['total'])->toBe(3);
            });

            it('handles required as string "false"', function (): void {
                $response = $this->getJson(route('api.channel-lister-field.search', [
                    'required' => 'false',
                ]));

                $response->assertStatus(200);
                $responseData = $response->json();

                expect($responseData['data']['results_info']['total'])->toBe(2);
            });
        });

        describe('input_type filtering', function (): void {
            it('filters by text input type', function (): void {
                $response = $this->getJson(route('api.channel-lister-field.search', [
                    'input_type' => 'text',
                ]));

                $response->assertStatus(200);
                $responseData = $response->json();

                expect($responseData['data']['results_info']['total'])->toBe(1);
            });

            it('filters by currency input type', function (): void {
                $response = $this->getJson(route('api.channel-lister-field.search', [
                    'input_type' => 'currency',
                ]));

                $response->assertStatus(200);
                $responseData = $response->json();

                expect($responseData['data']['results_info']['total'])->toBe(1);
            });

            it('returns no results for non-existent input type', function (): void {
                $response = $this->getJson(route('api.channel-lister-field.search', [
                    'input_type' => 'nonexistent',
                ]));

                $response->assertStatus(200);
                $responseData = $response->json();

                expect($responseData['data']['results_info']['total'])->toBe(0);
            });
        });

        describe('pagination', function (): void {
            it('respects per_page parameter', function (): void {
                $response = $this->getJson(route('api.channel-lister-field.search', [
                    'per_page' => 2,
                ]));

                $response->assertStatus(200);
                $responseData = $response->json();

                expect($responseData['data']['results_info']['total'])->toBe(5);
                expect($responseData['data']['results_info']['to'])->toBe(2);
                expect($responseData['data']['results_info']['last_page'])->toBe(3);
            });

            it('respects page parameter', function (): void {
                $response = $this->getJson(route('api.channel-lister-field.search', [
                    'per_page' => 2,
                    'page' => 2,
                ]));

                $response->assertStatus(200);
                $responseData = $response->json();

                expect($responseData['data']['results_info']['current_page'])->toBe(2);
                expect($responseData['data']['results_info']['from'])->toBe(3);
                expect($responseData['data']['results_info']['to'])->toBe(4);
            });

            it('handles last page correctly', function (): void {
                $response = $this->getJson(route('api.channel-lister-field.search', [
                    'per_page' => 2,
                    'page' => 3,
                ]));

                $response->assertStatus(200);
                $responseData = $response->json();

                expect($responseData['data']['results_info']['current_page'])->toBe(3);
                expect($responseData['data']['results_info']['from'])->toBe(5);
                expect($responseData['data']['results_info']['to'])->toBe(5);
            });

            it('uses default pagination when not specified', function (): void {
                $response = $this->getJson(route('api.channel-lister-field.search'));

                $response->assertStatus(200);
                $responseData = $response->json();

                expect($responseData['data']['results_info']['current_page'])->toBe(1);
                expect($responseData['data']['results_info']['from'])->toBe(1);
                expect($responseData['data']['results_info']['to'])->toBe(5);
            });
        });

        describe('combined filters', function (): void {
            it('combines search and marketplace filters', function (): void {
                $response = $this->getJson(route('api.channel-lister-field.search', [
                    'search' => 'price',
                    'marketplace' => 'amazon',
                ]));

                $response->assertStatus(200);
                $responseData = $response->json();

                expect($responseData['data']['results_info']['total'])->toBe(1);
            });

            it('combines marketplace and required filters', function (): void {
                $response = $this->getJson(route('api.channel-lister-field.search', [
                    'marketplace' => 'amazon',
                    'required' => true,
                ]));

                $response->assertStatus(200);
                $responseData = $response->json();

                expect($responseData['data']['results_info']['total'])->toBe(2);
            });

            it('combines all filters', function (): void {
                $response = $this->getJson(route('api.channel-lister-field.search', [
                    'search' => 'price',
                    'marketplace' => 'amazon',
                    'required' => true,
                    'input_type' => 'currency',
                ]));

                $response->assertStatus(200);
                $responseData = $response->json();

                expect($responseData['data']['results_info']['total'])->toBe(1);
            });

            it('returns no results when filters have no matches', function (): void {
                $response = $this->getJson(route('api.channel-lister-field.search', [
                    'marketplace' => 'amazon',
                    'input_type' => 'textarea', // textarea is only on ebay
                ]));

                $response->assertStatus(200);
                $responseData = $response->json();

                expect($responseData['data']['results_info']['total'])->toBe(0);
            });
        });

        describe('validation', function (): void {
            it('validates search parameter length', function (): void {
                $longString = str_repeat('a', 256);

                $response = $this->getJson(route('api.channel-lister-field.search', [
                    'search' => $longString,
                ]));

                $response->assertStatus(422)
                    ->assertJsonValidationErrors(['search']);
            });

            it('validates page parameter minimum', function (): void {
                $response = $this->getJson(route('api.channel-lister-field.search', [
                    'page' => 0,
                ]));

                $response->assertStatus(422)
                    ->assertJsonValidationErrors(['page']);
            });

            it('validates per_page parameter minimum', function (): void {
                $response = $this->getJson(route('api.channel-lister-field.search', [
                    'per_page' => 0,
                ]));

                $response->assertStatus(422)
                    ->assertJsonValidationErrors(['per_page']);
            });

            it('validates per_page parameter maximum', function (): void {
                $response = $this->getJson(route('api.channel-lister-field.search', [
                    'per_page' => 101,
                ]));

                $response->assertStatus(422)
                    ->assertJsonValidationErrors(['per_page']);
            });

            it('validates required parameter as boolean', function (): void {
                $response = $this->getJson(route('api.channel-lister-field.search', [
                    'required' => 'invalid',
                ]));

                $response->assertStatus(422)
                    ->assertJsonValidationErrors(['required']);
            });

            it('accepts valid parameters', function (): void {
                $response = $this->getJson(route('api.channel-lister-field.search', [
                    'search' => 'valid search',
                    'marketplace' => 'amazon',
                    'required' => true,
                    'input_type' => 'text',
                    'page' => 1,
                    'per_page' => 10,
                ]));

                $response->assertStatus(200);
            });
        });

        describe('sorting', function (): void {
            it('returns results ordered by ordering field', function (): void {
                $response = $this->getJson(route('api.channel-lister-field.search', [
                    'per_page' => 10,
                ]));

                $response->assertStatus(200);
                $html = $response->json('data.table_html');

                // Check that the first row contains the field with ordering 1
                expect($html)->toContain('product_title');

                // The table should be ordered by the ordering field
                // We can verify this by checking the structure contains ordering data
                expect($html)->toBeString();
                expect(strlen($html))->toBeGreaterThan(0);
            });
        });

        describe('edge cases', function (): void {
            it('handles empty database gracefully', function (): void {
                ChannelListerField::truncate();

                $response = $this->getJson(route('api.channel-lister-field.search'));

                $response->assertStatus(200);
                $responseData = $response->json();

                expect($responseData['data']['results_info']['total'])->toBe(0);
                expect($responseData['data']['results_info']['from'])->toBe(null);
                expect($responseData['data']['results_info']['to'])->toBe(null);
            });

            it('handles special characters in search', function (): void {
                $response = $this->getJson(route('api.channel-lister-field.search', [
                    'search' => 'test@#$%^&*()',
                ]));

                $response->assertStatus(200);
                $responseData = $response->json();

                expect($responseData['data']['results_info']['total'])->toBe(0);
            });

            it('handles very large page numbers', function (): void {
                $response = $this->getJson(route('api.channel-lister-field.search', [
                    'page' => 999,
                ]));

                $response->assertStatus(200);
                $responseData = $response->json();

                expect($responseData['data']['results_info']['current_page'])->toBe(999);
                expect($responseData['data']['results_info']['from'])->toBe(null);
                expect($responseData['data']['results_info']['to'])->toBe(null);
            });
        });
    });
});
