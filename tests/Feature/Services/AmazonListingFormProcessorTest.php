<?php

use IGE\ChannelLister\Models\AmazonListing;
use IGE\ChannelLister\Services\AmazonListingFormProcessor;
use IGE\ChannelLister\Services\AmazonSpApiService;
use Mockery;

/**
 * Small helper to create a persisted AmazonListing without touching non-existent columns.
 *
 * @param  array{product_type?:string,marketplace_id?:string,status?:string,form_data?:array,requirements?:array,validation_errors?:array|null}  $attrs
 */
function makeAmazonListing(array $attrs = []): AmazonListing
{
    $listing = new AmazonListing;

    $listing->product_type = $attrs['product_type'] ?? 'ELECTRONICS';
    $listing->marketplace_id = $attrs['marketplace_id'] ?? 'ATVPDKIKX0DER';
    $listing->status = $attrs['status'] ?? AmazonListing::STATUS_DRAFT;
    $listing->form_data = $attrs['form_data'] ?? ['seller_sku' => 'SKU-TEST'];
    if (array_key_exists('requirements', $attrs)) {
        $listing->requirements = $attrs['requirements'];
    }
    if (array_key_exists('validation_errors', $attrs)) {
        $listing->validation_errors = $attrs['validation_errors'];
    }

    $listing->save();

    return $listing->fresh();
}

beforeEach(function (): void {
    $this->amazon = Mockery::mock(AmazonSpApiService::class);
    $this->processor = new AmazonListingFormProcessor($this->amazon);
});

afterEach(function (): void {
    Mockery::close();
});

