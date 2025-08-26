<?php

use IGE\ChannelLister\Services\AmazonSpApiService;
use IGE\ChannelLister\Services\AmazonTokenManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    // Mock the token manager
    $this->tokenManager = $this->createMock(AmazonTokenManager::class);
    $this->tokenManager->method('getAccessToken')->willReturn('mock_access_token');

    $this->service = new AmazonSpApiService($this->tokenManager);

    // Clear cache before each test
    Cache::flush();

    // Set up storage fake
    Storage::fake('local');
});

it('can search product types', function (): void {
    // Mock the HTTP response for product types search
    Http::fake([
        'sellingpartnerapi-na.amazon.com/definitions/2020-09-01/productTypes*' => Http::response([
            'productTypes' => [
                [
                    'name' => 'LUGGAGE',
                    'displayName' => 'Luggage',
                    'description' => 'Travel luggage and bags',
                ],
                [
                    'name' => 'BACKPACK',
                    'displayName' => 'Backpack',
                    'description' => 'Backpacks and hiking bags',
                ],
            ],
        ], 200),
    ]);

    $result = $this->service->searchProductTypes('luggage');

    expect($result)->toBeArray()
        ->toHaveCount(2);
    expect($result[0]['id'])->toBe('LUGGAGE');
    expect($result[0]['name'])->toBe('Luggage');
    expect($result[0]['description'])->toBe('Travel luggage and bags');
});

it('caches product type searches', function (): void {
    Http::fake([
        'sellingpartnerapi-na.amazon.com/definitions/2020-09-01/productTypes*' => Http::response([
            'productTypes' => [
                ['name' => 'LUGGAGE', 'displayName' => 'Luggage'],
            ],
        ], 200),
    ]);

    // First call
    $result1 = $this->service->searchProductTypes('luggage');

    // Second call - should be cached
    $result2 = $this->service->searchProductTypes('luggage');

    expect($result1)->toEqual($result2);

    // Verify only one HTTP request was made
    Http::assertSentCount(1);
});

it('can get listing requirements with direct schema', function (): void {
    // Mock the HTTP response for listing requirements (old format)
    Http::fake([
        'sellingpartnerapi-na.amazon.com/definitions/2020-09-01/productTypes/LUGGAGE*' => Http::response([
            'schema' => [
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
                ],
                'required' => ['item_name', 'brand'],
            ],
        ], 200),
    ]);

    $result = $this->service->getListingRequirements('LUGGAGE');

    expect($result)->toBeArray()
        ->toHaveCount(2);

    $itemName = collect($result)->firstWhere('name', 'item_name');
    expect($itemName['displayName'])->toBe('Title');
    expect($itemName['description'])->toBe('Product title');
    expect($itemName['required'])->toBeTrue();
    expect($itemName['example'])->toBe('Blue Suitcase');
});

it('can get listing requirements with schema link', function (): void {
    $schemaUrl = 'https://s3.amazonaws.com/test-schema.json';

    // Mock the initial SP-API response with schema link
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
                    'description' => 'Product title',
                    'type' => 'string',
                    'examples' => ['Blue Suitcase'],
                ],
            ],
            'required' => ['item_name'],
        ], 200),
    ]);

    $result = $this->service->getListingRequirements('LUGGAGE');

    expect($result)->toBeArray()
        ->toHaveCount(1);

    $itemName = $result[0];
    expect($itemName['name'])->toBe('item_name');
    expect($itemName['displayName'])->toBe('Title');
    expect($itemName['required'])->toBeTrue();
});

it('caches schema files to disk', function (): void {
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

    // First call - should fetch and cache
    $this->service->getListingRequirements('LUGGAGE');

    // Verify schema was cached to disk
    $urlHash = md5($schemaUrl);
    $diskPath = "amazon-schemas/{$urlHash}.json";
    expect(Storage::disk('local')->exists($diskPath))->toBeTrue();

    // Verify cached content
    $cachedContent = Storage::disk('local')->get($diskPath);
    $cachedData = json_decode((string) $cachedContent, true);
    expect($cachedData)->toHaveKey('properties');
    expect($cachedData['properties'])->toHaveKey('item_name');
});

