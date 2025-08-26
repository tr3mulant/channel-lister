<?php

namespace IGE\ChannelLister\Services;

use IGE\ChannelLister\Models\AmazonListing;
use Illuminate\Support\Facades\Validator;

class AmazonListingFormProcessor
{
    public function __construct(
        protected AmazonSpApiService $amazonService
    ) {}

    /**
     * Process and save form data for Amazon listing.
     *
     * @param  array<string, mixed>  $formData
     */
    public function processFormSubmission(array $formData, string $productType, string $marketplaceId, ?int $listingId = null): AmazonListing
    {
        // Create or update listing
        $listing = $this->createOrUpdateListing($formData, $productType, $marketplaceId, $listingId);

        // Get requirements for validation
        $requirements = $this->amazonService->getListingRequirements($productType);
        $listing->update(['requirements' => $requirements]);

        // Validate the form data
        $validationResult = $this->validateFormData($listing, $requirements);

        if ($validationResult['isValid']) {
            $listing->markAsValidated();
        } else {
            $errors = is_array($validationResult['errors']) ? $validationResult['errors'] : [];
            $listing->markAsError($errors);
        }

        return $listing;
    }

    /**
     * Create or update a listing record.
     *
     * @param  array<string, mixed>  $formData
     */
    protected function createOrUpdateListing(array $formData, string $productType, string $marketplaceId, ?int $listingId = null): AmazonListing
    {
        // If a specific listing ID is provided, update that listing (handles error status drafts)
        if ($listingId !== null && $listingId !== 0) {
            $listing = AmazonListing::findOrFail($listingId);

            // Prepare update data
            $updateData = [
                'product_type' => $productType,
                'marketplace_id' => $marketplaceId,
                'form_data' => $formData,
            ];

            // If the listing was in error status, reset it to draft and clear validation errors
            if ($listing->status === AmazonListing::STATUS_ERROR) {
                $updateData['status'] = AmazonListing::STATUS_DRAFT;
                $updateData['validation_errors'] = null; // Clear previous validation errors
            }

            $listing->fill($updateData);
            $listing->save();

            return $listing;
        }

        // Try to find existing draft by SKU or create new
        $sku = $this->extractSku($formData);

        /** @var AmazonListing|null $listing */
        $listing = null;
        if ($sku !== null && $sku !== '' && $sku !== '0') {
            $listing = AmazonListing::where('marketplace_id', $marketplaceId)
                ->where('status', AmazonListing::STATUS_DRAFT)
                ->whereJsonContains('form_data->seller_sku', $sku)
                ->first();
        }

        if ($listing === null) {
            $listing = new AmazonListing;
        }

        assert($listing instanceof AmazonListing);

        $listing->fill([
            'status' => AmazonListing::STATUS_DRAFT,
            'product_type' => $productType,
            'marketplace_id' => $marketplaceId,
            'form_data' => $formData,
        ]);

        $listing->save();

        return $listing;
    }

    /**
     * Validate form data against Amazon requirements.
     *
     * @param  array<int, array<string, mixed>>  $requirements
     * @return array<string, mixed>
     */
    public function validateFormData(AmazonListing $listing, array $requirements): array
    {
        $errors = [];
        $formData = $listing->form_data;

        foreach ($requirements as $requirement) {
            if (! is_array($requirement)) {
                continue;
            }

            $fieldName = $requirement['name'] ?? '';
            $value = data_get($formData, is_string($fieldName) ? $fieldName : '');

            // Check required fields
            if (($requirement['required'] ?? false) && empty($value)) {
                $displayName = isset($requirement['displayName']) && is_string($requirement['displayName']) ? $requirement['displayName'] : (is_string($fieldName) ? $fieldName : 'field');
                $errors[$fieldName] = "The {$displayName} field is required.";

                continue;
            }

            // Skip validation if field is empty and not required
            if (empty($value)) {
                continue;
            }

            // Validate based on field type and constraints
            $fieldErrors = is_string($fieldName) ? $this->validateField($fieldName, $value, $requirement) : null;
            if ($fieldErrors !== null && $fieldErrors !== '' && $fieldErrors !== '0') {
                $errors[$fieldName] = $fieldErrors;
            }
        }

        // Additional business logic validations
        $businessErrors = $this->validateBusinessRules($listing);
        $errors = array_merge($errors, $businessErrors);

        return [
            'isValid' => $errors === [],
            'errors' => $errors,
        ];
    }

