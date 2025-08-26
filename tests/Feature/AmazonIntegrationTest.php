<?php

use IGE\ChannelLister\Models\AmazonListing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class, WithFaker::class);

beforeEach(function (): void {
    // Set up storage and cache for testing
    Storage::fake('local');
    Cache::flush();
});

it('can complete full amazon listing workflow', function (): void {
    $schemaUrl = 'https://s3.amazonaws.com/luggage-schema.json';

    // Mock all external API calls
    Http::fake([
        // Listing requirements
        'sellingpartnerapi-na.amazon.com/definitions/2020-09-01/productTypes/LUGGAGE*' => Http::response([
            'schema' => [
                'link' => [
                    'resource' => $schemaUrl,
                ],
            ],
        ], 200),

        // Product type search
        'sellingpartnerapi-na.amazon.com/definitions/2020-09-01/productTypes*' => Http::response([
            'productTypes' => [
                [
                    'name' => 'LUGGAGE',
                    'displayName' => 'Luggage',
                    'description' => 'Travel luggage and bags',
                ],
            ],
        ], 200),

        // Schema file
        $schemaUrl => Http::response([
            'properties' => [
                'item_name' => [
                    'title' => 'Title',
                    'description' => 'Product title',
                    'type' => 'string',
                    'examples' => ['Blue Suitcase'],
                ],
                'brand' => [
                    'title' => 'Brand Name',
                    'description' => 'Product brand',
                    'type' => 'string',
                    'examples' => ['Samsonite'],
                ],
                'product_description' => [
                    'title' => 'Product Description',
                    'description' => 'Detailed product description',
                    'type' => 'string',
                ],
                'bullet_point' => [
                    'title' => 'Key Product Features',
                    'description' => 'Product highlights',
                    'type' => 'string',
                ],
                'item_type_keyword' => [
                    'title' => 'Item Type Keyword',
                    'description' => 'Product category keyword',
                    'type' => 'string',
                ],
                'country_of_origin' => [
                    'title' => 'Country of Publication',
                    'description' => 'Country where product was made',
                    'type' => 'string',
                ],
                'supplier_declared_dg_hz_regulation' => [
                    'title' => 'Dangerous Goods Regulations',
                    'description' => 'Safety regulations',
                    'type' => 'string',
                ],
            ],
            'required' => ['item_name', 'brand', 'product_description', 'bullet_point', 'item_type_keyword', 'country_of_origin', 'supplier_declared_dg_hz_regulation'],
        ], 200),
    ]);

    // Step 1: Search for product types
    $searchResponse = $this->postJson('/api/amazon-listing/search-product-types', [
        'query' => 'luggage',
    ]);

    expect($searchResponse->status())->toBe(200);
    $searchData = $searchResponse->json('data');
    expect($searchData)->toContain('LUGGAGE');
    expect($searchData)->toContain('Luggage');

    // Step 2: Get listing requirements
    $requirementsResponse = $this->postJson('/api/amazon-listing/listing-requirements', [
        'product_type' => 'LUGGAGE',
    ]);

    expect($requirementsResponse->status())->toBe(200);
    $requirementsData = $requirementsResponse->json('data');

    // Verify HTML panels were generated
    expect($requirementsData['html'])->toContain('Product Details');
    expect($requirementsData['html'])->toContain('Brand Information');
    expect($requirementsData['html'])->toContain('Title');
    expect($requirementsData['html'])->toContain('Brand Name');

    // Verify fields array contains the right structure
    expect($requirementsData['fields'])->toBeArray();
    expect(count($requirementsData['fields']))->toBeGreaterThan(0);

    // Step 3: Submit listing with form data
    $formData = [
        'item_name' => 'Premium Blue Suitcase',
        'brand' => 'Samsonite',
        'product_description' => 'This is a high-quality blue suitcase perfect for travel.',
        'bullet_point' => 'Durable construction with 4 wheels',
        'item_type_keyword' => 'luggage',
        'country_of_origin' => 'US',
        'supplier_declared_dg_hz_regulation' => 'GHS',
    ];

    $submitResponse = $this->postJson('/api/amazon-listing/submit', [
        'product_type' => 'LUGGAGE',
        'marketplace_id' => 'ATVPDKIKX0DER',
        'form_data' => $formData,
    ]);

    expect($submitResponse->status())->toBe(200);
    $submitData = $submitResponse->json();

    expect($submitData['success'])->toBeTrue();
    expect($submitData)->toHaveKey('listing_id');
    $listingId = $submitData['listing_id'];

    // Verify listing was created in database
    $listing = AmazonListing::find($listingId);

    expect($listing)->not()->toBeNull();
    expect($listing->product_type)->toEqual('LUGGAGE');
    expect($listing->marketplace_id)->toEqual('ATVPDKIKX0DER');
    expect($listing->status)->toEqual('validated');
    expect($listing->form_data)->toEqual($formData);

    // Step 4: Get listing status
    $statusResponse = $this->getJson("/api/amazon-listing/listings/{$listingId}");

    expect($statusResponse->status())->toBe(200);
    $statusData = $statusResponse->json();

    expect($statusData['listing']['id'])->toBe($listingId);
    expect($statusData['listing']['product_type'])->toBe('LUGGAGE');
    expect($statusData['listing']['status'])->toBe('validated');

    // Step 5: Generate file (CSV)
    $generateResponse = $this->postJson('/api/amazon-listing/generate-file', [
        'listing_id' => $listingId,
        'format' => 'csv',
    ]);

    expect($generateResponse->status())->toBe(200);
    $generateData = $generateResponse->json();

    expect($generateData['success'])->toBeTrue();
    expect($generateData['format'])->toBe('csv');
    expect($generateData)->toHaveKey('download_url');

    // Verify caching worked
    $urlHash = md5($schemaUrl);
    $diskPath = "amazon-schemas/{$urlHash}.json";
    Storage::disk('local')->assertExists($diskPath);

    // Verify cache keys exist
    expect(Cache::get('amazon_product_types_search_'.md5('luggageATVPDKIKX0DER')))->not->toBeNull();
    expect(Cache::get('amazon_listing_requirements_LUGGAGE_ATVPDKIKX0DER_en_US'))->not->toBeNull();
});