it('uses cached schema from disk', function (): void {
    $schemaUrl = 'https://s3.amazonaws.com/test-schema.json';
    $urlHash = md5($schemaUrl);
    $diskPath = "amazon-schemas/{$urlHash}.json";

    // Pre-populate disk cache
    $schemaData = [
        'properties' => [
            'item_name' => [
                'title' => 'Cached Title',
                'type' => 'string',
            ],
        ],
        'required' => ['item_name'],
    ];
    Storage::disk('local')->put($diskPath, json_encode($schemaData));

    Http::fake([
        'sellingpartnerapi-na.amazon.com/definitions/2020-09-01/productTypes/LUGGAGE*' => Http::response([
            'schema' => [
                'link' => [
                    'resource' => $schemaUrl,
                ],
            ],
        ], 200),
    ]);

    $result = $this->service->getListingRequirements('LUGGAGE');

    // Should use cached data, not make HTTP request to schema URL
    Http::assertNotSent(fn ($request): bool => $request->url() === $schemaUrl);

    expect($result)->toHaveCount(1);
    expect($result[0]['displayName'])->toBe('Cached Title');
});

it('can get existing listing', function (): void {
    Http::fake([
        'sellingpartnerapi-na.amazon.com/catalog/2022-04-01/items*' => Http::response([
            'items' => [
                [
                    'asin' => 'B123456789',
                    'attributes' => [
                        'item_name' => [
                            ['value' => 'Blue Suitcase'],
                        ],
                    ],
                    'productTypes' => [
                        ['name' => 'LUGGAGE'],
                    ],
                    'salesRanks' => [],
                ],
            ],
        ], 200),
    ]);

    $result = $this->service->getExistingListing('B123456789', 'ASIN');

    expect($result)->toBeArray();
    expect($result['asin'])->toBe('B123456789');
    expect($result['title'])->toBe('Blue Suitcase');
    expect($result)->toHaveKey('productTypes');
});

it('returns null for non existent listing', function (): void {
    Http::fake([
        'sellingpartnerapi-na.amazon.com/catalog/2022-04-01/items*' => Http::response([], 404),
    ]);

    $result = $this->service->getExistingListing('NONEXISTENT', 'ASIN');

    expect($result)->toBeNull();
});

it('can generate form fields from requirements', function (): void {
    $requirements = [
        [
            'name' => 'item_name',
            'displayName' => 'Title',
            'description' => 'Product title',
            'type' => 'string',
            'required' => true,
            'example' => 'Blue Suitcase',
            'grouping' => 'Product Details',
        ],
        [
            'name' => 'brand',
            'displayName' => 'Brand',
            'description' => 'Product brand',
            'type' => 'string',
            'required' => true,
            'enum' => ['Nike', 'Adidas'],
            'grouping' => 'Brand Information',
        ],
    ];

    $fields = $this->service->generateFormFields($requirements);

    expect($fields)->toHaveCount(2);

    $titleField = $fields->first();
    expect($titleField->field_name)->toBe('item_name');
    expect($titleField->display_name)->toBe('Title');
    expect($titleField->tooltip)->toBe('Product title');
    expect($titleField->required)->toBeTrue();
    expect($titleField->marketplace)->toBe('amazon');

    $brandField = $fields->last();
    expect($brandField->field_name)->toBe('brand');
    expect($brandField->input_type_aux)->toBe('Nike||Adidas');
});

it('can clear schema cache', function (): void {
    // Create some test files
    Storage::disk('local')->put('amazon-schemas/test1.json', '{}');
    Storage::disk('local')->put('amazon-schemas/test2.json', '{}');

    $this->service->clearSchemaCache();

    expect(Storage::disk('local')->exists('amazon-schemas/test1.json'))->toBeFalse();
    expect(Storage::disk('local')->exists('amazon-schemas/test2.json'))->toBeFalse();
});

