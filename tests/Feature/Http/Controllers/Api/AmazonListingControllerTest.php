<?php

use IGE\ChannelLister\Models\AmazonListing;
use IGE\ChannelLister\Services\AmazonDataTransformer;
use IGE\ChannelLister\Services\AmazonListingFormProcessor;
use IGE\ChannelLister\Services\AmazonSpApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Mock the services
    $this->amazonService = mock(AmazonSpApiService::class);
    $this->formProcessor = mock(AmazonListingFormProcessor::class);
    $this->dataTransformer = mock(AmazonDataTransformer::class);

    // Bind mocks to the container
    $this->app->instance(AmazonSpApiService::class, $this->amazonService);
    $this->app->instance(AmazonListingFormProcessor::class, $this->formProcessor);
    $this->app->instance(AmazonDataTransformer::class, $this->dataTransformer);
});

it('can search product types', function (): void {
    $this->amazonService
        ->shouldReceive('searchProductTypes')
        ->with('luggage')
        ->once()
        ->andReturn([
            ['id' => 'LUGGAGE', 'name' => 'Luggage', 'description' => 'Travel bags'],
            ['id' => 'BACKPACK', 'name' => 'Backpack', 'description' => 'Hiking bags'],
        ]);

    $response = $this->postJson('/api/amazon-listing/search-product-types', [
        'query' => 'luggage',
    ]);

    expect($response->status())->toBe(200);
    $response->assertJsonStructure([
        'data',
        'count',
    ])
        ->assertJson([
            'count' => 2,
        ]);

    expect($response->json('data'))->toContain('LUGGAGE');
    expect($response->json('data'))->toContain('Luggage');
});

it('validates product type search query', function (): void {
    $response = $this->postJson('/api/amazon-listing/search-product-types', [
        'query' => 'ab', // Too short
    ]);

    expect($response->status())->toBe(422);
    $response->assertJsonValidationErrors(['query']);
});

it('can get listing requirements', function (): void {
    $mockRequirements = [
        [
            'name' => 'item_name',
            'displayName' => 'Title',
            'description' => 'Product title',
            'type' => 'string',
            'required' => true,
            'grouping' => 'Product Details',
        ],
    ];

    $mockFields = collect([
        (object) [
            'field_name' => 'item_name',
            'display_name' => 'Title',
            'tooltip' => 'Product title',
            'required' => true,
            'grouping' => 'Product Details',
        ],
    ]);

    $this->amazonService
        ->shouldReceive('getListingRequirements')
        ->with('LUGGAGE')
        ->once()
        ->andReturn($mockRequirements);

    $this->amazonService
        ->shouldReceive('generateFormFields')
        ->with($mockRequirements)
        ->once()
        ->andReturn($mockFields);

    $response = $this->postJson('/api/amazon-listing/listing-requirements', [
        'product_type' => 'LUGGAGE',
    ]);

    expect($response->status())->toBe(200);
    $response->assertJsonStructure([
        'data' => [
            'html',
            'fields',
            'remove_attributes',
        ],
    ]);

    $responseData = $response->json('data');
    expect($responseData['html'])->toContain('Product Details');
    expect($responseData['html'])->toContain('Title');
    expect($responseData['fields'])->toBeArray();
    expect($responseData['remove_attributes'])->toBeArray();
});

it('includes maps to information in form fields', function (): void {
    $mockRequirements = [
        [
            'name' => 'item_name',
            'displayName' => 'Product Title',
            'description' => 'The title of your product',
            'type' => 'string',
            'required' => true,
            'grouping' => 'Product Details',
        ],
        [
            'name' => 'brand',
            'displayName' => 'Brand Name',
            'description' => 'The brand of your product',
            'type' => 'string',
            'required' => true,
            'grouping' => 'Product Details',
        ],
    ];

    $mockFields = collect([
        (object) [
            'field_name' => 'item_name',
            'display_name' => 'Product Title',
            'tooltip' => 'The title of your product',
            'required' => true,
            'input_type' => 'text',
            'grouping' => 'Product Details',
        ],
        (object) [
            'field_name' => 'brand',
            'display_name' => 'Brand Name',
            'tooltip' => 'The brand of your product',
            'required' => true,
            'input_type' => 'text',
            'grouping' => 'Product Details',
        ],
    ]);

    $this->amazonService
        ->shouldReceive('getListingRequirements')
        ->with('LUGGAGE')
        ->once()
        ->andReturn($mockRequirements);

    $this->amazonService
        ->shouldReceive('generateFormFields')
        ->with($mockRequirements)
        ->once()
        ->andReturn($mockFields);

    $response = $this->postJson('/api/amazon-listing/listing-requirements', [
        'product_type' => 'LUGGAGE',
    ]);

    expect($response->status())->toBe(200);
    $responseData = $response->json('data');

    // Verify that the HTML contains "Maps To" information for Amazon attributes
    expect($responseData['html'])->toContain('Maps To: <code>item_name</code>');
    expect($responseData['html'])->toContain('Maps To: <code>brand</code>');

    // Verify that both tooltip and maps to information are present
    expect($responseData['html'])->toContain('The title of your product');
    expect($responseData['html'])->toContain('The brand of your product');
});

