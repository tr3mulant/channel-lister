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

    // Clear cache and set up storage
    Cache::flush();
    Storage::fake('local');
    Storage::fake('s3');
});

it('respects configurable cache disk', function (): void {
    config(['channel-lister.amazon.cache.disk' => 's3']);
    config(['channel-lister.amazon.cache.schema_path' => 'test-schemas']);

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

    // Should cache to S3, not local
    $urlHash = md5($schemaUrl);
    $diskPath = "test-schemas/{$urlHash}.json";

    Storage::disk('s3')->assertExists($diskPath);
    Storage::disk('local')->assertMissing($diskPath);
});

it('respects configurable cache ttl', function (): void {
    // Set very short TTL for testing
    config(['channel-lister.amazon.cache.ttl.product_types_search' => 1]);
    config(['channel-lister.amazon.cache.ttl.listing_requirements' => 1]);
    config(['channel-lister.amazon.cache.ttl.schema_files' => 1]);

    Http::fake([
        'sellingpartnerapi-na.amazon.com/definitions/2020-09-01/productTypes*' => Http::response([
            'productTypes' => [
                ['name' => 'LUGGAGE', 'displayName' => 'Luggage'],
            ],
        ], 200),
    ]);

    // First call
    $result1 = $this->service->searchProductTypes('luggage');
    expect($result1)->toHaveCount(1);

    // Verify cached
    $cacheKey = 'amazon_product_types_search_'.md5('luggageATVPDKIKX0DER');
    expect(Cache::get($cacheKey))->not->toBeNull();

    // Wait for TTL to expire
    sleep(2);

    // Cache should be expired
    expect(Cache::get($cacheKey))->toBeNull();
});

it('loads schema from disk cache before network', function (): void {
    $schemaUrl = 'https://s3.amazonaws.com/test-schema.json';
    $urlHash = md5($schemaUrl);
    $diskPath = "amazon-schemas/{$urlHash}.json";

    // Pre-populate disk cache with different data
    $cachedSchema = [
        'properties' => [
            'item_name' => [
                'title' => 'Cached Title (from disk)',
                'type' => 'string',
            ],
        ],
        'required' => ['item_name'],
    ];
    Storage::disk('local')->put($diskPath, json_encode($cachedSchema));

    // Set up HTTP mocks (should not be called)
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
                    'title' => 'Network Title (should not be used)',
                    'type' => 'string',
                ],
            ],
        ], 200),
    ]);

    $result = $this->service->getListingRequirements('LUGGAGE');

    // Should use cached data from disk, not network
    expect($result)->toHaveCount(1);
    expect($result[0]['displayName'])->toBe('Cached Title (from disk)');

    // Should not have made HTTP request to schema URL
    Http::assertNotSent(fn ($request): bool => $request->url() === $schemaUrl);
});

it('handles corrupted disk cache gracefully', function (): void {
    $schemaUrl = 'https://s3.amazonaws.com/test-schema.json';
    $urlHash = md5($schemaUrl);
    $diskPath = "amazon-schemas/{$urlHash}.json";

    // Put corrupted JSON in disk cache
    Storage::disk('local')->put($diskPath, 'invalid json data');

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
                    'title' => 'Network Title',
                    'type' => 'string',
                ],
            ],
        ], 200),
    ]);

    $result = $this->service->getListingRequirements('LUGGAGE');

    // Should fall back to network and work correctly
    expect($result)->toHaveCount(1);
    expect($result[0]['displayName'])->toBe('Network Title');

    // Should have made network request as fallback
    Http::assertSent(fn ($request): bool => $request->url() === $schemaUrl);
});

it('can clear all caches', function (): void {
    config(['channel-lister.amazon.cache.schema_path' => 'test-cache']);

    // Set up some cache data
    Cache::put('amazon_product_types_search_test', ['data'], 3600);
    Cache::put('amazon_listing_requirements_test', ['data'], 3600);
    Cache::put('amazon_schema_test', ['data'], 3600);

    // Create some disk files
    Storage::disk('local')->put('test-cache/file1.json', '{}');
    Storage::disk('local')->put('test-cache/file2.json', '{}');

    $this->service->clearSchemaCache();

    // Disk files should be cleared
    Storage::disk('local')->assertMissing('test-cache/file1.json');
    Storage::disk('local')->assertMissing('test-cache/file2.json');
});