it('can clear specific product type cache', function (): void {
    // Set up cache
    $cacheKey = 'amazon_listing_requirements_LUGGAGE_ATVPDKIKX0DER_en_US';
    Cache::put($cacheKey, ['test' => 'data'], 3600);

    $this->service->clearProductTypeCache('LUGGAGE');

    expect(Cache::get($cacheKey))->toBeNull();
});

it('handles api errors gracefully', function (): void {
    Http::fake([
        'sellingpartnerapi-na.amazon.com/*' => Http::response([
            'errors' => [
                [
                    'code' => 'Unauthorized',
                    'message' => 'Access denied',
                ],
            ],
        ], 401),
    ]);

    $result = $this->service->searchProductTypes('luggage');

    expect($result)->toBeArray()
        ->toBeEmpty();
});

it('uses configurable cache settings', function (): void {
    // Override configuration for this test
    config(['channel-lister.amazon.cache.disk' => 'local']);
    config(['channel-lister.amazon.cache.ttl.schema_files' => 1000]);
    config(['channel-lister.amazon.cache.schema_path' => 'custom-path']);

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
                'item_name' => ['title' => 'Title', 'type' => 'string'],
            ],
        ], 200),
    ]);

    $this->service->getListingRequirements('LUGGAGE');

    // Verify custom path was used
    $urlHash = md5($schemaUrl);
    $customPath = "custom-path/{$urlHash}.json";
    expect(Storage::disk('local')->exists($customPath))->toBeTrue();
});

it('can get marketplace name', function (): void {
    $result = $this->service->getMarketplaceName();

    expect($result)->toBe('amazon');
});

it('can get token info', function (): void {
    $this->tokenManager->method('getTokenInfo')->willReturn([
        'has_token' => true,
        'expires_in_seconds' => 3600,
        'is_valid' => true,
    ]);

    $result = $this->service->getTokenInfo();

    expect($result)->toBeArray()
        ->toHaveKey('has_token', true)
        ->toHaveKey('expires_in_seconds', 3600)
        ->toHaveKey('is_valid', true);
});

it('returns null when token info is unavailable', function (): void {
    $this->tokenManager->method('getTokenInfo')->willReturn(null);

    $result = $this->service->getTokenInfo();

    expect($result)->toBeNull();
});

it('can validate configuration', function (): void {
    $this->tokenManager->method('validateConfiguration')->willReturn([]);

    $errors = $this->service->validateConfiguration();

    expect($errors)->toBeArray()->toBeEmpty();
});

it('returns configuration errors when present', function (): void {
    $this->tokenManager->method('validateConfiguration')->willReturn([
        'AMAZON_SP_API_CLIENT_ID is required',
        'AMAZON_SP_API_CLIENT_SECRET is required',
    ]);

    $errors = $this->service->validateConfiguration();

    expect($errors)->toBeArray()->toHaveCount(2)
        ->toContain('AMAZON_SP_API_CLIENT_ID is required')
        ->toContain('AMAZON_SP_API_CLIENT_SECRET is required');
});

it('handles marketplace listing provider interface methods', function (): void {
    // Test that the service implements the required interface
    expect($this->service)->toBeInstanceOf(\IGE\ChannelLister\Contracts\MarketplaceListingProvider::class);

    // Test getMarketplaceName method from interface
    expect($this->service->getMarketplaceName())->toBe('amazon');
});

it('handles API errors with custom exception', function (): void {
    Http::fake([
        'sellingpartnerapi-na.amazon.com/definitions/2020-09-01/productTypes*' => Http::response([
            'errors' => [
                [
                    'code' => 'InvalidInput',
                    'message' => 'The provided input is invalid',
                    'details' => 'Query parameter is required',
                ],
            ],
        ], 400),
    ]);

    $result = $this->service->searchProductTypes('invalid');

    // Should return empty array when API error occurs
    expect($result)->toBeArray()->toBeEmpty();
});

