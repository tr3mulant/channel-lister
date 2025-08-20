<?php

namespace IGE\ChannelLister\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Unified product draft model for all marketplace data
 *
 * @property int $id
 * @property array $form_data
 * @property string $status
 * @property array|null $validation_errors
 * @property array|null $export_formats
 * @property string|null $title
 * @property string|null $sku
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<ProductDraft> byStatus(string $status)
 * @method static \Illuminate\Database\Eloquent\Builder<ProductDraft> recent(int $days = 7)
 * @method static ProductDraft findOrFail(mixed $id)
 * @method static ProductDraft create(array $attributes)
 */
class ProductDraft extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'channel_lister_product_drafts';

    protected $fillable = [
        'form_data',
        'status',
        'validation_errors',
        'export_formats',
        'title',
        'sku',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'form_data' => 'array',
        'validation_errors' => 'array',
        'export_formats' => 'array',
    ];

    // Status constants
    public const STATUS_DRAFT = 'draft';

    public const STATUS_VALIDATED = 'validated';

    public const STATUS_EXPORTED = 'exported';

    // Export format constants
    public const FORMAT_RITHUM = 'rithum';

    public const FORMAT_AMAZON = 'amazon';

    public const FORMAT_EBAY = 'ebay';

    public const FORMAT_ETSY = 'etsy';

    /**
     * Check if the draft is in draft status.
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Check if the draft is validated.
     */
    public function isValidated(): bool
    {
        return $this->status === self::STATUS_VALIDATED;
    }

    /**
     * Check if the draft has validation errors.
     */
    public function hasErrors(): bool
    {
        return ! empty($this->validation_errors);
    }

    /**
     * Get form data for a specific marketplace.
     *
     * @return array<string, mixed>
     */
    public function getMarketplaceData(string $marketplace): array
    {
        return $this->form_data[$marketplace] ?? [];
    }

    /**
     * Set form data for a specific marketplace.
     *
     * @param  array<string, mixed>  $data
     */
    public function setMarketplaceData(string $marketplace, array $data): void
    {
        $formData = $this->form_data;
        $formData[$marketplace] = $data;
        $this->form_data = $formData;
    }

    /**
     * Get common/shared form data.
     *
     * @return array<string, mixed>
     */
    public function getCommonData(): array
    {
        return $this->getMarketplaceData('common');
    }

    /**
     * Get Amazon-specific form data.
     *
     * @return array<string, mixed>
     */
    public function getAmazonData(): array
    {
        return $this->getMarketplaceData('amazon');
    }

    /**
     * Get the product title from form data (try common first, then marketplaces).
     */
    public function getTitleFromData(): ?string
    {
        // Try common data first
        $commonData = $this->getCommonData();
        if (! empty($commonData['Auction Title']) && is_string($commonData['Auction Title'])) {
            return $commonData['Auction Title'];
        }

        // Try Amazon data
        $amazonData = $this->getAmazonData();
        if (! empty($amazonData['item_name']) && is_string($amazonData['item_name'])) {
            return $amazonData['item_name'];
        }

        return null;
    }

    /**
     * Get the product SKU from form data (try common first, then marketplaces).
     */
    public function getSkuFromData(): ?string
    {
        // Try common data first
        $commonData = $this->getCommonData();
        if (! empty($commonData['Inventory Number']) && is_string($commonData['Inventory Number'])) {
            return $commonData['Inventory Number'];
        }

        // Try Amazon data
        $amazonData = $this->getAmazonData();
        if (! empty($amazonData['seller_sku']) && is_string($amazonData['seller_sku'])) {
            return $amazonData['seller_sku'];
        }

        return null;
    }

    /**
     * Update the title and SKU fields from form data.
     */
    public function updateIdentifiers(): void
    {
        $this->title = $this->getTitleFromData();
        $this->sku = $this->getSkuFromData();
    }

    /**
     * Get all custom attributes for Rithum export.
     * This includes marketplace-specific data that should be exported as custom attributes.
     *
     * @return array<string, mixed>
     */
    public function getCustomAttributes(): array
    {
        $customAttributes = [];

        // Process Amazon data as custom attributes
        $amazonData = $this->getAmazonData();
        foreach ($amazonData as $fieldName => $value) {
            if (! empty($value)) {
                $customAttributes['amazon_'.$fieldName] = $value;
            }
        }

        // Process eBay data as custom attributes
        $ebayData = $this->getMarketplaceData('ebay');
        foreach ($ebayData as $fieldName => $value) {
            if (! empty($value)) {
                $customAttributes['ebay_'.$fieldName] = $value;
            }
        }

        // Process other marketplace data...
        // This can be extended for Etsy, Walmart, etc.

        return $customAttributes;
    }

    /**
     * Scope to filter by status.
     *
     * @param  Builder<ProductDraft>  $query
     * @return Builder<ProductDraft>
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get recent drafts.
     *
     * @param  Builder<ProductDraft>  $query
     * @return Builder<ProductDraft>
     */
    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