    /**
     * Validate individual field based on its requirements.
     *
     * @param  array<string, mixed>  $requirement
     */
    protected function validateField(string $fieldName, mixed $value, array $requirement): ?string
    {
        $rules = [];
        $customMessages = [];

        // Type-based validation
        switch ($requirement['type'] ?? 'string') {
            case 'number':
            case 'integer':
                $rules[] = 'numeric';
                break;
            case 'string':
                $rules[] = 'string';
                break;
            case 'boolean':
                $rules[] = 'boolean';
                break;
        }

        // Length constraints
        if (isset($requirement['minLength'])) {
            $rules[] = 'min:'.$requirement['minLength'];
        }
        if (isset($requirement['maxLength'])) {
            $rules[] = 'max:'.$requirement['maxLength'];
        }

        // Pattern validation
        if (isset($requirement['pattern']) && is_string($requirement['pattern'])) {
            $pattern = $requirement['pattern'];
            $displayName = isset($requirement['displayName']) && is_string($requirement['displayName']) ? $requirement['displayName'] : $fieldName;
            $rules[] = "regex:/{$pattern}/";
            $customMessages[$fieldName.'.regex'] = "The {$displayName} format is invalid.";
        }

        // Enum validation
        if (isset($requirement['enum']) && is_array($requirement['enum'])) {
            $displayName = isset($requirement['displayName']) && is_string($requirement['displayName']) ? $requirement['displayName'] : $fieldName;
            $rules[] = 'in:'.implode(',', $requirement['enum']);
            $customMessages[$fieldName.'.in'] = "The {$displayName} must be one of: ".implode(', ', $requirement['enum']);
        }

        // Additional Amazon-specific validations
        $rules = array_merge($rules, $this->getAmazonSpecificRules($fieldName, $requirement));

        if ($rules === []) {
            return null;
        }

        $validator = Validator::make(
            [$fieldName => $value],
            [$fieldName => $rules],
            $customMessages
        );

        if ($validator->fails()) {
            return $validator->errors()->first($fieldName);
        }

        return null;
    }

    /**
     * Get Amazon-specific validation rules.
     *
     * @param  array<string, mixed>  $requirement
     * @return array<string>
     */
    protected function getAmazonSpecificRules(string $fieldName, array $requirement): array
    {
        $rules = [];

        // SKU validation
        if (str_contains($fieldName, 'sku')) {
            $rules[] = 'regex:/^[a-zA-Z0-9-_\.]{1,40}$/';
        }

        // UPC/GTIN validation
        if (str_contains($fieldName, 'upc') || str_contains($fieldName, 'gtin')) {
            $rules[] = 'regex:/^\d{8,14}$/';
        }

        // Price validation
        if (str_contains($fieldName, 'price') || str_contains($fieldName, 'cost')) {
            $rules[] = 'numeric';
            $rules[] = 'min:0.01';
        }

        // Weight validation
        if (str_contains($fieldName, 'weight')) {
            $rules[] = 'numeric';
            $rules[] = 'min:0';
        }

        // Dimension validation
        if (str_contains($fieldName, 'length') || str_contains($fieldName, 'width') || str_contains($fieldName, 'height')) {
            $rules[] = 'numeric';
            $rules[] = 'min:0';
        }

        // URL validation
        if (str_contains($fieldName, 'url') || str_contains($fieldName, 'link')) {
            $rules[] = 'url';
        }

        return $rules;
    }

