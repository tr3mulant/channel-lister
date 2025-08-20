<?php

namespace IGE\ChannelLister\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShippingCalculatorService
{
    private readonly ?string $apiKey;

    private readonly string $baseUrl;

    public function __construct()
    {
        $apiKeyConfig = config('channel-lister.shipstation.api_key');
        $this->apiKey = is_string($apiKeyConfig) ? $apiKeyConfig : null;

        $baseUrlConfig = config('channel-lister.shipstation.base_url', 'https://api.shipengine.com/v1');
        $this->baseUrl = is_string($baseUrlConfig) ? $baseUrlConfig : 'https://api.shipengine.com/v1';
    }

    /**
     * Check if API key is available for calculations
     */
    public function hasApiKey(): bool
    {
        return $this->apiKey !== null && $this->apiKey !== '' && $this->apiKey !== '0';
    }

    /**
     * Get user location from IP address - simplified version without external dependencies
     *
     * @return array{success: bool, city?: string, state?: string, zip_code?: string, country?: string, latitude?: float, longitude?: float, is_default?: bool, message?: string, error?: string}
     */
    public function getLocationFromIP(?string $ipAddress = null): array
    {
        try {
            // For now, just return default location (Bellingham, WA)
            // This can be enhanced later once the location package is properly configured
            Log::info('Location detection requested for IP: '.($ipAddress !== null && $ipAddress !== '' && $ipAddress !== '0' ? $ipAddress : 'current'));

            return [
                'success' => true,
                'city' => 'Bellingham',
                'state' => 'Washington',
                'zip_code' => '98225',
                'country' => 'United States',
                'latitude' => 48.7519,
                'longitude' => -122.4787,
                'is_default' => true,
                'message' => 'Using default location. Location detection can be configured later.',
            ];

        } catch (\Exception $e) {
            Log::error('Location detection failed: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Could not determine location',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Calculate dimensional weight for different carriers
     *
     * @return array{cubic_size: float, actual_weight: float, dimensional_weights: array<string, array{dimensional_weight: float, billable_weight: float, divisor: int}>}
     */
    public function calculateDimensionalWeight(float $length, float $width, float $height, float $actualWeight): array
    {
        $cubicSize = $length * $width * $height;

        // Carrier divisors for commercial rates
        $divisors = [
            'ups_commercial' => 139,
            'fedex' => 139,
            'usps' => 166,
        ];

        $dimensionalWeights = [];

        foreach ($divisors as $carrier => $divisor) {
            $dimWeight = $cubicSize / $divisor;
            $billableWeight = max($actualWeight, $dimWeight);

            $dimensionalWeights[$carrier] = [
                'dimensional_weight' => round($dimWeight, 2),
                'billable_weight' => round($billableWeight, 2),
                'divisor' => $divisor,
            ];
        }

        return [
            'cubic_size' => $cubicSize,
            'actual_weight' => $actualWeight,
            'dimensional_weights' => $dimensionalWeights,
        ];
    }

    /**
     * Get shipping rates from ShipStation API
     *
     * @param  array<string, mixed>  $options
     * @param  array{city: string, state: string, zip: string}  $fromAddress
     * @param  array{city: string, state: string, zip: string}  $toAddress
     * @return array{success: bool, message?: string, manual_entry_needed?: bool, rates?: array<mixed>, dimensional_data?: array<mixed>, request_id?: string|null, error?: mixed}
     */
    public function getShippingRates(array $fromAddress, array $toAddress, float $length, float $width, float $height, float $weight, array $options = []): array
    {
        // If no API key, return message to use manual entry
        if (! $this->hasApiKey()) {
            return [
                'success' => false,
                'message' => 'No API key configured. Please enter shipping cost manually.',
                'manual_entry_needed' => true,
            ];
        }

        try {
            // Calculate dimensional weights first
            $dimensionalData = $this->calculateDimensionalWeight($length, $width, $height, $weight);

            // Prepare API payload with required address fields
            $payload = [
                'rate_options' => [
                    'carrier_ids' => $options['carrier_ids'] ?? [], // Empty array gets all carriers
                ],
                'shipment' => [
                    'ship_to' => [
                        'name' => 'Recipient',
                        'address_line1' => '123 Main St',
                        'city_locality' => $toAddress['city'],
                        'state_province' => $toAddress['state'],
                        'postal_code' => $toAddress['zip'],
                        'country_code' => 'US',
                        'phone' => '555-123-4567',
                    ],
                    'ship_from' => [
                        'name' => 'Sender',
                        'address_line1' => '456 Business Ave',
                        'city_locality' => $fromAddress['city'],
                        'state_province' => $fromAddress['state'],
                        'postal_code' => $fromAddress['zip'],
                        'country_code' => 'US',
                        'phone' => '555-987-6543',
                    ],
                    'packages' => [
                        [
                            'weight' => [
                                'value' => $weight,
                                'unit' => 'pound',
                            ],
                            'dimensions' => [
                                'length' => $length,
                                'width' => $width,
                                'height' => $height,
                                'unit' => 'inch',
                            ],
                        ],
                    ],
                ],
            ];

            // Add service types filter if specified
            if (! empty($options['service_types'])) {
                $payload['rate_options']['service_codes'] = $options['service_types'];
            }

            // Make API call to ShipStation
            $response = Http::withHeaders([
                'API-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl.'/rates', $payload);

            if ($response->successful()) {
                $data = $response->json();
                $rateResponse = is_array($data) ? ($data['rate_response'] ?? []) : [];

                return [
                    'success' => true,
                    'rates' => $this->formatRates(is_array($rateResponse) ? ($rateResponse['rates'] ?? []) : []),
                    'dimensional_data' => $dimensionalData,
                    'request_id' => is_array($rateResponse) ? ($rateResponse['request_id'] ?? null) : null,
                ];
            }
            Log::error('ShipStation API Error: '.$response->body());

            return [
                'success' => false,
                'message' => 'Failed to get shipping rates. Please enter shipping cost manually.',
                'error' => $response->json(),
                'manual_entry_needed' => true,
            ];

        } catch (\Exception $e) {
            Log::error('Shipping calculation error: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Error calculating shipping rates. Please enter shipping cost manually.',
                'error' => $e->getMessage(),
                'manual_entry_needed' => true,
            ];
        }
    }

    /**
     * Format rates for consistent response
     *
     * @param  array<mixed>  $rates
     * @return array<mixed, array<'amount'|'carrier'|'carrier_delivery_days'|'currency'|'delivery_days'|'estimated_delivery_date'|'negotiated_rate'|'package_type'|'rate_id'|'service_code'|'service_name'|'zone', mixed>>
     */
    private function formatRates(array $rates): array
    {
        $formattedRates = [];

        foreach ($rates as $rate) {
            if (! is_array($rate)) {
                continue;
            }

            $shippingAmount = is_array($rate['shipping_amount'] ?? null) ? $rate['shipping_amount'] : [];

            $formattedRates[] = [
                'rate_id' => $rate['rate_id'] ?? null,
                'service_name' => $rate['service_type'] ?? 'Unknown Service',
                'carrier' => $rate['carrier_friendly_name'] ?? 'Unknown Carrier',
                'service_code' => $rate['service_code'] ?? null,
                'amount' => number_format((float) ($shippingAmount['amount'] ?? 0), 2),
                'currency' => $shippingAmount['currency'] ?? 'USD',
                'delivery_days' => $rate['delivery_days'] ?? null,
                'estimated_delivery_date' => $rate['estimated_delivery_date'] ?? null,
                'carrier_delivery_days' => $rate['carrier_delivery_days'] ?? null,
                'negotiated_rate' => $rate['negotiated_rate'] ?? false,
                'zone' => $rate['zone'] ?? null,
                'package_type' => $rate['package_type'] ?? null,
            ];
        }

        // Sort by price (ascending)
        usort($formattedRates, fn (array $a, array $b): int => floatval($a['amount']) <=> floatval($b['amount']));

        return $formattedRates;
    }

    /**
     * Validate ZIP codes
     */
    public function validateZipCode(string $zipCode): bool
    {
        return (bool) preg_match('/^\d{5}(-\d{4})?$/', $zipCode);
    }

    /**
     * Get carrier-specific rates
     *
     * @param  array{city: string, state: string, zip: string}  $fromAddress
     * @param  array{city: string, state: string, zip: string}  $toAddress
     * @return array{success: bool, message?: string, manual_entry_needed?: bool, rates?: array<mixed>, dimensional_data?: array<mixed>, request_id?: string|null, error?: mixed}
     */
    public function getCarrierRates(array $fromAddress, array $toAddress, float $length, float $width, float $height, float $weight, string $carrierCode): array
    {
        if (! $this->hasApiKey()) {
            return [
                'success' => false,
                'message' => 'No API key configured. Please enter shipping cost manually.',
                'manual_entry_needed' => true,
            ];
        }

        // Get carrier IDs dynamically from API
        $carrierIds = $this->getCarrierIdsByCode($carrierCode);

        if ($carrierIds === []) {
            return [
                'success' => false,
                'message' => 'Unable to find carrier ID for: '.$carrierCode,
                'manual_entry_needed' => true,
            ];
        }

        $options = [
            'carrier_ids' => $carrierIds,
        ];

        return $this->getShippingRates($fromAddress, $toAddress, $length, $width, $height, $weight, $options);
    }

    /**
     * Get carrier IDs for a specific carrier code (ups, fedex, usps)
     *
     * @return array<string>
     */
    private function getCarrierIdsByCode(string $carrierCode): array
    {
        $carriersResponse = $this->getAvailableCarriers();

        if (! $carriersResponse['success'] || empty($carriersResponse['carriers'])) {
            return [];
        }

        $carriers = $carriersResponse['carriers'];
        $carrierIds = [];

        // Map carrier code to friendly names that might appear in API
        $carrierMappings = [
            'ups' => ['ups', 'united parcel service'],
            'fedex' => ['fedex', 'federal express'],
            'usps' => ['usps', 'united states postal service', 'us postal service'],
        ];

        $searchTerms = $carrierMappings[strtolower($carrierCode)] ?? [strtolower($carrierCode)];

        foreach ($carriers as $carrier) {
            if (! is_array($carrier)) {
                continue;
            }

            $carrierName = strtolower((string) ($carrier['friendly_name'] ?? $carrier['carrier_name'] ?? ''));
            $carrierId = $carrier['carrier_id'] ?? null;

            if ($carrierId && $carrierName) {
                foreach ($searchTerms as $term) {
                    if (str_contains($carrierName, $term)) {
                        $carrierIds[] = $carrierId;
                        break;
                    }
                }
            }
        }

        return $carrierIds;
    }

    /**
     * Get all carrier IDs for fetching rates from all carriers
     *
     * @return array<string>
     */
    public function getAllCarrierIds(): array
    {
        $carriersResponse = $this->getAvailableCarriers();

        if (! $carriersResponse['success'] || empty($carriersResponse['carriers'])) {
            return [];
        }

        $carriers = $carriersResponse['carriers'];
        $carrierIds = [];

        foreach ($carriers as $carrier) {
            if (is_array($carrier) && isset($carrier['carrier_id'])) {
                $carrierIds[] = $carrier['carrier_id'];
            }
        }

        return $carrierIds;
    }

    /**
     * Get available carriers from ShipStation
     *
     * @return array{success: bool, message?: string, manual_entry_needed?: bool, carriers?: array<mixed>, error?: string}
     */
    public function getAvailableCarriers(): array
    {
        if (! $this->hasApiKey()) {
            return [
                'success' => false,
                'message' => 'No API key configured',
                'manual_entry_needed' => true,
            ];
        }

        try {
            $response = Http::withHeaders([
                'API-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl.'/carriers');

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'carriers' => is_array($data) ? ($data['carriers'] ?? []) : [],
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to fetch carriers',
                'manual_entry_needed' => true,
            ];

        } catch (\Exception $e) {
            Log::error('Error fetching carriers: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Error fetching available carriers',
                'error' => $e->getMessage(),
                'manual_entry_needed' => true,
            ];
        }
    }
}