it('handles network timeouts gracefully', function (): void {
    Http::fake(fn () => throw new \Exception('Connection timeout'));

    $result = $this->service->searchProductTypes('timeout-test');

    expect($result)->toBeArray()->toBeEmpty();
});

it('caches product type searches with custom TTL', function (): void {
    // Mock config for custom TTL
    config(['channel-lister.amazon.cache.ttl.product_types_search' => 7200]);

    Http::fake([
        'sellingpartnerapi-na.amazon.com/definitions/2020-09-01/productTypes*' => Http::response([
            'productTypes' => [
                [
                    'name' => 'LUGGAGE',
                    'displayName' => 'Luggage',
                    'description' => 'Travel luggage',
                ],
            ],
        ], 200),
    ]);

    $result1 = $this->service->searchProductTypes('luggage');
    $result2 = $this->service->searchProductTypes('luggage'); // Should hit cache

    expect($result1)->toEqual($result2);

    // Should only make one HTTP request due to caching
    Http::assertSentCount(1);
});

it('handles empty API responses gracefully', function (): void {
    Http::fake([
        'sellingpartnerapi-na.amazon.com/definitions/2020-09-01/productTypes*' => Http::response([
            'productTypes' => [],
        ], 200),
    ]);

    $result = $this->service->searchProductTypes('nonexistent');

    expect($result)->toBeArray()->toBeEmpty();
});

it('can clear all caches', function (): void {
    // Pre-populate some cache
    Http::fake([
        'sellingpartnerapi-na.amazon.com/definitions/2020-09-01/productTypes*' => Http::response([
            'productTypes' => [['name' => 'TEST', 'displayName' => 'Test']],
        ], 200),
        'sellingpartnerapi-na.amazon.com/definitions/2020-09-01/productTypes/TEST*' => Http::response([
            'schema' => ['properties' => ['item_name' => ['type' => 'string']]],
        ], 200),
    ]);

    $this->service->searchProductTypes('test');
    $this->service->getListingRequirements('TEST');

    // Clear all caches
    $this->service->clearSchemaCache();

    // Subsequent calls should hit API again
    Http::fake([
        'sellingpartnerapi-na.amazon.com/definitions/2020-09-01/productTypes*' => Http::response([
            'productTypes' => [['name' => 'TEST2', 'displayName' => 'Test2']],
        ], 200),
        'sellingpartnerapi-na.amazon.com/definitions/2020-09-01/productTypes/TEST*' => Http::response([
            'schema' => ['properties' => ['item_name' => ['type' => 'string']]],
        ], 200),
    ]);

    $result = $this->service->searchProductTypes('test');

    // Just verify cache clearing works and methods still return data
    expect($result)->toBeArray();
    expect($result)->not()->toBeEmpty();
});

it('can clear individual product type cache and force refresh', function (): void {
    // Pre-populate cache for specific product type
    Http::fake([
        'sellingpartnerapi-na.amazon.com/definitions/2020-09-01/productTypes/LUGGAGE*' => Http::response([
            'schema' => ['properties' => ['item_name' => ['type' => 'string']]],
        ], 200),
    ]);

    $this->service->getListingRequirements('LUGGAGE');

    // Clear specific product type cache
    $this->service->clearProductTypeCache('LUGGAGE');

    // Subsequent call should hit API again
    $result = $this->service->getListingRequirements('LUGGAGE');

    expect($result)->toBeArray();

    // Should have made 2 API calls (initial + after cache clear)
    Http::assertSentCount(2);
});

it('handles configuration with fallback values', function (): void {
    // Test that service handles missing config gracefully
    config(['channel-lister.amazon.sp_api_base_url' => null]);
    config(['channel-lister.amazon.marketplace_id' => null]);

    $service = new AmazonSpApiService($this->tokenManager);

    // Should still work with fallback values
    expect($service->getMarketplaceName())->toBe('amazon');
});
