<?php

namespace IGE\ChannelLister\Services;

use IGE\ChannelLister\Models\AmazonListing;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AmazonDataTransformer
{
    /**
     * Transform listing data to Amazon feed format.
     */
    public function transformToAmazonFormat(AmazonListing $listing, string $format = 'csv'): array
    {
        $formData = $listing->form_data;
        $productType = $listing->product_type;

        // Map form fields to Amazon feed fields
        $amazonData = $this->mapFieldsToAmazonFormat($formData, $productType);

        // Apply Amazon-specific transformations
        $amazonData = $this->applyAmazonTransformations($amazonData, $productType);

        // Validate required Amazon fields
        $amazonData = $this->ensureRequiredAmazonFields($amazonData, $listing);

        return $amazonData;
    }

    /**
     * Map form fields to Amazon feed column names.
     */
    protected function mapFieldsToAmazonFormat(array $formData, string $productType): array
    {
        $mapping = $this->getFieldMapping($productType);
        $amazonData = [];

        foreach ($formData as $fieldName => $value) {
            // Use mapping if available, otherwise use field name as-is
            $amazonFieldName = $mapping[$fieldName] ?? $fieldName;

            // Skip empty values except for explicitly allowed empty fields
            if ($this->shouldIncludeField($amazonFieldName, $value)) {
                $amazonData[$amazonFieldName] = $this->transformFieldValue($amazonFieldName, $value);
            }
        }

        return $amazonData;
    }

    /**
     * Get field mapping for specific product type.
     */
    protected function getFieldMapping(string $productType): array
    {
        // Common mappings that apply to most product types
        $commonMapping = [
            'seller_sku' => 'item_sku',
            'product_title' => 'item_name',
            'item_name' => 'item_name',
            'title' => 'item_name',
            'description' => 'item_type',
            'product_description' => 'product_description',
            'brand_name' => 'brand_name',
            'manufacturer' => 'manufacturer',
            'list_price' => 'standard_price',
            'price' => 'standard_price',
            'sale_price' => 'sale_price',
            'quantity' => 'quantity',
            'main_image_url' => 'main_image_url',
            'other_image_url1' => 'other_image_url1',
            'other_image_url2' => 'other_image_url2',
            'other_image_url3' => 'other_image_url3',
            'item_weight' => 'item_weight',
            'item_length' => 'item_length',
            'item_width' => 'item_width',
            'item_height' => 'item_height',
            'package_weight' => 'package_weight',
            'package_length' => 'package_length',
            'package_width' => 'package_width',
            'package_height' => 'package_height',
            'shipping_weight' => 'shipping_weight',
            'upc' => 'external_product_id',
            'gtin' => 'external_product_id',
            'ean' => 'external_product_id',
            'isbn' => 'external_product_id',
            'condition_type' => 'condition_type',
            'condition_note' => 'condition_note',
            'fulfillment_center_id' => 'fulfillment_center_id',
            'product_tax_code' => 'product_tax_code',
        ];

        // Product type specific mappings
        $specificMappings = [
            'LUGGAGE' => [
                'item_type_keyword' => 'item_type_keyword',
                'target_audience_keyword' => 'target_audience_keyword',
                'color_name' => 'color_name',
                'size_name' => 'size_name',
                'material_type' => 'material_type',
            ],
            'APPAREL' => [
                'color_name' => 'color_name',
                'size_name' => 'size_name',
                'department_name' => 'department_name',
                'style_name' => 'style_name',
                'material_composition' => 'material_composition',
            ],
            'HOME' => [
                'color_name' => 'color_name',
                'size_name' => 'size_name',
                'material_type' => 'material_type',
                'pattern_name' => 'pattern_name',
            ],
            'ELECTRONICS' => [
                'model_name' => 'model_name',
                'model_number' => 'model_number',
                'power_source_type' => 'power_source_type',
                'battery_type' => 'battery_type',
            ],
        ];

        return array_merge($commonMapping, $specificMappings[$productType] ?? []);
    }

    /**
     * Apply Amazon-specific data transformations.
     */
    protected function applyAmazonTransformations(array $amazonData, string $productType): array
    {
        // Transform boolean values
        foreach ($amazonData as $field => $value) {
            if (is_bool($value)) {
                $amazonData[$field] = $value ? 'Yes' : 'No';
            }
        }

        // Ensure proper formatting for specific fields
        if (isset($amazonData['standard_price'])) {
            $amazonData['standard_price'] = number_format((float) $amazonData['standard_price'], 2, '.', '');
        }

        if (isset($amazonData['sale_price'])) {
            $amazonData['sale_price'] = number_format((float) $amazonData['sale_price'], 2, '.', '');
        }

        // Format dimensions with units
        $dimensionFields = ['item_length', 'item_width', 'item_height', 'package_length', 'package_width', 'package_height'];
        foreach ($dimensionFields as $field) {
            if (isset($amazonData[$field]) && is_numeric($amazonData[$field])) {
                $amazonData[$field] .= ' inches'; // Default to inches
            }
        }

        // Format weight with units
        $weightFields = ['item_weight', 'package_weight', 'shipping_weight'];
        foreach ($weightFields as $field) {
            if (isset($amazonData[$field]) && is_numeric($amazonData[$field])) {
                $amazonData[$field] .= ' pounds'; // Default to pounds
            }
        }

        // Clean up SKU - Amazon has specific requirements
        if (isset($amazonData['item_sku'])) {
            $amazonData['item_sku'] = $this->cleanSku($amazonData['item_sku']);
        }

        // Set default condition if not specified
        if (! isset($amazonData['condition_type'])) {
            $amazonData['condition_type'] = 'New';
        }

        // Product type specific transformations
        $amazonData = $this->applyProductTypeTransformations($amazonData, $productType);

        return $amazonData;
    }

    /**
     * Apply product type specific transformations.
     */
    protected function applyProductTypeTransformations(array $amazonData, string $productType): array
    {
        switch ($productType) {
            case 'APPAREL':
                // Ensure department is set for apparel
                if (! isset($amazonData['department_name']) && isset($amazonData['target_audience'])) {
                    $amazonData['department_name'] = $this->mapTargetAudienceToDepartment($amazonData['target_audience']);
                }
                break;

            case 'ELECTRONICS':
                // Electronics need specific safety warnings
                if (! isset($amazonData['safety_warning'])) {
                    $amazonData['safety_warning'] = 'No safety warnings applicable';
                }
                break;

            case 'HOME':
                // Home products often need assembly information
                if (! isset($amazonData['assembly_required'])) {
                    $amazonData['assembly_required'] = 'No';
                }
                break;
        }

        return $amazonData;
    }

    /**
     * Ensure required Amazon fields are present.
     */
    protected function ensureRequiredAmazonFields(array $amazonData, AmazonListing $listing): array
    {
        // Required fields for most Amazon listings
        $requiredFields = [
            'item_sku' => $listing->getSku() ?? 'SKU-'.Str::random(8),
            'item_name' => $listing->getTitle() ?? 'Product Title',
            'product_id' => '',
            'product_id_type' => '',
            'brand_name' => 'Generic',
            'item_type' => $listing->product_type,
            'standard_price' => '0.01',
            'quantity' => '0',
            'condition_type' => 'New',
        ];

        // Set defaults for missing required fields
        foreach ($requiredFields as $field => $defaultValue) {
            if (! isset($amazonData[$field]) || $amazonData[$field] === '') {
                $amazonData[$field] = $defaultValue;
            }
        }

        return $amazonData;
    }

    /**
     * Generate CSV file from Amazon data.
     */
    public function generateCsvFile(AmazonListing $listing): string
    {
        $amazonData = $this->transformToAmazonFormat($listing, 'csv');

        // Get column headers
        $headers = $this->getCsvHeaders($listing->product_type);

        // Create CSV content
        $csvContent = $this->arrayToCsv($headers, [$amazonData]);

        // Save file
        $filename = $this->generateFilename($listing, 'csv');
        $path = "amazon-listings/{$filename}";

        Storage::disk('local')->put($path, $csvContent);

        // Update listing with file path
        $listing->update([
            'file_path' => $path,
            'file_format' => 'csv',
        ]);

        return $path;
    }

    /**
     * Generate JSON file from Amazon data.
     */
    public function generateJsonFile(AmazonListing $listing): string
    {
        $amazonData = $this->transformToAmazonFormat($listing, 'json');

        $jsonContent = json_encode($amazonData, JSON_PRETTY_PRINT);

        $filename = $this->generateFilename($listing, 'json');
        $path = "amazon-listings/{$filename}";

        Storage::disk('local')->put($path, $jsonContent);

        $listing->update([
            'file_path' => $path,
            'file_format' => 'json',
        ]);

        return $path;
    }

    /**
     * Get CSV headers for product type.
     */
    protected function getCsvHeaders(string $productType): array
    {
        // Standard headers that apply to most product types
        $standardHeaders = [
            'item_sku',
            'item_name',
            'product_id',
            'product_id_type',
            'brand_name',
            'item_type',
            'standard_price',
            'quantity',
            'condition_type',
            'condition_note',
            'main_image_url',
            'other_image_url1',
            'other_image_url2',
            'other_image_url3',
            'item_weight',
            'item_length',
            'item_width',
            'item_height',
            'package_weight',
            'package_length',
            'package_width',
            'package_height',
            'product_description',
            'fulfillment_center_id',
            'product_tax_code',
        ];

        // Product type specific headers
        $specificHeaders = [
            'APPAREL' => ['color_name', 'size_name', 'department_name', 'style_name', 'material_composition'],
            'ELECTRONICS' => ['model_name', 'model_number', 'power_source_type', 'battery_type', 'safety_warning'],
            'HOME' => ['color_name', 'size_name', 'material_type', 'pattern_name', 'assembly_required'],
            'LUGGAGE' => ['color_name', 'size_name', 'material_type', 'item_type_keyword', 'target_audience_keyword'],
        ];

        return array_merge($standardHeaders, $specificHeaders[$productType] ?? []);
    }

    /**
     * Convert array to CSV format.
     */
    protected function arrayToCsv(array $headers, array $data): string
    {
        $output = fopen('php://temp', 'r+');

        // Write headers
        fputcsv($output, $headers);

        // Write data rows
        foreach ($data as $row) {
            $csvRow = [];
            foreach ($headers as $header) {
                $csvRow[] = $row[$header] ?? '';
            }
            fputcsv($output, $csvRow);
        }

        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        return $csvContent;
    }

    /**
     * Helper methods
     */
    protected function shouldIncludeField(string $fieldName, $value): bool
    {
        // Always include these fields even if empty
        $alwaysInclude = ['product_id', 'product_id_type', 'condition_note'];

        return ! empty($value) || in_array($fieldName, $alwaysInclude);
    }

    protected function transformFieldValue(string $fieldName, $value): string
    {
        if (is_array($value)) {
            return implode('|', $value);
        }

        return (string) $value;
    }

    protected function cleanSku(string $sku): string
    {
        // Amazon SKU requirements: 1-40 characters, alphanumeric plus - _ .
        return preg_replace('/[^a-zA-Z0-9\-_\.]/', '', substr($sku, 0, 40));
    }

    protected function mapTargetAudienceToDepartment(string $audience): string
    {
        $mapping = [
            'men' => 'mens',
            'women' => 'womens',
            'boys' => 'boys',
            'girls' => 'girls',
            'baby-boys' => 'baby-boys',
            'baby-girls' => 'baby-girls',
            'unisex' => 'unisex-adult',
        ];

        return $mapping[strtolower($audience)] ?? 'unisex-adult';
    }

    protected function generateFilename(AmazonListing $listing, string $extension): string
    {
        $sku = $listing->getSku() ?? 'listing';
        $timestamp = now()->format('Y-m-d_H-i-s');

        return "{$sku}_{$timestamp}.{$extension}";
    }
}