it('can lookup existing amazon listing', function (): void {
    Http::fake([
        'sellingpartnerapi-na.amazon.com/definitions/2020-09-01/productTypes/LUGGAGE*' => Http::response([
            'schema' => [
                'properties' => [
                    'item_name' => [
                        'title' => 'Title',
                        'type' => 'string',
                    ],
                ],
                'required' => ['item_name'],
            ],
        ], 200),
        'sellingpartnerapi-na.amazon.com/catalog/2022-04-01/items*' => Http::response([
            'items' => [
                [
                    'asin' => 'B123456789',
                    'attributes' => [
                        'item_name' => [
                            ['value' => 'Existing Blue Suitcase'],
                        ],
                        'brand' => [
                            ['value' => 'ExistingBrand'],
                        ],
                    ],
                    'productTypes' => [
                        ['productType' => 'LUGGAGE'],
                    ],
                    'salesRanks' => [],
                ],
            ],
        ], 200),
    ]);

    $response = $this->postJson('/api/amazon-listing/existing-listing', [
        'identifier' => 'B123456789',
        'identifier_type' => 'ASIN',
    ]);

    expect($response->status())->toBe(200);
    $responseData = $response->json('data');

    expect($responseData['listing']['asin'])->toBe('B123456789');
    expect($responseData['listing']['title'])->toBe('Existing Blue Suitcase');
    expect($responseData['product_type'])->toBe('LUGGAGE');
    expect($responseData['form_fields'])->toBeArray();
});

it('handles cache misses and hits correctly', function (): void {
    $schemaUrl = 'https://s3.amazonaws.com/test-schema.json';

    Http::fake([
        'sellingpartnerapi-na.amazon.com/definitions/2020-09-01/productTypes/LUGGAGE*' => Http::response([
            'schema' => [
                'link' => [
                    'resource' => $schemaUrl,
                ],
            ],
        ], 200),
        $schemaUrl => Http::response([
            'properties' => [
                'item_name' => [
                    'title' => 'Title',
                    'type' => 'string',
                ],
            ],
            'required' => ['item_name'],
        ], 200),
    ]);

    // First request - should hit external APIs
    $response1 = $this->postJson('/api/amazon-listing/listing-requirements', [
        'product_type' => 'LUGGAGE',
    ]);

    expect($response1->status())->toBe(200);

    // Verify external calls were made
    Http::assertSentCount(2); // One for requirements, one for schema

    // Second request - should use cache
    $response2 = $this->postJson('/api/amazon-listing/listing-requirements', [
        'product_type' => 'LUGGAGE',
    ]);

    expect($response2->status())->toBe(200);

    // Should still only have 2 requests (no new ones)
    Http::assertSentCount(2);

    // Responses should be identical
    expect($response1->json())->toEqual($response2->json());
});

it('validates form data and shows errors', function (): void {
    Http::fake([
        'sellingpartnerapi-na.amazon.com/definitions/2020-09-01/productTypes/LUGGAGE*' => Http::response([
            'schema' => [
                'properties' => [
                    'item_name' => [
                        'title' => 'Title',
                        'type' => 'string',
                    ],
                    'brand' => [
                        'title' => 'Brand',
                        'type' => 'string',
                    ],
                ],
                'required' => ['item_name', 'brand'],
            ],
        ], 200),
    ]);

    // Submit incomplete form data (missing required brand)
    $response = $this->postJson('/api/amazon-listing/submit', [
        'product_type' => 'LUGGAGE',
        'marketplace_id' => 'ATVPDKIKX0DER',
        'form_data' => [
            'item_name' => 'Test Suitcase',
            // Missing 'brand' which is required
        ],
    ]);

    expect($response->status())->toBe(200); // Still successful but with validation issues
    $responseData = $response->json();

    expect($responseData['success'])->toBeTrue();
    expect($responseData['status'])->toBe('error');

    // Should have validation summary with missing fields
    expect($responseData)->toHaveKey('validation_summary');
});

it('can filter and paginate listings', function (): void {
    // Create test listings
    AmazonListing::factory()->count(3)->create([
        'status' => 'validated',
        'product_type' => 'LUGGAGE',
    ]);

    AmazonListing::factory()->count(2)->create([
        'status' => 'draft',
        'product_type' => 'BACKPACK',
    ]);

    // Test filtering by status
    $response = $this->getJson('/api/amazon-listing/listings?status=validated');
    expect($response->status())->toBe(200);
    expect($response->json('listings'))->toHaveCount(3);

    // Test filtering by product type
    $response = $this->getJson('/api/amazon-listing/listings?product_type=BACKPACK');
    expect($response->status())->toBe(200);
    expect($response->json('listings'))->toHaveCount(2);

    // Test pagination
    $response = $this->getJson('/api/amazon-listing/listings?per_page=2');
    expect($response->status())->toBe(200);
    expect($response->json('listings'))->toHaveCount(2);

    $paginationData = $response->json('pagination');
    expect($paginationData['current_page'])->toBe(1);
    expect($paginationData['last_page'])->toBeGreaterThan(1);
    expect($paginationData['total'])->toBe(5);
});