describe('AmazonListingFormProcessor', function (): void {

    it('creates a new listing (no listingId) and validates successfully', function (): void {
        $requirements = [
            ['name' => 'seller_sku', 'required' => true, 'type' => 'string'],
            ['name' => 'title', 'required' => true, 'type' => 'string'],
        ];

        $this->amazon
            ->shouldReceive('getListingRequirements')
            ->once()
            ->with('BACKPACK')
            ->andReturn($requirements);

        $formData = [
            'seller_sku' => 'ABC123',
            'title' => 'Great Backpack',
        ];

        $listing = $this->processor->processFormSubmission(
            $formData,
            'BACKPACK',
            'ATVPDKIKX0DER'
        );

        expect($listing)->toBeInstanceOf(AmazonListing::class)
            ->and($listing->status)->toBe(AmazonListing::STATUS_VALIDATED)
            ->and($listing->requirements)->toEqual($requirements)
            ->and($listing->form_data['seller_sku'])->toBe('ABC123');
    });

    it('updates an existing listing by ID (and validates)', function (): void {
        $existing = makeAmazonListing([
            'product_type' => 'BACKPACK',
            'marketplace_id' => 'ATVPDKIKX0DER',
            'status' => AmazonListing::STATUS_DRAFT,
            'form_data' => ['seller_sku' => 'XYZ789', 'title' => 'Old Title'],
        ]);

        $requirements = [
            ['name' => 'seller_sku', 'required' => true, 'type' => 'string'],
            ['name' => 'title', 'required' => true, 'type' => 'string'],
        ];

        $this->amazon
            ->shouldReceive('getListingRequirements')
            ->once()
            ->with('BACKPACK')
            ->andReturn($requirements);

        $updated = $this->processor->processFormSubmission(
            ['seller_sku' => 'XYZ789', 'title' => 'New Title'],
            'BACKPACK',
            'ATVPDKIKX0DER',
            $existing->id
        );

        expect($updated->id)->toBe($existing->id)
            ->and($updated->status)->toBe(AmazonListing::STATUS_VALIDATED)
            ->and($updated->form_data['title'])->toBe('New Title');
    });

    it('resets status and clears prior validation_errors when updating an ERROR listing', function (): void {
        $errorListing = makeAmazonListing([
            'product_type' => 'ELECTRONICS',
            'marketplace_id' => 'ATVPDKIKX0DER',
            'status' => AmazonListing::STATUS_ERROR,
            'validation_errors' => ['something' => 'bad'],
            'form_data' => ['seller_sku' => 'ERR123'],
        ]);

        $requirements = [
            ['name' => 'seller_sku', 'required' => true, 'type' => 'string'],
        ];

        $this->amazon
            ->shouldReceive('getListingRequirements')
            ->once()
            ->with('ELECTRONICS')
            ->andReturn($requirements);

        $updated = $this->processor->processFormSubmission(
            ['seller_sku' => 'ERR123'],
            'ELECTRONICS',
            'ATVPDKIKX0DER',
            $errorListing->id
        );

        expect($updated->status)->toBe(AmazonListing::STATUS_VALIDATED)
            ->and(($updated->validation_errors ?? null))->toBeNull();
    });

    it('marks listing as ERROR when required field is missing', function (): void {
        $requirements = [
            ['name' => 'title', 'required' => true, 'type' => 'string', 'displayName' => 'Title'],
        ];

        $this->amazon
            ->shouldReceive('getListingRequirements')
            ->once()
            ->with('ELECTRONICS')
            ->andReturn($requirements);

        $listing = $this->processor->processFormSubmission(
            ['seller_sku' => 'ABC123'], // title missing
            'ELECTRONICS',
            'ATVPDKIKX0DER'
        );

        expect($listing->status)->toBe(AmazonListing::STATUS_ERROR)
            ->and($listing->validation_errors)->toHaveKey('title');
    });

    describe('validateFormData - field rules', function (): void {
        it('reports missing required field', function (): void {
            $listing = makeAmazonListing(['form_data' => ['seller_sku' => 'ABC123']]);
            $requirements = [
                ['name' => 'title', 'required' => true, 'type' => 'string', 'displayName' => 'Title'],
            ];

            $result = $this->processor->validateFormData($listing, $requirements);

            expect($result['isValid'])->toBeFalse()
                ->and($result['errors'])->toHaveKey('title')
                ->and($result['errors']['title'])->toContain('The Title field is required.');
        });

        it('reports type mismatch (numeric expected)', function (): void {
            $listing = makeAmazonListing(['form_data' => ['price' => 'abc']]);
            $requirements = [
                ['name' => 'price', 'type' => 'number'],
            ];

            $result = $this->processor->validateFormData($listing, $requirements);

            expect($result['isValid'])->toBeFalse()
                ->and($result['errors'])->toHaveKey('price')
                ->and($result['errors']['price'])->toContain('must be a number');
        });

        it('reports enum mismatch with custom message', function (): void {
            $listing = makeAmazonListing(['form_data' => ['condition' => 'USED']]);
            $requirements = [
                ['name' => 'condition', 'enum' => ['NEW'], 'displayName' => 'Condition'],
            ];

            $result = $this->processor->validateFormData($listing, $requirements);

            expect($result['isValid'])->toBeFalse()
                ->and($result['errors'])->toHaveKey('condition')
                ->and($result['errors']['condition'])->toBe('The Condition must be one of: NEW');
        });

        it('applies Amazon-specific SKU regex', function (): void {
            $listing = makeAmazonListing(['form_data' => ['seller_sku' => 'bad sku!']]);
            $requirements = [
                ['name' => 'seller_sku', 'type' => 'string'],
            ];

            $result = $this->processor->validateFormData($listing, $requirements);

            expect($result['isValid'])->toBeFalse()
                ->and($result['errors'])->toHaveKey('seller_sku')
                ->and($result['errors']['seller_sku'])->toContain('format is invalid');
        });

        it('applies Amazon-specific UPC/GTIN regex', function (): void {
            $listing = makeAmazonListing(['form_data' => ['upc' => '12345']]); // too short
            $requirements = [
                ['name' => 'upc', 'type' => 'string'],
            ];

            $result = $this->processor->validateFormData($listing, $requirements);

            expect($result['isValid'])->toBeFalse()
                ->and($result['errors'])->toHaveKey('upc')
                ->and($result['errors']['upc'])->toContain('format is invalid');
        });
    });

    describe('validateFormData - business rules', function (): void {
        it('flags price <= cost', function (): void {
            $listing = makeAmazonListing([
                'form_data' => ['price' => 5, 'cost' => 10],
            ]);

            $result = $this->processor->validateFormData($listing, []); // only business rules

            expect($result['isValid'])->toBeFalse()
                ->and($result['errors'])->toHaveKey('price')
                ->and($result['errors']['price'])->toBe('Selling price should be higher than cost.');
        });

        it('flags unrealistic weight vs dimensions', function (): void {
            $listing = makeAmazonListing([
                'form_data' => [
                    'item_weight' => 0.05,      // very light
                    'item_length' => 20,
                    'item_width' => 20,
                    'item_height' => 3,         // volume = 1200 (>1000)
                ],
            ]);

            $result = $this->processor->validateFormData($listing, []);

            expect($result['isValid'])->toBeFalse()
                ->and($result['errors'])->toHaveKey('item_weight')
                ->and($result['errors']['item_weight'])->toBe('Weight seems too light for the specified dimensions.');
        });

        it('detects duplicate SKU in same marketplace (excluding self and non-error records)', function (): void {
            // Existing listing with same SKU in same marketplace, not ERROR
            makeAmazonListing([
                'marketplace_id' => 'US',
                'status' => 'submitted',
                'form_data' => ['seller_sku' => 'DUP1'],
            ]);

            // New listing with same SKU
            $current = makeAmazonListing([
                'marketplace_id' => 'US',
                'status' => AmazonListing::STATUS_DRAFT,
                'form_data' => ['seller_sku' => 'DUP1'],
            ]);

            $result = $this->processor->validateFormData($current, []);

            expect($result['isValid'])->toBeFalse()
                ->and($result['errors'])->toHaveKey('seller_sku')
                ->and($result['errors']['seller_sku'])->toBe("A listing with SKU 'DUP1' already exists in this marketplace.");
        });
    });

    describe('protected helpers via reflection', function (): void {
        it('extractSku returns seller_sku when present', function (): void {
            $ref = new ReflectionClass($this->processor);
            $m = $ref->getMethod('extractSku');
            $m->setAccessible(true);

            $result = $m->invoke($this->processor, [
                'seller_sku' => 'EX123',
                'upc' => '000000000000',
            ]);

            expect($result)->toBe('EX123');
        });

        it('extractNumericValue returns first numeric match', function (): void {
            $ref = new ReflectionClass($this->processor);
            $m = $ref->getMethod('extractNumericValue');
            $m->setAccessible(true);

            $result = $m->invoke($this->processor, ['price' => 19.99], ['list_price', 'price']);
            expect($result)->toBe(19.99);
        });
    });

    it('getValidationSummary counts completed vs total fields', function (): void {
        $listing = makeAmazonListing([
            'status' => AmazonListing::STATUS_DRAFT,
            'form_data' => ['title' => 'Tee'],
            'requirements' => [
                ['name' => 'title'],
                ['name' => 'price'],
            ],
        ]);

        $summary = $this->processor->getValidationSummary($listing);

        expect($summary)->toHaveKey('status', AmazonListing::STATUS_DRAFT)
            ->and($summary)->toHaveKey('total_fields', 2)
            ->and($summary)->toHaveKey('completed_fields', 1)
            ->and($summary)->toHaveKey('validation_errors');
    });

    describe('revalidateListing', function (): void {
        it('fetches requirements if missing and marks as VALIDATED when no errors', function (): void {
            $listing = makeAmazonListing([
                'product_type' => 'BACKPACK',
                'marketplace_id' => 'ATVPDKIKX0DER',
                'requirements' => null,
                'form_data' => ['seller_sku' => 'REVAL1', 'title' => 'Okay'],
                'status' => AmazonListing::STATUS_DRAFT,
            ]);

            $this->amazon
                ->shouldReceive('getListingRequirements')
                ->once()
                ->with('BACKPACK')
                ->andReturn([]); // no required fields

            $updated = $this->processor->revalidateListing($listing);

            expect($updated->status)->toBe(AmazonListing::STATUS_VALIDATED);
        });

        it('marks as ERROR when revalidation fails', function (): void {
            $listing = makeAmazonListing([
                'product_type' => 'LUGGAGE',
                'marketplace_id' => 'ATVPDKIKX0DER',
                'requirements' => null,
                'form_data' => ['seller_sku' => 'REVAL2'], // missing title
                'status' => AmazonListing::STATUS_DRAFT,
            ]);

            $this->amazon
                ->shouldReceive('getListingRequirements')
                ->once()
                ->with('LUGGAGE')
                ->andReturn([
                    ['name' => 'title', 'required' => true, 'type' => 'string'],
                ]);

            $updated = $this->processor->revalidateListing($listing);

            expect($updated->status)->toBe(AmazonListing::STATUS_ERROR)
                ->and($updated->validation_errors)->toHaveKey('title');
        });
    });
});
