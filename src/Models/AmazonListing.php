<?php

namespace IGE\ChannelLister\Models;

use IGE\ChannelLister\Database\Factories\AmazonListingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $status
 * @property string $product_type
 * @property string $marketplace_id
 * @property array $form_data
 * @property array|null $requirements
 * @property array|null $validation_errors
 * @property string|null $file_path
 * @property string|null $file_format
 * @property string|null $submission_response
 * @property \Carbon\Carbon|null $submitted_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<AmazonListing> byStatus(string $status)
 * @method static \Illuminate\Database\Eloquent\Builder<AmazonListing> byProductType(string $productType)
 * @method static \Illuminate\Database\Eloquent\Builder<AmazonListing> recent(int $days = 7)
 * @method static \Illuminate\Database\Eloquent\Builder<AmazonListing> where(string $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 * @method static AmazonListing findOrFail(mixed $id)
 * @method static AmazonListing create(array $attributes)
 */
class AmazonListing extends Model
{
    /** @use HasFactory<AmazonListingFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'channel_lister_amazon_listings';

    protected $fillable = [
        'status',
        'product_type',
        'marketplace_id',
        'form_data',
        'requirements',
        'validation_errors',
        'file_path',
        'file_format',
        'submission_response',
        'submitted_at',
    ];

    /**
     * Casts for the model
     *
     * @var array{'form_data': string,'requirements': string,'validation_errors': string,'submitted_at': string}
     *                                                                                                           }
     */
    protected $casts = [
        'form_data' => 'array',
        'requirements' => 'array',
        'validation_errors' => 'array',
        'submitted_at' => 'datetime',
    ];

    // Status constants
    public const STATUS_DRAFT = 'draft';

    public const STATUS_VALIDATING = 'validating';

    public const STATUS_VALIDATED = 'validated';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_ERROR = 'error';

    // File format constants
    public const FORMAT_CSV = 'csv';

    public const FORMAT_JSON = 'json';

    public const FORMAT_XML = 'xml';

    /**
     * Check if the listing is in draft status.
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Check if the listing is validated and ready for submission.
     */
    public function isValidated(): bool
    {
        return $this->status === self::STATUS_VALIDATED;
    }

    /**
     * Check if the listing has been submitted.
     */
    public function isSubmitted(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    /**
     * Check if the listing has validation errors.
     */
    public function hasErrors(): bool
    {
        return $this->status === self::STATUS_ERROR || ! empty($this->validation_errors);
    }

    /**
     * Mark the listing as validating.
     */
    public function markAsValidating(): void
    {
        $this->update(['status' => self::STATUS_VALIDATING]);
    }

    /**
     * Mark the listing as validated.
     */
    public function markAsValidated(): void
    {
        $this->update([
            'status' => self::STATUS_VALIDATED,
            'validation_errors' => null,
        ]);
    }

    /**
     * Mark the listing as having errors.
     *
     * @param  array<string, string>  $errors
     */
    public function markAsError(array $errors): void
    {
        $this->update([
            'status' => self::STATUS_ERROR,
            'validation_errors' => $errors,
        ]);
    }

    /**
     * Mark the listing as submitted.
     */
    public function markAsSubmitted(?string $response = null): void
    {
        $this->update([
            'status' => self::STATUS_SUBMITTED,
            'submission_response' => $response,
            'submitted_at' => now(),
        ]);
    }

    /**
     * Get a form field value by name.
     *
     * @return mixed
     */
    public function getFormField(string $fieldName, mixed $default = null)
    {
        return data_get($this->form_data, $fieldName, $default);
    }

    /**
     * Set a form field value.
     */
    public function setFormField(string $fieldName, mixed $value): void
    {
        $formData = $this->form_data;
        data_set($formData, $fieldName, $value);
        $this->update(['form_data' => $formData]);
    }

    /**
     * Get the listing title from form data.
     */
    public function getTitle(): ?string
    {
        $title = $this->getFormField('item_name') ??
               $this->getFormField('title') ??
               $this->getFormField('product_title');

        return is_scalar($title) ? (string) $title : null;
    }

    /**
     * Get the SKU from form data.
     */
    public function getSku(): ?string
    {
        $sku = $this->getFormField('seller_sku') ??
               $this->getFormField('sku') ??
               $this->getFormField('merchant_sku');

        return is_scalar($sku) ? (string) $sku : null;
    }

    /**
     * Get the ASIN from form data.
     */
    public function getAsin(): ?string
    {
        $asin = $this->getFormField('standard_product_id') ??
               $this->getFormField('asin') ??
               $this->getFormField('external_product_id');

        return is_scalar($asin) ? (string) $asin : null;
    }

    /**
     * Get required fields that are missing values.
     *
     * @return list<string>
     */
    public function getMissingRequiredFields(): array
    {
        $missing = [];

        if (! $this->requirements) {
            return $missing;
        }

        foreach ($this->requirements as $requirement) {
            if (($requirement['required'] ?? false) && empty($this->getFormField($requirement['name']))) {
                $missing[] = $requirement['name'];
            }
        }

        return $missing;
    }

    /**
     * Get the completion percentage.
     */
    public function getCompletionPercentage(): int
    {
        if (! $this->requirements) {
            return 0;
        }

        $totalFields = count($this->requirements);
        $completedFields = 0;

        foreach ($this->requirements as $requirement) {
            if (! empty($this->getFormField($requirement['name']))) {
                $completedFields++;
            }
        }

        return (int) round(($completedFields / $totalFields) * 100);
    }

    /**
     * Scope to filter by status.
     *
     * @param  Builder<AmazonListing>  $query
     * @return Builder<AmazonListing>
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by product type.
     *
     * @param  Builder<AmazonListing>  $query
     * @return Builder<AmazonListing>
     */
    public function scopeByProductType(Builder $query, string $productType): Builder
    {
        return $query->where('product_type', $productType);
    }

    /**
     * Scope to get recent listings.
     *
     * @param  Builder<AmazonListing>  $query
     * @return Builder<AmazonListing>
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): AmazonListingFactory
    {
        return AmazonListingFactory::new();
    }
}
