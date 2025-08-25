<?php

namespace IGE\ChannelLister\Services;

use IGE\ChannelLister\Contracts\MarketplaceListingProvider;
use IGE\ChannelLister\Enums\InputType;
use IGE\ChannelLister\Enums\Type;
use IGE\ChannelLister\Exceptions\AmazonSpApiException;
use IGE\ChannelLister\Models\ChannelListerField;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AmazonSpApiService implements MarketplaceListingProvider
{
    protected string $baseUrl;

    protected string $marketplaceId;

    public function __construct(
        protected AmazonTokenManager $tokenManager
    ) {
        $baseUrl = config('channel-lister.amazon.sp_api_base_url', 'https://sellingpartnerapi-na.amazon.com');
        $this->baseUrl = is_string($baseUrl) ? $baseUrl : 'https://sellingpartnerapi-na.amazon.com';

        $marketplaceId = config('channel-lister.amazon.marketplace_id', 'ATVPDKIKX0DER');
        $this->marketplaceId = is_string($marketplaceId) ? $marketplaceId : 'ATVPDKIKX0DER';
    }

    public function searchProductTypes(string $query): array
    {
        // Cache product type searches with configurable TTL
        $cacheKey = 'amazon_product_types_search_'.md5($query.$this->marketplaceId);
        $ttlValue = config('channel-lister.amazon.cache.ttl.product_types_search', 3600);
        $ttl = is_int($ttlValue) ? $ttlValue : 3600;

        return cache()->remember($cacheKey, $ttl, function () use ($query): array {
            try {
                $response = $this->makeApiCall('GET', '/definitions/2020-09-01/productTypes', [
                    'keywords' => $query,
                    'marketplaceIds' => $this->marketplaceId,
                ]);

                Log::info('Amazon product types search executed (cache miss)', [
                    'query' => $query,
                    'cacheKey' => 'amazon_product_types_search_'.md5($query.$this->marketplaceId),
                ]);

                return $this->transformProductTypesResponse($response);
            } catch (AmazonSpApiException $e) {
                Log::error('Amazon SP-API product types search failed', [
                    'error' => $e->getMessage(),
                    'amazon_error_code' => $e->getAmazonErrorCode(),
                    'query' => $query,
                ]);

                return [];
            }
        });
    }

    public function getListingRequirements(string $productType): array
    {
        // Create cache key with marketplace and locale for specificity
        $cacheKey = "amazon_listing_requirements_{$productType}_{$this->marketplaceId}_en_US";
        $ttlValue = config('channel-lister.amazon.cache.ttl.listing_requirements', 86400);
        $ttl = is_int($ttlValue) ? $ttlValue : 86400;

        // Try to get from cache first with configurable TTL
        $cached = cache()->remember($cacheKey, $ttl, function () use ($productType): array {
            try {
                $response = $this->makeApiCall('GET', "/definitions/2020-09-01/productTypes/{$productType}", [
                    'marketplaceIds' => $this->marketplaceId,
                    'requirements' => 'LISTING',
                    'locale' => 'en_US',
                ]);

                // Debug logging for cache miss
                Log::info('Amazon SP-API listing requirements fetched (cache miss)', [
                    'productType' => $productType,
                    'cacheKey' => "amazon_listing_requirements_{$productType}_{$this->marketplaceId}_en_US",
                ]);

                return $this->transformListingRequirementsResponse($response);
            } catch (AmazonSpApiException $e) {
                Log::error('Amazon SP-API listing requirements failed', [
                    'error' => $e->getMessage(),
                    'amazon_error_code' => $e->getAmazonErrorCode(),
                    'productType' => $productType,
                ]);

                return [];
            }
        });

        return $cached;
    }

    public function getExistingListing(string $identifier, string $identifierType): ?array
    {
        try {
            $response = $this->makeApiCall('GET', '/catalog/2022-04-01/items', [
                'marketplaceIds' => $this->marketplaceId,
                'identifiers' => $identifier,
                'identifiersType' => strtoupper($identifierType),
                'includedData' => 'attributes,productTypes,salesRanks',
            ]);

            $existingListing = $this->transformExistingListingResponse($response);

            return isset($existingListing['asin']) ? $existingListing : null;
        } catch (AmazonSpApiException $e) {
            // 404 is expected when listing doesn't exist
            if ($e->getCode() === 404) {
                Log::info('Amazon listing not found', [
                    'identifier' => $identifier,
                    'identifierType' => $identifierType,
                ]);

                return null;
            }

            Log::error('Amazon SP-API existing listing error', [
                'error' => $e->getMessage(),
                'amazon_error_code' => $e->getAmazonErrorCode(),
                'identifier' => $identifier,
                'identifierType' => $identifierType,
            ]);

            return null;
        }
    }

    public function generateFormFields(array $requirements): Collection
    {
        $fields = collect();
        $ordering = 1;

        foreach ($requirements as $requirement) {
            $field = new ChannelListerField([
                'ordering' => $ordering++,
                'field_name' => $requirement['name'],
                'display_name' => $requirement['displayName'] ?? null,
                'tooltip' => $requirement['description'] ?? null,
                'example' => $requirement['example'] ?? null,
                'marketplace' => 'amazon',
                'input_type' => $this->mapToInputType($requirement),
                'input_type_aux' => $this->getInputTypeAux($requirement),
                'required' => $requirement['required'] ?? false,
                'grouping' => $requirement['grouping'] ?? 'Product Details',
                'type' => Type::CUSTOM,
            ]);

            $fields->push($field);
        }

        return $fields;
    }

    public function getMarketplaceName(): string
    {
        return 'amazon';
    }

    /**
     * Create a list of product types.
     *
     * @param  array<string, mixed>  $data
     * @return list<array{'id': string, 'name': string, 'description': ?string}>
     */
    protected function transformProductTypesResponse(array $data): array
    {
        $productTypes = [];

        if (isset($data['productTypes']) && is_array($data['productTypes'])) {
            foreach ($data['productTypes'] as $productType) {
                if (! is_array($productType)) {
                    continue;
                }
                $productTypes[] = [
                    'id' => is_string($productType['name'] ?? null) ? $productType['name'] : '',
                    'name' => is_string($productType['displayName'] ?? null) ? $productType['displayName'] : (is_string($productType['name'] ?? null) ? $productType['name'] : ''),
                    'description' => is_string($productType['description'] ?? null) ? $productType['description'] : null,
                ];
            }
        }

        return $productTypes;
    }

    /**
     * Create a list of listing requirements.
     *
     * @param  array<string, mixed>  $data
     * @return array<int, array<string, mixed>> Array of field requirements
     */
    protected function transformListingRequirementsResponse(array $data): array
    {
        $requirements = [];

        // Handle new API format where schema is provided as a link
        if (isset($data['schema']) && is_array($data['schema']) &&
            isset($data['schema']['link']) && is_array($data['schema']['link']) &&
            isset($data['schema']['link']['resource']) && is_string($data['schema']['link']['resource'])) {
            $schemaUrl = $data['schema']['link']['resource'];
            $schemaData = $this->getCachedSchema($schemaUrl);

            if ($schemaData && isset($schemaData['properties']) && is_array($schemaData['properties'])) {
                foreach ($schemaData['properties'] as $propertyName => $property) {
                    if (! is_array($property)) {
                        continue;
                    }
                    if (! is_string($propertyName)) {
                        continue;
                    }
                    if ($this->shouldIncludeProperty($property, $propertyName)) {
                        // Check if this is a complex nested property that needs multiple fields
                        if ($this->isComplexNestedProperty($property)) {
                            $requiredFields = isset($schemaData['required']) && is_array($schemaData['required']) ? $schemaData['required'] : [];
                            $nestedFields = $this->extractNestedFields($property, $propertyName, $requiredFields);
                            $requirements = array_merge($requirements, $nestedFields);
                        } else {
                            // Extract type and enum, handling nested array structures for Amazon boolean fields
                            $typeInfo = $this->extractTypeAndEnum($property);

                            $requirements[] = [
                                'name' => $propertyName,
                                'displayName' => $property['title'] ?? null,
                                'description' => $property['description'] ?? null,
                                'type' => $typeInfo['type'],
                                'required' => isset($schemaData['required']) && is_array($schemaData['required']) && in_array($propertyName, $schemaData['required']),
                                'enum' => $typeInfo['enum'],
                                'enumNames' => $typeInfo['enumNames'],
                                'minLength' => $property['minLength'] ?? null,
                                'maxLength' => $property['maxLength'] ?? null,
                                'pattern' => $property['pattern'] ?? null,
                                'example' => $property['examples'][0] ?? null,
                                'grouping' => $this->determineGrouping($propertyName, $property),
                            ];
                        }
                    }
                }
            }
        }
        // Fallback for old API format (direct schema in response)
        elseif (isset($data['schema']) && is_array($data['schema']) &&
                isset($data['schema']['properties']) && is_array($data['schema']['properties'])) {
            foreach ($data['schema']['properties'] as $propertyName => $property) {
                if (! is_array($property)) {
                    continue;
                }
                if (! is_string($propertyName)) {
                    continue;
                }
                if ($this->shouldIncludeProperty($property, $propertyName)) {
                    // Check if this is a complex nested property that needs multiple fields
                    if ($this->isComplexNestedProperty($property)) {
                        $requiredFields = isset($data['schema']['required']) && is_array($data['schema']['required']) ? $data['schema']['required'] : [];
                        $nestedFields = $this->extractNestedFields($property, $propertyName, $requiredFields);
                        $requirements = array_merge($requirements, $nestedFields);
                    } else {
                        // Extract type and enum, handling nested array structures for Amazon boolean fields
                        $typeInfo = $this->extractTypeAndEnum($property);

                        $requirements[] = [
                            'name' => $propertyName,
                            'displayName' => $property['title'] ?? null,
                            'description' => $property['description'] ?? null,
                            'type' => $typeInfo['type'],
                            'required' => isset($data['schema']['required']) && is_array($data['schema']['required']) && in_array($propertyName, $data['schema']['required']),
                            'enum' => $typeInfo['enum'],
                            'enumNames' => $typeInfo['enumNames'],
                            'minLength' => $property['minLength'] ?? null,
                            'maxLength' => $property['maxLength'] ?? null,
                            'pattern' => $property['pattern'] ?? null,
                            'example' => $property['examples'][0] ?? null,
                            'grouping' => $this->determineGrouping($propertyName, $property),
                        ];
                    }
                }
            }
        }

        return $requirements;
    }

    /**
     * Extract type and enum information from property, handling nested array structures.
     *
     * Amazon uses nested array structures for boolean fields like:
     * {
     *   "type": "array",
     *   "items": {
     *     "properties": {
     *       "value": {
     *         "type": "boolean",
     *         "enum": [false, true],
     *         "enumNames": ["No", "Yes"]
     *       }
     *     }
     *   }
     * }
     */
    /**
     * @param  array<string, mixed>  $property
     * @return array<string, mixed>
     */
    protected function extractTypeAndEnum(array $property): array
    {
        $propertyType = $property['type'] ?? null;

        if ($propertyType === 'array' &&
            isset($property['items']) && is_array($property['items']) &&
            isset($property['items']['properties']) && is_array($property['items']['properties']) &&
            isset($property['items']['properties']['type']) && is_array($property['items']['properties']['type']) &&
            isset($property['items']['properties']['type']['enum'])) {
            $typeProperty = $property['items']['properties']['type'];

            return [
                'type' => is_string($typeProperty['type'] ?? null) ? $typeProperty['type'] : 'string',
                'enum' => is_array($typeProperty['enum']) ? $typeProperty['enum'] : null,
                'enumNames' => is_array($typeProperty['enumNames'] ?? null) ? $typeProperty['enumNames'] : null,
            ];
        }

        if ($propertyType === 'array' &&
            isset($property['items']) && is_array($property['items']) &&
            isset($property['items']['properties']) && is_array($property['items']['properties']) &&
            isset($property['items']['properties']['value']) && is_array($property['items']['properties']['value']) &&
            isset($property['items']['properties']['value']['enum'])) {
            $valueProperty = $property['items']['properties']['value'];

            return [
                'type' => is_string($valueProperty['type'] ?? null) ? $valueProperty['type'] : 'string',
                'enum' => is_array($valueProperty['enum']) ? $valueProperty['enum'] : null,
                'enumNames' => is_array($valueProperty['enumNames'] ?? null) ? $valueProperty['enumNames'] : null,
            ];
        }

        // Standard property structure
        return [
            'type' => $property['type'] ?? 'string',
            'enum' => $property['enum'] ?? null,
            'enumNames' => $property['enumNames'] ?? null,
        ];
    }

    /**
     * Check if this property is a simple array attribute that only wraps a single value.
     *
     * Simple attributes have array structure but only contain a single "value" property
     * along with optional metadata fields that don't need form inputs.
     */
    /**
     * @param  array<string, mixed>  $property
     */
    protected function isSimpleArrayAttribute(array $property): bool
    {
        if (($property['type'] ?? null) !== 'array' ||
            ! isset($property['items']) || ! is_array($property['items']) ||
            ($property['items']['type'] ?? null) !== 'object' ||
            ! isset($property['items']['properties']) || ! is_array($property['items']['properties'])) {
            return false;
        }

        $itemProperties = $property['items']['properties'];

        // Check if it has a "value" property
        if (! isset($itemProperties['value'])) {
            return false;
        }

        // Count non-metadata properties (exclude language_tag, marketplace_id)
        $metadataFields = ['language_tag', 'marketplace_id'];
        $nonMetadataProperties = array_filter(
            array_keys($itemProperties),
            fn ($key): bool => is_string($key) && ! in_array($key, $metadataFields)
        );

        // It's simple if it only has a "value" property (plus optional metadata)
        return count($nonMetadataProperties) === 1 && in_array('value', $nonMetadataProperties);
    }

    /**
     * Check if this property is a complex nested property that needs multiple fields.
     */
    /**
     * @param  array<string, mixed>  $property
     */
    protected function isComplexNestedProperty(array $property): bool
    {
        if (($property['type'] ?? null) !== 'array' ||
            ! isset($property['items']) || ! is_array($property['items']) ||
            ($property['items']['type'] ?? null) !== 'object' ||
            ! isset($property['items']['properties']) || ! is_array($property['items']['properties'])) {
            return false;
        }

        // If it's a simple array attribute, it's not complex
        if ($this->isSimpleArrayAttribute($property)) {
            return false;
        }

        $itemProperties = $property['items']['properties'];
        $metadataFields = ['language_tag', 'marketplace_id'];

        // Count non-metadata properties
        $nonMetadataProperties = array_filter(
            array_keys($itemProperties),
            fn ($key): bool => is_string($key) && ! in_array($key, $metadataFields)
        );

        // It's complex if it has more than one non-metadata property
        return count($nonMetadataProperties) > 1;
    }

    /**
     * Extract fields from a nested property, handling both simple and complex attributes.
     */
    /**
     * @param  array<string, mixed>  $property
     * @param  array<int, string>  $requiredFields
     * @return array<int, array<string, mixed>>
     */
    protected function extractNestedFields(array $property, string $propertyName, array $requiredFields): array
    {
        $fields = [];
        $isRequired = in_array($propertyName, $requiredFields);
        $baseTitle = $property['title'] ?? ucfirst(str_replace('_', ' ', $propertyName));
        $baseGrouping = $baseTitle; // Use property title as group name

        // Handle simple array attributes (like brand with just a "value" property)
        if ($this->isSimpleArrayAttribute($property)) {
            return $this->extractSimpleArrayField($property, $propertyName, $isRequired, is_string($baseGrouping) ? $baseGrouping : 'General'); // Already returns array of arrays
        }

        // Handle complex nested properties with multiple sub-properties
        $itemProperties = [];
        if (isset($property['items']) && is_array($property['items']) &&
            isset($property['items']['properties']) && is_array($property['items']['properties'])) {
            $itemProperties = $property['items']['properties'];
        }

        if (! is_array($itemProperties)) {
            return [];
        }

        foreach ($itemProperties as $subPropertyName => $subProperty) {
            if (! is_string($subPropertyName)) {
                continue;
            }
            if (! is_array($subProperty)) {
                continue;
            }
            // Skip metadata fields that shouldn't be form inputs
            if (in_array($subPropertyName, ['marketplace_id', 'language_tag'])) {
                continue;
            }

            $extractedFields = $this->extractFieldsFromSubProperty(
                $subProperty,
                $propertyName,
                $subPropertyName,
                $isRequired,
                is_string($baseGrouping) ? $baseGrouping : 'General',
                true // Mark as complex for proper naming
            );

            $fields = array_merge($fields, $extractedFields);
        }

        return $fields;
    }

    /**
     * Extract a simple array field that only contains a single value property.
     */
    /**
     * @param  array<string, mixed>  $property
     * @return array<int, array<string, mixed>>
     */
    protected function extractSimpleArrayField(array $property, string $propertyName, bool $isRequired, string $grouping): array
    {
        $valueProperty = [];
        if (isset($property['items']) && is_array($property['items']) &&
            isset($property['items']['properties']) && is_array($property['items']['properties']) &&
            isset($property['items']['properties']['value']) && is_array($property['items']['properties']['value'])) {
            $valueProperty = $property['items']['properties']['value'];
        }
        $typeInfo = $this->extractTypeAndEnum($property);

        return [[
            'name' => $propertyName, // Use parent name directly (e.g., "brand" not "brand_value")
            'displayName' => $property['title'] ?? ucfirst(str_replace('_', ' ', $propertyName)),
            'description' => $property['description'] ?? null,
            'type' => $typeInfo['type'],
            'required' => $isRequired,
            'enum' => $typeInfo['enum'],
            'enumNames' => $typeInfo['enumNames'],
            'minLength' => $valueProperty['minLength'] ?? null,
            'maxLength' => $valueProperty['maxLength'] ?? null,
            'pattern' => $valueProperty['pattern'] ?? null,
            'example' => (isset($property['examples']) && is_array($property['examples']) && isset($property['examples'][0])) ? $property['examples'][0] : ((isset($valueProperty['examples']) && is_array($valueProperty['examples']) && isset($valueProperty['examples'][0])) ? $valueProperty['examples'][0] : null),
            'grouping' => $grouping,
        ]];
    }

    /**
     * Extract fields from a sub-property, handling various nesting patterns.
     */
    /**
     * @param  array<string, mixed>  $subProperty
     * @return array<int, array<string, mixed>>
     */
    protected function extractFieldsFromSubProperty(array $subProperty, string $parentName, string $subPropertyName, bool $isRequired, string $grouping, bool $isComplex = true): array
    {
        $fields = [];

        // Handle array sub-properties with nested structures
        if (($subProperty['type'] ?? null) === 'array' &&
            isset($subProperty['items']) && is_array($subProperty['items']) &&
            isset($subProperty['items']['properties']) && is_array($subProperty['items']['properties'])) {
            $itemProperties = $subProperty['items']['properties'];

            foreach ($itemProperties as $nestedName => $nestedProperty) {
                if (! is_string($nestedName)) {
                    continue;
                }
                if (! is_array($nestedProperty)) {
                    continue;
                }
                // Skip metadata fields
                if (in_array($nestedName, ['marketplace_id', 'language_tag'])) {
                    continue;
                }

                $fieldName = "{$parentName}_{$subPropertyName}_{$nestedName}";
                $displayName = (is_string($nestedProperty['title'] ?? null) ? $nestedProperty['title'] : null) ??
                              (is_string($subProperty['title'] ?? null) ? $subProperty['title'] : null) ??
                              ucfirst(str_replace('_', ' ', $fieldName));

                $fields[] = [
                    'name' => $fieldName,
                    'displayName' => $displayName,
                    'description' => (is_string($nestedProperty['description'] ?? null) ? $nestedProperty['description'] : null) ?? (is_string($subProperty['description'] ?? null) ? $subProperty['description'] : null),
                    'type' => is_string($nestedProperty['type'] ?? null) ? $nestedProperty['type'] : 'string',
                    'required' => $isRequired,
                    'enum' => is_array($nestedProperty['enum'] ?? null) ? $nestedProperty['enum'] : null,
                    'enumNames' => is_array($nestedProperty['enumNames'] ?? null) ? $nestedProperty['enumNames'] : null,
                    'minLength' => is_numeric($nestedProperty['minLength'] ?? null) ? $nestedProperty['minLength'] : null,
                    'maxLength' => is_numeric($nestedProperty['maxLength'] ?? null) ? $nestedProperty['maxLength'] : null,
                    'pattern' => is_string($nestedProperty['pattern'] ?? null) ? $nestedProperty['pattern'] : null,
                    'example' => (isset($nestedProperty['examples']) && is_array($nestedProperty['examples']) && isset($nestedProperty['examples'][0])) ? $nestedProperty['examples'][0] : ((isset($subProperty['examples']) && is_array($subProperty['examples']) && isset($subProperty['examples'][0])) ? $subProperty['examples'][0] : null),
                    'grouping' => $grouping,
                ];
            }
        } else {
            // Handle direct sub-properties
            $fieldName = "{$parentName}_{$subPropertyName}";
            $displayName = is_string($subProperty['title'] ?? null) ? $subProperty['title'] : ucfirst(str_replace('_', ' ', $fieldName));

            $fields[] = [
                'name' => $fieldName,
                'displayName' => $displayName,
                'description' => is_string($subProperty['description'] ?? null) ? $subProperty['description'] : null,
                'type' => is_string($subProperty['type'] ?? null) ? $subProperty['type'] : 'string',
                'required' => $isRequired,
                'enum' => is_array($subProperty['enum'] ?? null) ? $subProperty['enum'] : null,
                'enumNames' => is_array($subProperty['enumNames'] ?? null) ? $subProperty['enumNames'] : null,
                'minLength' => is_numeric($subProperty['minLength'] ?? null) ? $subProperty['minLength'] : null,
                'maxLength' => is_numeric($subProperty['maxLength'] ?? null) ? $subProperty['maxLength'] : null,
                'pattern' => is_string($subProperty['pattern'] ?? null) ? $subProperty['pattern'] : null,
                'example' => (isset($subProperty['examples']) && is_array($subProperty['examples']) && isset($subProperty['examples'][0])) ? $subProperty['examples'][0] : null,
                'grouping' => $grouping,
            ];
        }

        return $fields;
    }

    /**
     * Transform existing listing response data
     *
     * @param  array<string, mixed>  $data
     * @return array{'asin': string|null, 'title': string|null, 'productTypes': array<string>, 'attributes': array<string>, 'salesRank': array<string>}
     */
    protected function transformExistingListingResponse(array $data): array
    {
        $listing = [
            'asin' => null,
            'title' => null,
            'productTypes' => [],
            'attributes' => [],
            'salesRank' => [],
        ];

        if (isset($data['items']) && is_array($data['items']) && $data['items'] !== []) {
            $item = $data['items'][0];
            if (! is_array($item)) {
                return $listing;
            }

            $title = null;
            if (isset($item['attributes']) && is_array($item['attributes']) &&
                isset($item['attributes']['item_name']) && is_array($item['attributes']['item_name']) &&
                isset($item['attributes']['item_name'][0]) && is_array($item['attributes']['item_name'][0]) &&
                isset($item['attributes']['item_name'][0]['value']) && is_string($item['attributes']['item_name'][0]['value'])) {
                $title = $item['attributes']['item_name'][0]['value'];
            }

            // Process productTypes to extract string values
            $productTypes = [];
            if (isset($item['productTypes']) && is_array($item['productTypes'])) {
                foreach ($item['productTypes'] as $productType) {
                    if (is_string($productType)) {
                        $productTypes[] = $productType;
                    } elseif (is_array($productType) && isset($productType['name']) && is_string($productType['name'])) {
                        $productTypes[] = $productType['name'];
                    }
                }
            }

            $listing = [
                'asin' => is_string($item['asin'] ?? null) ? $item['asin'] : null,
                'title' => $title,
                'productTypes' => $productTypes,
                'attributes' => is_array($item['attributes'] ?? null) ? $item['attributes'] : [],
                'salesRank' => is_array($item['salesRanks'] ?? null) ? $item['salesRanks'] : [],
            ];
        }

        return $listing;
    }

    /**
     * @param  array<string, mixed>  $requirement
     */
    protected function mapToInputType(array $requirement): InputType
    {
        if (isset($requirement['enum'])) {
            return InputType::SELECT;
        }

        return match ($requirement['type']) {
            'string' => strlen(is_string($requirement['description'] ?? null) ? $requirement['description'] : '') > 100 ? InputType::TEXTAREA : InputType::TEXT,
            'number', 'integer' => InputType::DECIMAL,
            'boolean' => InputType::CHECKBOX,
            default => InputType::TEXT,
        };
    }

    /**
     * @param  array<string, mixed>  $requirement
     */
    protected function getInputTypeAux(array $requirement): ?string
    {
        if (isset($requirement['enum'])) {
            // For fields with enumNames, use the display names with corresponding values
            if (isset($requirement['enumNames']) && is_array($requirement['enumNames'])) {
                // Create key-value pairs: "EAN==ean||GTIN==gtin||UPC==upc"
                $options = [];
                foreach ($requirement['enumNames'] as $index => $displayName) {
                    if (! is_string($displayName)) {
                        continue;
                    }
                    $value = (is_array($requirement['enum']) && isset($requirement['enum'][$index])) ? $requirement['enum'][$index] : $index;
                    // Convert boolean values to strings for form handling
                    $valueStr = is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
                    $options[] = $displayName.'=='.$valueStr;
                }

                return implode('||', $options);
            }

            // Default enum handling for fields without enumNames
            if (is_array($requirement['enum'])) {
                $enumStrings = array_map('strval', $requirement['enum']);

                return implode('||', $enumStrings);
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $property
     */
    protected function shouldIncludeProperty(array $property, string $propertyName = ''): bool
    {
        // Skip complex nested objects that don't have clear form field mappings
        $excludePatterns = [
            'fulfillment_availability',
            'merchant_suggested_asin',
            'purchasable_offer',
        ];

        // Allow specific array-type properties that we know how to handle
        $allowedArrayProperties = [
            'externally_assigned_product_identifier',
            'supplier_declared_has_product_identifier_exemption',
        ];

        // If this is an array type we know how to handle, allow it
        if (in_array($propertyName, $allowedArrayProperties)) {
            return true;
        }

        return ! isset($property['type']) ||
               $property['type'] !== 'object' ||
               ! in_array($propertyName, $excludePatterns);
    }

    /**
     * @param  array<string, mixed>  $property
     */
    protected function determineGrouping(string $propertyName, array $property): string
    {
        if (str_contains($propertyName, 'brand') || str_contains($propertyName, 'manufacturer')) {
            return 'Brand Information';
        }

        if (str_contains($propertyName, 'dimension') || str_contains($propertyName, 'weight')) {
            return 'Physical Attributes';
        }

        if (str_contains($propertyName, 'image') || str_contains($propertyName, 'media')) {
            return 'Images & Media';
        }

        if (str_contains($propertyName, 'price') || str_contains($propertyName, 'cost')) {
            return 'Pricing';
        }

        return 'Product Details';
    }

    /**
     * Make an authenticated API call to Amazon SP-API.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    protected function makeApiCall(string $method, string $endpoint, array $params = []): array
    {
        try {
            $accessToken = $this->tokenManager->getAccessToken();

            $httpClient = Http::withHeaders([
                'Authorization' => 'Bearer '.$accessToken,
                'x-amz-access-token' => $accessToken,
                'Content-Type' => 'application/json',
                'User-Agent' => 'ChannelLister/1.0 (Language=PHP)',
            ])->timeout(30);

            $url = $this->baseUrl.$endpoint;

            $response = match (strtoupper($method)) {
                'GET' => $httpClient->get($url.'?'.http_build_query($params)),
                'POST' => $httpClient->post($url, $params),
                'PUT' => $httpClient->put($url, $params),
                'DELETE' => $httpClient->delete($url, $params),
                default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}")
            };

            if ($response->successful()) {
                $jsonData = $response->json();

                return is_array($jsonData) ? $jsonData : [];
            }

            // Handle specific error cases
            $responseData = $response->json();
            $errorData = is_array($responseData) ? $responseData : [];
            throw AmazonSpApiException::fromApiResponse($errorData, $response->status());
        } catch (AmazonSpApiException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Amazon SP-API call failed', [
                'method' => $method,
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            throw new AmazonSpApiException(
                message: 'Failed to communicate with Amazon SP-API: '.$e->getMessage(),
                code: 0,
                previous: $e,
                context: [
                    'method' => $method,
                    'endpoint' => $endpoint,
                    'params' => $params,
                ]
            );
        }
    }

    /**
     * Get token information for debugging.
     */
    /**
     * @return array<string, mixed>|null
     */
    public function getTokenInfo(): ?array
    {
        return $this->tokenManager->getTokenInfo();
    }

    /**
     * Validate the service configuration.
     */
    /**
     * @return array<int, string>
     */
    public function validateConfiguration(): array
    {
        $errors = $this->tokenManager->validateConfiguration();

        if ($this->baseUrl === '' || $this->baseUrl === '0') {
            $errors[] = 'AMAZON_SP_API_BASE_URL is required';
        }

        if ($this->marketplaceId === '' || $this->marketplaceId === '0') {
            $errors[] = 'AMAZON_MARKETPLACE_ID is required';
        }

        return $errors;
    }

    /**
     * Get cached schema data from URL with multi-level caching strategy.
     *
     * @return array<string, mixed>|null
     */
    protected function getCachedSchema(string $schemaUrl): ?array
    {
        // Get configurable cache settings
        $diskValue = config('channel-lister.amazon.cache.disk', 'local');
        $disk = is_string($diskValue) ? $diskValue : 'local';
        $ttlValue = config('channel-lister.amazon.cache.ttl.schema_files', 604800);
        $ttl = is_int($ttlValue) ? $ttlValue : 604800;
        $schemaPathValue = config('channel-lister.amazon.cache.schema_path', 'amazon-schemas');
        $schemaPath = is_string($schemaPathValue) ? $schemaPathValue : 'amazon-schemas';

        // Create cache key from URL hash
        $urlHash = md5($schemaUrl);
        $cacheKey = "amazon_schema_{$urlHash}";
        $diskPath = "{$schemaPath}/{$urlHash}.json";

        // Try Laravel cache first (fast access) with configurable TTL
        $cached = cache()->remember($cacheKey, $ttl, function () use ($schemaUrl, $diskPath, $disk) {
            // Try disk storage second (persistence)
            if (Storage::disk($disk)->exists($diskPath)) {
                try {
                    $diskData = Storage::disk($disk)->get($diskPath);
                    $schemaData = $diskData !== null ? json_decode($diskData, true) : null;

                    if ($schemaData) {
                        Log::info('Amazon schema loaded from disk cache', [
                            'url' => $schemaUrl,
                            'disk' => $disk,
                            'diskPath' => $diskPath,
                        ]);

                        return $schemaData;
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to load schema from disk cache', [
                        'url' => $schemaUrl,
                        'disk' => $disk,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Fetch from Amazon S3 as last resort
            try {
                Log::info('Fetching Amazon schema from S3 (cache miss)', [
                    'url' => $schemaUrl,
                ]);

                $schemaResponse = Http::timeout(30)->get($schemaUrl);

                if ($schemaResponse->successful()) {
                    $schemaData = $schemaResponse->json();

                    // Store to disk for persistence
                    try {
                        $jsonData = json_encode($schemaData);
                        if ($jsonData !== false) {
                            Storage::disk($disk)->put($diskPath, $jsonData);
                        }
                        Log::info('Amazon schema cached to disk', [
                            'url' => $schemaUrl,
                            'disk' => $disk,
                            'diskPath' => $diskPath,
                        ]);
                    } catch (\Exception $e) {
                        Log::warning('Failed to cache schema to disk', [
                            'url' => $schemaUrl,
                            'disk' => $disk,
                            'error' => $e->getMessage(),
                        ]);
                    }

                    return $schemaData;
                }
                Log::warning('Failed to fetch Amazon schema from URL', [
                    'url' => $schemaUrl,
                    'status' => $schemaResponse->status(),
                ]);

                return null;
            } catch (\Exception $e) {
                Log::error('Error fetching Amazon schema', [
                    'url' => $schemaUrl,
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        });

        return is_array($cached) ? $cached : null;
    }

    /**
     * Clear all Amazon schema caches.
     */
    public function clearSchemaCache(): void
    {
        $diskValue = config('channel-lister.amazon.cache.disk', 'local');
        $disk = is_string($diskValue) ? $diskValue : 'local';
        $schemaPathValue = config('channel-lister.amazon.cache.schema_path', 'amazon-schemas');
        $schemaPath = is_string($schemaPathValue) ? $schemaPathValue : 'amazon-schemas';

        // Clear Laravel cache
        $keys = cache()->get('amazon_schema_keys', []);
        if (is_array($keys)) {
            foreach ($keys as $key) {
                if (is_string($key)) {
                    cache()->forget($key);
                }
            }
        }
        cache()->forget('amazon_schema_keys');

        // Clear disk cache with configurable disk
        try {
            $files = Storage::disk($disk)->files($schemaPath);
            foreach ($files as $file) {
                Storage::disk($disk)->delete($file);
            }

            Log::info('Amazon schema cache cleared', [
                'disk' => $disk,
                'path' => $schemaPath,
                'files_cleared' => count($files),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to clear Amazon schema cache', [
                'disk' => $disk,
                'path' => $schemaPath,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Clear specific product type cache.
     */
    public function clearProductTypeCache(string $productType): void
    {
        $cacheKey = "amazon_listing_requirements_{$productType}_{$this->marketplaceId}_en_US";
        cache()->forget($cacheKey);

        Log::info('Amazon product type cache cleared', [
            'productType' => $productType,
            'cacheKey' => $cacheKey,
        ]);
    }
}
