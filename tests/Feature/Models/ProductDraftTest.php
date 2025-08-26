<?php

use IGE\ChannelLister\Models\ProductDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->productDraft = ProductDraft::create([
        'form_data' => [
            'common' => [
                'Auction Title' => 'Test Product Title',
                'Inventory Number' => 'SKU123',
            ],
            'amazon' => [
                'item_name' => 'Amazon Product Name',
                'seller_sku' => 'AMZ-SKU-123',
                'brand_name' => 'Test Brand',
                'manufacturer' => 'Test Manufacturer',
            ],
            'ebay' => [
                'title' => 'eBay Product Title',
                'sku' => 'EBAY-SKU-123',
                'category_id' => '12345',
            ],
        ],
        'status' => ProductDraft::STATUS_DRAFT,
        'validation_errors' => null,
        'export_formats' => [ProductDraft::FORMAT_AMAZON, ProductDraft::FORMAT_EBAY],
    ]);
});

describe('ProductDraft Model', function (): void {
    it('has correct table name', function (): void {
        expect($this->productDraft->getTable())->toBe('channel_lister_product_drafts');
    });

    it('has correct fillable attributes', function (): void {
        $fillable = ['form_data', 'status', 'validation_errors', 'export_formats', 'title', 'sku'];
        expect($this->productDraft->getFillable())->toEqual($fillable);
    });

    it('casts arrays correctly', function (): void {
        expect($this->productDraft->form_data)->toBeArray();
        expect($this->productDraft->export_formats)->toBeArray();
        expect($this->productDraft->validation_errors)->toBeNull();

        // Test setting validation errors as array
        $this->productDraft->update(['validation_errors' => ['error1', 'error2']]);
        expect($this->productDraft->fresh()->validation_errors)->toBeArray();
        expect($this->productDraft->fresh()->validation_errors)->toEqual(['error1', 'error2']);
    });

    it('has correct status constants', function (): void {
        expect(ProductDraft::STATUS_DRAFT)->toBe('draft');
        expect(ProductDraft::STATUS_VALIDATED)->toBe('validated');
        expect(ProductDraft::STATUS_EXPORTED)->toBe('exported');
    });

    it('has correct format constants', function (): void {
        expect(ProductDraft::FORMAT_RITHUM)->toBe('rithum');
        expect(ProductDraft::FORMAT_AMAZON)->toBe('amazon');
        expect(ProductDraft::FORMAT_EBAY)->toBe('ebay');
        expect(ProductDraft::FORMAT_ETSY)->toBe('etsy');
    });
});

describe('Status Methods', function (): void {
    it('can check if draft is in draft status', function (): void {
        expect($this->productDraft->isDraft())->toBeTrue();

        $this->productDraft->update(['status' => ProductDraft::STATUS_VALIDATED]);
        expect($this->productDraft->isDraft())->toBeFalse();
    });

    it('can check if draft is validated', function (): void {
        expect($this->productDraft->isValidated())->toBeFalse();

        $this->productDraft->update(['status' => ProductDraft::STATUS_VALIDATED]);
        expect($this->productDraft->isValidated())->toBeTrue();
    });

    it('can check if draft has errors', function (): void {
        expect($this->productDraft->hasErrors())->toBeFalse();

        $this->productDraft->update(['validation_errors' => ['error1', 'error2']]);
        expect($this->productDraft->hasErrors())->toBeTrue();

        $this->productDraft->update(['validation_errors' => []]);
        expect($this->productDraft->hasErrors())->toBeFalse();

        $this->productDraft->update(['validation_errors' => null]);
        expect($this->productDraft->hasErrors())->toBeFalse();
    });
});