it('can clear specific product type cache', function (): void {
    // Set up cache for multiple product types
    $luggageKey = 'amazon_listing_requirements_LUGGAGE_ATVPDKIKX0DER_en_US';
    $backpackKey = 'amazon_listing_requirements_BACKPACK_ATVPDKIKX0DER_en_US';

    Cache::put($luggageKey, ['luggage_data'], 3600);
    Cache::put($backpackKey, ['backpack_data'], 3600);

    // Clear only luggage cache
    $this->service->clearProductTypeCache('LUGGAGE');

    // Luggage cache should be cleared, backpack should remain
    expect(Cache::get($luggageKey))->toBeNull();
    expect(Cache::get($backpackKey))->not->toBeNull();
});

it('handles network failures gracefully with cache', function (): void {
    $schemaUrl = 'https://s3.amazonaws.com/test-schema.json';
    $urlHash = md5($schemaUrl);
    $diskPath = "amazon-schemas/{$urlHash}.json";

    // Pre-populate disk cache
    $cachedSchema = [
        'properties' => [
            'item_name' => ['title' => 'Cached Title', 'type' => 'string'],
        ],
        'required' => ['item_name'],
    ];
    Storage::disk('local')->put($diskPath, json_encode($cachedSchema));

    // Simulate network failures
    Http::fake([
        'sellingpartnerapi-na.amazon.com/definitions/2020-09-01/productTypes/LUGGAGE*' => Http::response([
            'schema' => [
                'link' => [
                    'resource' => $schemaUrl,
                ],
            ],
        ], 200),
        $schemaUrl => Http::response([], 500), // Simulate S3 failure
    ]);

    $result = $this->service->getListingRequirements('LUGGAGE');

    // Should still work using disk cache despite network failure
    expect($result)->toHaveCount(1);
    expect($result[0]['displayName'])->toBe('Cached Title');
});

it('populates disk cache after successful network request', function (): void {
    $schemaUrl = 'https://s3.amazonaws.com/test-schema.json';
    $urlHash = md5($schemaUrl);
    $diskPath = "amazon-schemas/{$urlHash}.json";

    // Ensure no disk cache exists
    Storage::disk('local')->assertMissing($diskPath);

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
                    'title' => 'Network Title',
                    'type' => 'string',
                ],
            ],
            'required' => ['item_name'],
        ], 200),
    ]);

    $result = $this->service->getListingRequirements('LUGGAGE');

    // Should work correctly
    expect($result)->toHaveCount(1);
    expect($result[0]['displayName'])->toBe('Network Title');

    // Should have populated disk cache
    Storage::disk('local')->assertExists($diskPath);

    // Verify cached content is correct
    $cachedContent = Storage::disk('local')->get($diskPath);
    $cachedData = json_decode((string) $cachedContent, true);
    expect($cachedData['properties']['item_name']['title'])->toBe('Network Title');
});

it('uses different cache keys for different marketplaces', function (): void {
    // Mock service with different marketplace
    $service2 = new AmazonSpApiService($this->tokenManager);
    $reflection = new \ReflectionClass($service2);
    $property = $reflection->getProperty('marketplaceId');
    $property->setAccessible(true);
    $property->setValue($service2, 'A1F83G8C2ARO7P'); // UK marketplace

    Http::fake([
        'sellingpartnerapi-na.amazon.com/definitions/2020-09-01/productTypes*' => Http::response([
            'productTypes' => [
                ['name' => 'LUGGAGE', 'displayName' => 'Luggage'],
            ],
        ], 200),
    ]);

    // Search with different marketplaces
    $this->service->searchProductTypes('luggage'); // US marketplace
    $service2->searchProductTypes('luggage'); // UK marketplace

    // Should have different cache keys
    $usCacheKey = 'amazon_product_types_search_'.md5('luggageATVPDKIKX0DER');
    $ukCacheKey = 'amazon_product_types_search_'.md5('luggageA1F83G8C2ARO7P');

    expect(Cache::get($usCacheKey))->not->toBeNull();
    expect(Cache::get($ukCacheKey))->not->toBeNull();
    expect($usCacheKey)->not->toBe($ukCacheKey);
});
