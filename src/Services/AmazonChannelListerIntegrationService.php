<?php

namespace IGE\ChannelLister\Services;

use IGE\ChannelLister\ChannelLister;
use IGE\ChannelLister\Enums\Type;
use IGE\ChannelLister\Models\AmazonListing;
use IGE\ChannelLister\Models\ChannelListerField;
use IGE\ChannelLister\Models\ProductDraft;
use Illuminate\Support\Collection;

/**
 * Service to integrate Amazon data with the unified ChannelLister system.
 * Handles mapping between Amazon form data and Rithum custom attributes.
 */
class AmazonChannelListerIntegrationService
{
    public function __construct(
        protected AmazonSpApiService $amazonService
    ) {}

    /**
     * Convert Amazon form data to custom attributes format for Rithum export.
     *
     * @param  array  $amazonFormData  Amazon form field data
     * @return array Custom attributes in format ['field_name' => 'value']
     */
    public function mapAmazonDataToCustomAttributes(array $amazonFormData): array
    {
        $customAttributes = [];

        foreach ($amazonFormData as $fieldName => $value) {
            if (! empty($value)) {
                // Prefix with 'amazon_' to distinguish from other marketplace data
                $customAttributeName = 'amazon_'.$fieldName;
                $customAttributes[$customAttributeName] = $value;
            }
        }

        return $customAttributes;
    }

    /**
     * Create dynamic ChannelListerField entries for Amazon requirements.
     * This allows Amazon dynamic fields to be treated like regular form fields.
     *
     * @param  array  $requirements  Amazon listing requirements from API
     * @param  string  $marketplace  Target marketplace (e.g., 'amazon')
     * @return Collection<ChannelListerField>
     */
    public function createDynamicAmazonFields(array $requirements, string $marketplace = 'amazon'): Collection
    {
        $fields = collect();
        $ordering = 1000; // Start high to avoid conflicts with existing fields

        foreach ($requirements as $requirement) {
            $field = new ChannelListerField([
                'ordering' => $ordering++,
                'field_name' => 'amazon_'.$requirement['name'], // Prefix to avoid conflicts
                'display_name' => $requirement['displayName'] ?? $requirement['name'],
                'tooltip' => $requirement['description'] ?? null,
                'example' => $requirement['example'] ?? null,
                'marketplace' => $marketplace,
                'input_type' => $this->mapAmazonInputType($requirement),
                'input_type_aux' => $this->getAmazonInputTypeAux($requirement),
                'required' => $requirement['required'] ?? false,
                'grouping' => $requirement['grouping'] ?? 'Amazon Product Details',
                'type' => Type::CUSTOM,
            ]);

            $fields->push($field);
        }

        return $fields;
    }

    /**
     * Merge Amazon data into unified form data structure.
     *
     * @param  array  $unifiedFormData  Existing unified form data
     * @param  array  $amazonFormData  Amazon-specific form data
     * @return array Merged form data
     */
    public function mergeAmazonData(array $unifiedFormData, array $amazonFormData): array
    {
        // Store Amazon data in its own section
        $unifiedFormData['amazon'] = $amazonFormData;

        // Also merge Amazon data as custom attributes for backward compatibility
        $customAttributes = $this->mapAmazonDataToCustomAttributes($amazonFormData);

        if (! isset($unifiedFormData['custom_attributes'])) {
            $unifiedFormData['custom_attributes'] = [];
        }

        $unifiedFormData['custom_attributes'] = array_merge(
            $unifiedFormData['custom_attributes'],
            $customAttributes
        );

        return $unifiedFormData;
    }