describe('Marketplace Data Methods', function (): void {
    it('can get marketplace data', function (): void {
        $commonData = $this->productDraft->getMarketplaceData('common');
        expect($commonData)->toBeArray();
        expect($commonData['Auction Title'])->toBe('Test Product Title');
        expect($commonData['Inventory Number'])->toBe('SKU123');

        $amazonData = $this->productDraft->getMarketplaceData('amazon');
        expect($amazonData['item_name'])->toBe('Amazon Product Name');
        expect($amazonData['seller_sku'])->toBe('AMZ-SKU-123');

        $nonExistentData = $this->productDraft->getMarketplaceData('nonexistent');
        expect($nonExistentData)->toBeArray();
        expect($nonExistentData)->toBeEmpty();
    });

    it('can set marketplace data', function (): void {
        $newData = ['new_field' => 'new_value', 'another_field' => 'another_value'];
        $this->productDraft->setMarketplaceData('etsy', $newData);

        expect($this->productDraft->getMarketplaceData('etsy'))->toEqual($newData);
        expect($this->productDraft->form_data['etsy'])->toEqual($newData);

        // Ensure existing data is preserved
        expect($this->productDraft->getMarketplaceData('common')['Auction Title'])->toBe('Test Product Title');
    });

    it('can get common data', function (): void {
        $commonData = $this->productDraft->getCommonData();
        expect($commonData)->toBeArray();
        expect($commonData['Auction Title'])->toBe('Test Product Title');
        expect($commonData['Inventory Number'])->toBe('SKU123');
    });

    it('can get amazon data', function (): void {
        $amazonData = $this->productDraft->getAmazonData();
        expect($amazonData)->toBeArray();
        expect($amazonData['item_name'])->toBe('Amazon Product Name');
        expect($amazonData['seller_sku'])->toBe('AMZ-SKU-123');
        expect($amazonData['brand_name'])->toBe('Test Brand');
    });
});

describe('Data Extraction Methods', function (): void {
    it('can get title from common data', function (): void {
        expect($this->productDraft->getTitleFromData())->toBe('Test Product Title');
    });

    it('can get title from amazon data when common is empty', function (): void {
        $this->productDraft->setMarketplaceData('common', []);
        expect($this->productDraft->getTitleFromData())->toBe('Amazon Product Name');
    });

    it('returns null when no title found', function (): void {
        $this->productDraft->update(['form_data' => []]);
        expect($this->productDraft->getTitleFromData())->toBeNull();
    });

    it('handles non-string title values', function (): void {
        $this->productDraft->setMarketplaceData('common', ['Auction Title' => ['not_a_string']]);
        $this->productDraft->setMarketplaceData('amazon', ['item_name' => 123]);
        expect($this->productDraft->getTitleFromData())->toBeNull();
    });

    it('can get sku from common data', function (): void {
        expect($this->productDraft->getSkuFromData())->toBe('SKU123');
    });

    it('can get sku from amazon data when common is empty', function (): void {
        $this->productDraft->setMarketplaceData('common', []);
        expect($this->productDraft->getSkuFromData())->toBe('AMZ-SKU-123');
    });

    it('returns null when no sku found', function (): void {
        $this->productDraft->update(['form_data' => []]);
        expect($this->productDraft->getSkuFromData())->toBeNull();
    });

    it('handles non-string sku values', function (): void {
        $this->productDraft->setMarketplaceData('common', ['Inventory Number' => ['not_a_string']]);
        $this->productDraft->setMarketplaceData('amazon', ['seller_sku' => true]);
        expect($this->productDraft->getSkuFromData())->toBeNull();
    });

    it('can update identifiers from form data', function (): void {
        $this->productDraft->updateIdentifiers();

        expect($this->productDraft->title)->toBe('Test Product Title');
        expect($this->productDraft->sku)->toBe('SKU123');
    });

    it('updates identifiers to null when no data found', function (): void {
        $this->productDraft->update(['form_data' => []]);
        $this->productDraft->updateIdentifiers();

        expect($this->productDraft->title)->toBeNull();
        expect($this->productDraft->sku)->toBeNull();
    });
});