it('validates listing requirements product type', function (): void {
    $response = $this->postJson('/api/amazon-listing/listing-requirements', [
        'product_type' => '',
    ]);

    expect($response->status())->toBe(422);
    $response->assertJsonValidationErrors(['product_type']);
});

it('can get existing listing', function (): void {
    $mockListing = [
        'asin' => 'B123456789',
        'title' => 'Blue Suitcase',
        'productTypes' => [['name' => 'LUGGAGE']],
        'attributes' => [],
        'salesRank' => [],
    ];

    $mockRequirements = [
        [
            'name' => 'item_name',
            'displayName' => 'Title',
            'type' => 'string',
            'required' => true,
        ],
    ];

    $mockFields = collect([
        (object) [
            'field_name' => 'item_name',
            'display_name' => 'Title',
        ],
    ]);

    $this->amazonService
        ->shouldReceive('getExistingListing')
        ->with('B123456789', 'ASIN')
        ->once()
        ->andReturn($mockListing);

    $this->amazonService
        ->shouldReceive('getListingRequirements')
        ->with('LUGGAGE')
        ->once()
        ->andReturn($mockRequirements);

    $this->amazonService
        ->shouldReceive('generateFormFields')
        ->with($mockRequirements)
        ->once()
        ->andReturn($mockFields);

    $response = $this->postJson('/api/amazon-listing/existing-listing', [
        'identifier' => 'B123456789',
        'identifier_type' => 'ASIN',
    ]);

    expect($response->status())->toBe(200);
    $response->assertJsonStructure([
        'data' => [
            'listing',
            'requirements',
            'form_fields',
            'product_type',
        ],
    ])
        ->assertJson([
            'data' => [
                'product_type' => 'LUGGAGE',
            ],
        ]);
});

it('returns 404 for non existent listing', function (): void {
    $this->amazonService
        ->shouldReceive('getExistingListing')
        ->with('NONEXISTENT', 'ASIN')
        ->once()
        ->andReturn(null);

    $response = $this->postJson('/api/amazon-listing/existing-listing', [
        'identifier' => 'NONEXISTENT',
        'identifier_type' => 'ASIN',
    ]);

    expect($response->status())->toBe(404);
    $response->assertJson([
        'error' => 'Listing not found for the provided identifier',
    ]);
});

it('validates existing listing parameters', function (): void {
    $response = $this->postJson('/api/amazon-listing/existing-listing', [
        'identifier' => '',
        'identifier_type' => 'INVALID',
    ]);

    expect($response->status())->toBe(422);
    $response->assertJsonValidationErrors(['identifier', 'identifier_type']);
});

it('can submit listing', function (): void {
    $mockListing = Mockery::mock(AmazonListing::class);
    $mockListing->id = 1;
    $mockListing->status = 'draft';
    $mockListing->shouldReceive('isValidated')->andReturn(false);

    $mockValidationSummary = [
        'validation_errors' => [],
        'missing_required_fields' => ['brand'],
        'completion_percentage' => 80,
        'completed_fields' => 4,
        'total_fields' => 5,
    ];

    $this->formProcessor
        ->shouldReceive('processFormSubmission')
        ->with(
            ['item_name' => 'Blue Suitcase'],
            'LUGGAGE',
            'ATVPDKIKX0DER'
        )
        ->once()
        ->andReturn($mockListing);

    $this->formProcessor
        ->shouldReceive('getValidationSummary')
        ->with($mockListing)
        ->once()
        ->andReturn($mockValidationSummary);

    $response = $this->postJson('/api/amazon-listing/submit', [
        'product_type' => 'LUGGAGE',
        'marketplace_id' => 'ATVPDKIKX0DER',
        'form_data' => [
            'item_name' => 'Blue Suitcase',
        ],
    ]);

    expect($response->status())->toBe(200);
    $response->assertJsonStructure([
        'success',
        'listing_id',
        'status',
        'validation_summary',
        'message',
    ])
        ->assertJson([
            'success' => true,
            'listing_id' => 1,
            'status' => 'draft',
        ]);
});

it('validates submit listing parameters', function (): void {
    $response = $this->postJson('/api/amazon-listing/submit', [
        'product_type' => '',
        'marketplace_id' => '',
        'form_data' => 'not_array',
    ]);

    expect($response->status())->toBe(422);
    $response->assertJsonValidationErrors(['product_type', 'marketplace_id', 'form_data']);
});

