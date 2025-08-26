<?php

namespace IGE\ChannelLister\Contracts;

use Illuminate\Support\Collection;

interface MarketplaceListingProvider
{
    /**
     * Search for product types based on a query string.
     *
     * @param  string  $query  The search query
     * @return list<array{'id': string, 'name': string, 'description': ?string}> Array of product types with id and name
     */
    public function searchProductTypes(string $query): array;

    /**
     * Get listing requirements for a specific product type.
     *
     * @param  string  $productType  The product type identifier
     * @return array<int, array<string, mixed>>
     */
    public function getListingRequirements(string $productType): array;

    /**
     * Get existing listing data by identifier.
     *
     * @param  string  $identifier  The product identifier (GTIN, UPC, EAN, ASIN, etc.)
     * @param  string  $identifierType  The type of identifier
     * @return array{'asin': string|null, 'title': string|null, 'productTypes': string[], 'attributes': string[], 'salesRank': string[]}|null Existing listing data or null if not found
     */
    public function getExistingListing(string $identifier, string $identifierType): ?array;

    /**
     * Generate form fields from listing requirements.
     *
     * @param  array<int, array<string, mixed>>  $requirements  Array of field requirements
     * @return Collection<int, \IGE\ChannelLister\Models\ChannelListerField>
     */
    public function generateFormFields(array $requirements): Collection;

    /**
     * Get the marketplace name this provider supports.
     *
     * @return string The marketplace name
     */
    public function getMarketplaceName(): string;
}