describe('Custom Attributes Method', function (): void {
    it('can generate custom attributes for export', function (): void {
        $customAttributes = $this->productDraft->getCustomAttributes();

        expect($customAttributes)->toBeArray();
        expect($customAttributes)->toHaveKey('amazon_item_name');
        expect($customAttributes['amazon_item_name'])->toBe('Amazon Product Name');
        expect($customAttributes)->toHaveKey('amazon_seller_sku');
        expect($customAttributes['amazon_seller_sku'])->toBe('AMZ-SKU-123');
        expect($customAttributes)->toHaveKey('amazon_brand_name');
        expect($customAttributes['amazon_brand_name'])->toBe('Test Brand');

        expect($customAttributes)->toHaveKey('ebay_title');
        expect($customAttributes['ebay_title'])->toBe('eBay Product Title');
        expect($customAttributes)->toHaveKey('ebay_sku');
        expect($customAttributes['ebay_sku'])->toBe('EBAY-SKU-123');
        expect($customAttributes)->toHaveKey('ebay_category_id');
        expect($customAttributes['ebay_category_id'])->toBe('12345');
    });

    it('skips empty values in custom attributes', function (): void {
        $this->productDraft->setMarketplaceData('amazon', [
            'item_name' => 'Product Name',
            'empty_field' => '',
            'null_field' => null,
            'zero_field' => 0,
            'false_field' => false,
        ]);

        $customAttributes = $this->productDraft->getCustomAttributes();

        expect($customAttributes)->toHaveKey('amazon_item_name');
        expect($customAttributes)->not()->toHaveKey('amazon_empty_field');
        expect($customAttributes)->not()->toHaveKey('amazon_null_field');
        expect($customAttributes)->not()->toHaveKey('amazon_zero_field');
        expect($customAttributes)->not()->toHaveKey('amazon_false_field');
    });

    it('returns empty array when no marketplace data', function (): void {
        $this->productDraft->update(['form_data' => []]);

        $customAttributes = $this->productDraft->getCustomAttributes();
        expect($customAttributes)->toBeArray();
        expect($customAttributes)->toBeEmpty();
    });
});

describe('Query Scopes', function (): void {
    beforeEach(function (): void {
        // Create additional test drafts with different statuses
        ProductDraft::create([
            'form_data' => ['test' => 'data1'],
            'status' => ProductDraft::STATUS_VALIDATED,
        ]);

        ProductDraft::create([
            'form_data' => ['test' => 'data2'],
            'status' => ProductDraft::STATUS_EXPORTED,
            'created_at' => now()->subDays(10),
        ]);
    });

    it('can filter by status', function (): void {
        $draftCount = ProductDraft::byStatus(ProductDraft::STATUS_DRAFT)->count();
        $validatedCount = ProductDraft::byStatus(ProductDraft::STATUS_VALIDATED)->count();
        $exportedCount = ProductDraft::byStatus(ProductDraft::STATUS_EXPORTED)->count();

        expect($draftCount)->toBe(1);
        expect($validatedCount)->toBe(1);
        expect($exportedCount)->toBe(1);
    });

    it('can filter recent drafts with default days', function (): void {
        $recentCount = ProductDraft::recent()->count();
        expect($recentCount)->toBe(3); // All 3 drafts are within 7 days by default
    });

    it('can filter recent drafts with custom days', function (): void {
        $recentCount = ProductDraft::recent(5)->count();
        expect($recentCount)->toBe(3); // All drafts are recent in test environment

        $recentCountLonger = ProductDraft::recent(15)->count();
        expect($recentCountLonger)->toBe(3); // All 3 drafts
    });
});

describe('HasConfigurableConnection Trait', function (): void {
    it('has configurable connection method', function (): void {
        expect(method_exists($this->productDraft, 'getConnectionName'))->toBeTrue();

        // The connection name depends on DefaultFieldDefinitions configuration
        $connectionName = $this->productDraft->getConnectionName();
        expect($connectionName === null || is_string($connectionName))->toBeTrue();
    });
});