    /**
     * Validate business rules for the listing.
     */
    /**
     * @return array<string, string>
     */
    protected function validateBusinessRules(AmazonListing $listing): array
    {
        $errors = [];
        $formData = $listing->form_data;

        // Check for duplicate SKU in same marketplace
        $sku = $listing->getSku();
        if ($sku !== null && $sku !== '' && $sku !== '0') {
            $existingListing = AmazonListing::where('marketplace_id', $listing->marketplace_id)
                ->where('id', '!=', $listing->id)
                ->whereJsonContains('form_data->seller_sku', $sku)
                ->where('status', '!=', AmazonListing::STATUS_ERROR)
                ->first();

            if ($existingListing) {
                $errors['seller_sku'] = "A listing with SKU '{$sku}' already exists in this marketplace.";
            }
        }

        // Price vs cost validation
        $price = $this->extractNumericValue($formData, ['price', 'standard_price', 'list_price']);
        $cost = $this->extractNumericValue($formData, ['cost', 'item_cost', 'wholesale_price']);

        if ($price && $cost && $price <= $cost) {
            $errors['price'] = 'Selling price should be higher than cost.';
        }

        // Dimension consistency
        $weight = $this->extractNumericValue($formData, ['item_weight', 'package_weight', 'weight']);
        $length = $this->extractNumericValue($formData, ['item_length', 'package_length', 'length']);
        $width = $this->extractNumericValue($formData, ['item_width', 'package_width', 'width']);
        $height = $this->extractNumericValue($formData, ['item_height', 'package_height', 'height']);

        if ($weight && ($length || $width || $height)) {
            // Very basic sanity check - large items should have weight
            $volume = ($length ?? 1) * ($width ?? 1) * ($height ?? 1);
            if ($volume > 1000 && $weight < 0.1) { // Large volume but very light
                $errors['item_weight'] = 'Weight seems too light for the specified dimensions.';
            }
        }

        return $errors;
    }

    /**
     * Extract SKU from form data.
     */
    /**
     * @param  array<string, mixed>  $formData
     */
    protected function extractSku(array $formData): ?string
    {
        $skuFields = ['seller_sku', 'sku', 'merchant_sku', 'external_product_id'];

        foreach ($skuFields as $field) {
            if (! empty($formData[$field]) && is_string($formData[$field])) {
                return $formData[$field];
            }
        }

        return null;
    }

    /**
     * Extract numeric value from form data using multiple possible field names.
     */
    /**
     * @param  array<string, mixed>  $formData
     * @param  array<string>  $possibleFields
     */
    protected function extractNumericValue(array $formData, array $possibleFields): ?float
    {
        foreach ($possibleFields as $field) {
            if (isset($formData[$field]) && is_numeric($formData[$field])) {
                return (float) $formData[$field];
            }
        }

        return null;
    }

    /**
     * Get validation summary for a listing.
     */
    /**
     * @return array<string, mixed>
     */
    public function getValidationSummary(AmazonListing $listing): array
    {
        $summary = [
            'status' => $listing->status,
            'completion_percentage' => $listing->getCompletionPercentage(),
            'missing_required_fields' => $listing->getMissingRequiredFields(),
            'validation_errors' => $listing->validation_errors ?? [],
            'total_fields' => count($listing->requirements ?? []),
            'completed_fields' => 0,
        ];

        if ($listing->requirements) {
            foreach ($listing->requirements as $requirement) {
                if (! empty($listing->getFormField($requirement['name']))) {
                    $summary['completed_fields']++;
                }
            }
        }

        return $summary;
    }

    /**
     * Revalidate an existing listing.
     */
    public function revalidateListing(AmazonListing $listing): AmazonListing
    {
        if (! $listing->requirements) {
            // Fetch requirements if not cached
            $requirements = $this->amazonService->getListingRequirements($listing->product_type);
            $listing->update(['requirements' => $requirements]);
        }

        $requirements = $listing->requirements ?? [];
        $validationResult = $this->validateFormData($listing, $requirements);

        if ($validationResult['isValid']) {
            $listing->markAsValidated();
        } else {
            $errors = is_array($validationResult['errors']) ? $validationResult['errors'] : [];
            $listing->markAsError($errors);
        }

        return $listing->fresh() ?? $listing;
    }
}