it('can validate listing', function (): void {
    $listing = AmazonListing::factory()->create([
        'status' => 'draft',
    ]);

    $mockValidatedListing = Mockery::mock(AmazonListing::class);
    $mockValidatedListing->id = $listing->id;
    $mockValidatedListing->status = 'validated';
    $mockValidatedListing->shouldReceive('isValidated')->andReturn(true);

    $mockValidationSummary = [
        'validation_errors' => [],
        'missing_required_fields' => [],
        'completion_percentage' => 100,
        'completed_fields' => 5,
        'total_fields' => 5,
    ];

    $this->formProcessor
        ->shouldReceive('revalidateListing')
        ->with(Mockery::type(AmazonListing::class))
        ->once()
        ->andReturn($mockValidatedListing);

    $this->formProcessor
        ->shouldReceive('getValidationSummary')
        ->with($mockValidatedListing)
        ->once()
        ->andReturn($mockValidationSummary);

    $response = $this->postJson('/api/amazon-listing/validate', [
        'listing_id' => $listing->id,
    ]);

    expect($response->status())->toBe(200);
    $response->assertJson([
        'success' => true,
        'status' => 'validated',
        'message' => 'Listing is valid',
    ]);
});

it('can generate csv file', function (): void {
    $listing = AmazonListing::factory()->create([
        'status' => 'validated',
    ]);

    $this->dataTransformer
        ->shouldReceive('generateCsvFile')
        ->with(Mockery::type(AmazonListing::class))
        ->once()
        ->andReturn('amazon-listings/test.csv');

    $response = $this->postJson('/api/amazon-listing/generate-file', [
        'listing_id' => $listing->id,
        'format' => 'csv',
    ]);

    expect($response->status())->toBe(200);
    $response->assertJsonStructure([
        'success',
        'file_path',
        'format',
        'download_url',
        'message',
    ])
        ->assertJson([
            'success' => true,
            'format' => 'csv',
        ]);
});

it('can generate json file', function (): void {
    $listing = AmazonListing::factory()->create([
        'status' => 'validated',
    ]);

    $this->dataTransformer
        ->shouldReceive('generateJsonFile')
        ->with(Mockery::type(AmazonListing::class))
        ->once()
        ->andReturn('amazon-listings/test.json');

    $response = $this->postJson('/api/amazon-listing/generate-file', [
        'listing_id' => $listing->id,
        'format' => 'json',
    ]);

    expect($response->status())->toBe(200);
    $response->assertJson([
        'success' => true,
        'format' => 'json',
    ]);
});

it('prevents file generation for unvalidated listing', function (): void {
    $listing = AmazonListing::factory()->create([
        'status' => 'draft',
    ]);

    $response = $this->postJson('/api/amazon-listing/generate-file', [
        'listing_id' => $listing->id,
        'format' => 'csv',
    ]);

    expect($response->status())->toBe(400);
    $response->assertJson([
        'success' => false,
        'message' => 'Listing must be validated before generating file',
    ]);
});

it('can get listing status', function (): void {
    $listing = AmazonListing::factory()->create([
        'product_type' => 'LUGGAGE',
        'marketplace_id' => 'ATVPDKIKX0DER',
        'status' => 'validated',
    ]);

    $mockValidationSummary = [
        'completion_percentage' => 100,
        'validation_errors' => [],
    ];

    $this->formProcessor
        ->shouldReceive('getValidationSummary')
        ->with(Mockery::type(AmazonListing::class))
        ->once()
        ->andReturn($mockValidationSummary);

    $response = $this->getJson("/api/amazon-listing/listings/{$listing->id}");

    expect($response->status())->toBe(200);
    $response->assertJsonStructure([
        'listing' => [
            'id',
            'status',
            'product_type',
            'marketplace_id',
            'created_at',
            'updated_at',
        ],
        'validation_summary',
    ]);
});

it('can get listings list', function (): void {
    AmazonListing::factory()->count(3)->create([
        'status' => 'validated',
    ]);

    AmazonListing::factory()->count(2)->create([
        'status' => 'draft',
    ]);

    $response = $this->getJson('/api/amazon-listing/listings');

    expect($response->status())->toBe(200);
    $response->assertJsonStructure([
        'listings',
        'pagination' => [
            'current_page',
            'last_page',
            'per_page',
            'total',
        ],
    ]);

    expect($response->json('listings'))->toHaveCount(5);
});

it('can filter listings by status', function (): void {
    AmazonListing::factory()->count(3)->create(['status' => 'validated']);
    AmazonListing::factory()->count(2)->create(['status' => 'draft']);

    $response = $this->getJson('/api/amazon-listing/listings?status=validated');

    expect($response->status())->toBe(200);
    expect($response->json('listings'))->toHaveCount(3);
});

it('can filter listings by product type', function (): void {
    AmazonListing::factory()->count(2)->create(['product_type' => 'LUGGAGE']);
    AmazonListing::factory()->count(3)->create(['product_type' => 'BACKPACK']);

    $response = $this->getJson('/api/amazon-listing/listings?product_type=LUGGAGE');

    expect($response->status())->toBe(200);
    expect($response->json('listings'))->toHaveCount(2);
});