    /**
     * Convert Amazon listing to ProductDraft format.
     *
     * @param  AmazonListing  $amazonListing  Existing Amazon listing
     * @return array ProductDraft data structure
     */
    public function convertAmazonListingToProductDraft(AmazonListing $amazonListing): array
    {
        $formData = [
            'amazon' => $amazonListing->form_data,
            'custom_attributes' => $this->mapAmazonDataToCustomAttributes($amazonListing->form_data),
        ];

        // Try to extract common fields from Amazon data
        $commonData = $this->extractCommonFieldsFromAmazon($amazonListing->form_data);
        if ($commonData !== []) {
            $formData['common'] = $commonData;
        }

        return [
            'form_data' => $formData,
            'status' => $this->mapAmazonStatusToProductDraftStatus($amazonListing->status),
            'validation_errors' => $amazonListing->validation_errors,
            'export_formats' => [ProductDraft::FORMAT_AMAZON, ProductDraft::FORMAT_RITHUM],
            'title' => $amazonListing->getTitle(),
            'sku' => $amazonListing->getSku(),
        ];
    }

    /**
     * Extract common product fields from Amazon form data.
     *
     * @param  array  $amazonFormData  Amazon form data
     * @return array Common form data fields
     */
    protected function extractCommonFieldsFromAmazon(array $amazonFormData): array
    {
        $commonFields = [];

        // Map Amazon fields to common ChannelAdvisor fields
        $fieldMapping = [
            'item_name' => 'Auction Title',
            'seller_sku' => 'Inventory Number',
            'product_description' => 'Description',
            'brand' => 'Brand',
            'manufacturer' => 'Manufacturer',
            // Add more mappings as needed
        ];

        foreach ($fieldMapping as $amazonField => $commonField) {
            if (! empty($amazonFormData[$amazonField])) {
                $commonFields[$commonField] = $amazonFormData[$amazonField];
            }
        }

        return $commonFields;
    }

    /**
     * Map Amazon listing status to ProductDraft status.
     */
    protected function mapAmazonStatusToProductDraftStatus(string $amazonStatus): string
    {
        return match ($amazonStatus) {
            AmazonListing::STATUS_VALIDATED => ProductDraft::STATUS_VALIDATED,
            AmazonListing::STATUS_SUBMITTED => ProductDraft::STATUS_EXPORTED,
            default => ProductDraft::STATUS_DRAFT,
        };
    }

    /**
     * Map Amazon requirement to ChannelListerField input type.
     */
    protected function mapAmazonInputType(array $requirement): string
    {
        // Reuse the existing Amazon mapping logic
        $amazonService = $this->amazonService;
        $reflection = new \ReflectionClass($amazonService);
        $method = $reflection->getMethod('mapToInputType');
        $method->setAccessible(true);

        return $method->invoke($amazonService, $requirement)->value;
    }

    /**
     * Get input type aux for Amazon requirement.
     */
    protected function getAmazonInputTypeAux(array $requirement): ?string
    {
        // Reuse the existing Amazon mapping logic
        $amazonService = $this->amazonService;
        $reflection = new \ReflectionClass($amazonService);
        $method = $reflection->getMethod('getInputTypeAux');
        $method->setAccessible(true);

        return $method->invoke($amazonService, $requirement);
    }

    /**
     * Generate unified export data for Rithum format.
     *
     * @param  ProductDraft  $draft  Product draft
     * @return array Export data ready for ChannelLister::csvFromUnifiedData()
     */
    public function generateUnifiedExportData(ProductDraft $draft): array
    {
        $exportData = [];

        // Start with common data
        $commonData = $draft->getCommonData();
        foreach ($commonData as $fieldName => $value) {
            if (! empty($value)) {
                $exportData[$fieldName] = $value;
            }
        }

        // Add custom attributes from all marketplaces
        $customAttributes = $draft->getCustomAttributes();
        foreach ($customAttributes as $attributeName => $value) {
            if (! empty($value)) {
                $exportData[$attributeName] = $value;
            }
        }

        return $exportData;
    }

    /**
     * Generate Rithum CSV file from ProductDraft using unified data structure.
     *
     * @param  ProductDraft  $draft  Product draft
     * @return string CSV filename for download
     */
    public function generateRithumCsvFile(ProductDraft $draft): string
    {
        $exportData = $this->generateUnifiedExportData($draft);

        return ChannelLister::csvFromUnifiedData($exportData);
    }
}
