<?php

namespace IGE\ChannelLister\Tests\Feature\Services;

use IGE\ChannelLister\Services\ShippingCalculatorService;
use IGE\ChannelLister\Tests\TestCase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class ShippingCalculatorServiceTest extends TestCase
{
    protected ShippingCalculatorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ShippingCalculatorService;
    }

    // ===== API KEY TESTING =====

    public function test_has_api_key_returns_true_when_api_key_configured(): void
    {
        Config::set('channel-lister.shipstation.api_key', 'test-api-key');
        $service = new ShippingCalculatorService;

        $this->assertTrue($service->hasApiKey());
    }

    public function test_has_api_key_returns_false_when_api_key_is_null(): void
    {
        Config::set('channel-lister.shipstation.api_key', null);
        $service = new ShippingCalculatorService;

        $this->assertFalse($service->hasApiKey());
    }

    public function test_has_api_key_returns_false_when_api_key_is_empty_string(): void
    {
        Config::set('channel-lister.shipstation.api_key', '');
        $service = new ShippingCalculatorService;

        $this->assertFalse($service->hasApiKey());
    }

    public function test_has_api_key_returns_false_when_api_key_is_zero_string(): void
    {
        Config::set('channel-lister.shipstation.api_key', '0');
        $service = new ShippingCalculatorService;

        $this->assertFalse($service->hasApiKey());
    }

    // ===== LOCATION TESTING =====

    public function test_get_location_from_ip_returns_default_location(): void
    {
        $result = $this->service->getLocationFromIP();

        $this->assertTrue($result['success']);
        $this->assertEquals('Bellingham', $result['city']);
        $this->assertEquals('Washington', $result['state']);
        $this->assertEquals('98225', $result['zip_code']);
        $this->assertEquals('United States', $result['country']);
        $this->assertEquals(48.7519, $result['latitude']);
        $this->assertEquals(-122.4787, $result['longitude']);
        $this->assertTrue($result['is_default']);
        $this->assertArrayHasKey('message', $result);
    }

    public function test_get_location_from_ip_with_specific_ip(): void
    {
        $result = $this->service->getLocationFromIP('192.168.1.1');

        $this->assertTrue($result['success']);
        $this->assertEquals('Bellingham', $result['city']);
        $this->assertTrue($result['is_default']);
    }

    // ===== DIMENSIONAL WEIGHT TESTING =====

    public function test_calculate_dimensional_weight(): void
    {
        $result = $this->service->calculateDimensionalWeight(12.0, 8.0, 6.0, 2.5);

        $this->assertEquals(576.0, $result['cubic_size']); // 12 * 8 * 6
        $this->assertEquals(2.5, $result['actual_weight']);
        $this->assertArrayHasKey('dimensional_weights', $result);

        // Test UPS Commercial
        $this->assertArrayHasKey('ups_commercial', $result['dimensional_weights']);
        $upsWeight = $result['dimensional_weights']['ups_commercial'];
        $this->assertEquals(round(576 / 139, 2), $upsWeight['dimensional_weight']);
        $this->assertEquals(round(max(2.5, 576 / 139), 2), $upsWeight['billable_weight']);
        $this->assertEquals(139, $upsWeight['divisor']);

        // Test FedEx
        $this->assertArrayHasKey('fedex', $result['dimensional_weights']);
        $fedexWeight = $result['dimensional_weights']['fedex'];
        $this->assertEquals(round(576 / 139, 2), $fedexWeight['dimensional_weight']);
        $this->assertEquals(139, $fedexWeight['divisor']);

        // Test USPS
        $this->assertArrayHasKey('usps', $result['dimensional_weights']);
        $uspsWeight = $result['dimensional_weights']['usps'];
        $this->assertEquals(round(576 / 166, 2), $uspsWeight['dimensional_weight']);
        $this->assertEquals(166, $uspsWeight['divisor']);
    }

    public function test_dimensional_weight_uses_actual_weight_when_higher(): void
    {
        // Heavy but small package
        $result = $this->service->calculateDimensionalWeight(4.0, 4.0, 4.0, 10.0);

        $cubicSize = 64.0; // 4 * 4 * 4
        $this->assertEquals($cubicSize, $result['cubic_size']);
        $this->assertEquals(10.0, $result['actual_weight']);

        // For all carriers, actual weight should be higher than dimensional weight
        foreach ($result['dimensional_weights'] as $weights) {
            $this->assertEquals(10.0, $weights['billable_weight']);
            $this->assertLessThan(10.0, $weights['dimensional_weight']);
        }
    }

    public function test_dimensional_weight_uses_dimensional_weight_when_higher(): void
    {
        // Light but large package
        $result = $this->service->calculateDimensionalWeight(20.0, 20.0, 20.0, 1.0);

        $cubicSize = 8000.0; // 20 * 20 * 20
        $this->assertEquals($cubicSize, $result['cubic_size']);
        $this->assertEquals(1.0, $result['actual_weight']);

        // For all carriers, dimensional weight should be higher than actual weight
        foreach ($result['dimensional_weights'] as $weights) {
            $this->assertEquals($weights['dimensional_weight'], $weights['billable_weight']);
            $this->assertGreaterThan(1.0, $weights['dimensional_weight']);
        }
    }

    // ===== SHIPPING RATES TESTING =====

    public function test_get_shipping_rates_without_api_key(): void
    {
        Config::set('channel-lister.shipstation.api_key', null);
        $service = new ShippingCalculatorService;

        $fromAddress = ['city' => 'Bellingham', 'state' => 'WA', 'zip' => '98225'];
        $toAddress = ['city' => 'Seattle', 'state' => 'WA', 'zip' => '98101'];

        $result = $service->getShippingRates($fromAddress, $toAddress, 10.0, 8.0, 6.0, 2.0);

        $this->assertFalse($result['success']);
        $this->assertEquals('No API key configured. Please enter shipping cost manually.', $result['message']);
        $this->assertTrue($result['manual_entry_needed']);
    }

    public function test_get_shipping_rates_with_successful_api_response(): void
    {
        Config::set('channel-lister.shipstation.api_key', 'test-api-key');

        $mockResponse = [
            'rate_response' => [
                'rates' => [
                    [
                        'rate_id' => 'se-123456',
                        'service_type' => 'UPS Ground',
                        'carrier_friendly_name' => 'UPS',
                        'service_code' => 'ups_ground',
                        'shipping_amount' => ['amount' => 12.50, 'currency' => 'USD'],
                        'delivery_days' => 3,
                        'estimated_delivery_date' => '2023-12-25',
                    ],
                    [
                        'rate_id' => 'se-789012',
                        'service_type' => 'FedEx Ground',
                        'carrier_friendly_name' => 'FedEx',
                        'service_code' => 'fedex_ground',
                        'shipping_amount' => ['amount' => 15.75, 'currency' => 'USD'],
                        'delivery_days' => 2,
                    ],
                ],
                'request_id' => 'req-123',
            ],
        ];

        Http::fake([
            '*/rates' => Http::response($mockResponse, 200),
        ]);

        $service = new ShippingCalculatorService;
        $fromAddress = ['city' => 'Bellingham', 'state' => 'WA', 'zip' => '98225'];
        $toAddress = ['city' => 'Seattle', 'state' => 'WA', 'zip' => '98101'];

        $result = $service->getShippingRates($fromAddress, $toAddress, 10.0, 8.0, 6.0, 2.0);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('rates', $result);
        $this->assertArrayHasKey('dimensional_data', $result);
        $this->assertEquals('req-123', $result['request_id']);

        // Test rate formatting
        $rates = $result['rates'];
        $this->assertCount(2, $rates);

        // Rates should be sorted by price (UPS $12.50 should come first)
        $this->assertEquals('se-123456', $rates[0]['rate_id']);
        $this->assertEquals('UPS Ground', $rates[0]['service_name']);
        $this->assertEquals('UPS', $rates[0]['carrier']);
        $this->assertEquals('12.50', $rates[0]['amount']);
        $this->assertEquals('USD', $rates[0]['currency']);
        $this->assertEquals(3, $rates[0]['delivery_days']);

        $this->assertEquals('se-789012', $rates[1]['rate_id']);
        $this->assertEquals('15.75', $rates[1]['amount']);
    }

    public function test_get_shipping_rates_with_api_error_response(): void
    {
        Config::set('channel-lister.shipstation.api_key', 'test-api-key');

        Http::fake([
            '*/rates' => Http::response(['error' => 'Invalid request'], 400),
        ]);

        $service = new ShippingCalculatorService;
        $fromAddress = ['city' => 'Bellingham', 'state' => 'WA', 'zip' => '98225'];
        $toAddress = ['city' => 'Seattle', 'state' => 'WA', 'zip' => '98101'];

        $result = $service->getShippingRates($fromAddress, $toAddress, 10.0, 8.0, 6.0, 2.0);

        $this->assertFalse($result['success']);
        $this->assertEquals('Failed to get shipping rates. Please enter shipping cost manually.', $result['message']);
        $this->assertTrue($result['manual_entry_needed']);
        $this->assertArrayHasKey('error', $result);
    }

    public function test_get_shipping_rates_with_exception(): void
    {
        Config::set('channel-lister.shipstation.api_key', 'test-api-key');

        Http::fake([
            '*/rates' => function (): void {
                throw new \Exception('Network error');
            },
        ]);

        $service = new ShippingCalculatorService;
        $fromAddress = ['city' => 'Bellingham', 'state' => 'WA', 'zip' => '98225'];
        $toAddress = ['city' => 'Seattle', 'state' => 'WA', 'zip' => '98101'];

        $result = $service->getShippingRates($fromAddress, $toAddress, 10.0, 8.0, 6.0, 2.0);

        $this->assertFalse($result['success']);
        $this->assertEquals('Error calculating shipping rates. Please enter shipping cost manually.', $result['message']);
        $this->assertTrue($result['manual_entry_needed']);
        $this->assertEquals('Network error', $result['error']);
    }

    // ===== ZIP CODE VALIDATION =====

    public function test_validate_zip_code_with_valid_formats(): void
    {
        $validZips = ['12345', '12345-6789', '98225', '90210-1234'];

        foreach ($validZips as $zip) {
            $this->assertTrue($this->service->validateZipCode($zip), "Failed to validate: {$zip}");
        }
    }

    public function test_validate_zip_code_with_invalid_formats(): void
    {
        $invalidZips = ['1234', '123456', 'abcde', '12345-123', '12345-', '-12345', ''];

        foreach ($invalidZips as $zip) {
            $this->assertFalse($this->service->validateZipCode($zip), "Incorrectly validated: {$zip}");
        }
    }

    // ===== CARRIER RATES TESTING =====

    public function test_get_carrier_rates_without_api_key(): void
    {
        Config::set('channel-lister.shipstation.api_key', null);
        $service = new ShippingCalculatorService;

        $fromAddress = ['city' => 'Bellingham', 'state' => 'WA', 'zip' => '98225'];
        $toAddress = ['city' => 'Seattle', 'state' => 'WA', 'zip' => '98101'];

        $result = $service->getCarrierRates($fromAddress, $toAddress, 10.0, 8.0, 6.0, 2.0, 'ups');

        $this->assertFalse($result['success']);
        $this->assertEquals('No API key configured. Please enter shipping cost manually.', $result['message']);
        $this->assertTrue($result['manual_entry_needed']);
    }

    public function test_get_carrier_rates_with_unknown_carrier(): void
    {
        Config::set('channel-lister.shipstation.api_key', 'test-api-key');

        // Mock carriers endpoint to return empty carriers
        Http::fake([
            '*/carriers' => Http::response(['carriers' => []], 200),
        ]);

        $service = new ShippingCalculatorService;
        $fromAddress = ['city' => 'Bellingham', 'state' => 'WA', 'zip' => '98225'];
        $toAddress = ['city' => 'Seattle', 'state' => 'WA', 'zip' => '98101'];

        $result = $service->getCarrierRates($fromAddress, $toAddress, 10.0, 8.0, 6.0, 2.0, 'unknown');

        $this->assertFalse($result['success']);
        $this->assertEquals('Unable to find carrier ID for: unknown', $result['message']);
        $this->assertTrue($result['manual_entry_needed']);
    }

    // ===== AVAILABLE CARRIERS TESTING =====

    public function test_get_available_carriers_without_api_key(): void
    {
        Config::set('channel-lister.shipstation.api_key', null);
        $service = new ShippingCalculatorService;

        $result = $service->getAvailableCarriers();

        $this->assertFalse($result['success']);
        $this->assertEquals('No API key configured', $result['message']);
        $this->assertTrue($result['manual_entry_needed']);
    }

    public function test_get_available_carriers_with_successful_response(): void
    {
        Config::set('channel-lister.shipstation.api_key', 'test-api-key');

        $mockCarriers = [
            'carriers' => [
                [
                    'carrier_id' => 'se-123456',
                    'carrier_name' => 'ups',
                    'friendly_name' => 'UPS',
                ],
                [
                    'carrier_id' => 'se-789012',
                    'carrier_name' => 'fedex',
                    'friendly_name' => 'FedEx',
                ],
            ],
        ];

        Http::fake([
            '*/carriers' => Http::response($mockCarriers, 200),
        ]);

        $service = new ShippingCalculatorService;
        $result = $service->getAvailableCarriers();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('carriers', $result);
        $this->assertCount(2, $result['carriers']);
        $this->assertEquals('se-123456', $result['carriers'][0]['carrier_id']);
    }

    public function test_get_available_carriers_with_api_error(): void
    {
        Config::set('channel-lister.shipstation.api_key', 'test-api-key');

        Http::fake([
            '*/carriers' => Http::response(['error' => 'Unauthorized'], 401),
        ]);

        $service = new ShippingCalculatorService;
        $result = $service->getAvailableCarriers();

        $this->assertFalse($result['success']);
        $this->assertEquals('Failed to fetch carriers', $result['message']);
        $this->assertTrue($result['manual_entry_needed']);
    }

    public function test_get_available_carriers_with_exception(): void
    {
        Config::set('channel-lister.shipstation.api_key', 'test-api-key');

        Http::fake([
            '*/carriers' => function (): void {
                throw new \Exception('Connection timeout');
            },
        ]);

        $service = new ShippingCalculatorService;
        $result = $service->getAvailableCarriers();

        $this->assertFalse($result['success']);
        $this->assertEquals('Error fetching available carriers', $result['message']);
        $this->assertEquals('Connection timeout', $result['error']);
        $this->assertTrue($result['manual_entry_needed']);
    }

    // ===== CARRIER ID TESTING =====

    public function test_get_all_carrier_ids_with_successful_response(): void
    {
        Config::set('channel-lister.shipstation.api_key', 'test-api-key');

        $mockCarriers = [
            'carriers' => [
                ['carrier_id' => 'se-123456', 'friendly_name' => 'UPS'],
                ['carrier_id' => 'se-789012', 'friendly_name' => 'FedEx'],
                ['carrier_id' => 'se-345678', 'friendly_name' => 'USPS'],
            ],
        ];

        Http::fake([
            '*/carriers' => Http::response($mockCarriers, 200),
        ]);

        $service = new ShippingCalculatorService;
        $carrierIds = $service->getAllCarrierIds();

        $this->assertCount(3, $carrierIds);
        $this->assertContains('se-123456', $carrierIds);
        $this->assertContains('se-789012', $carrierIds);
        $this->assertContains('se-345678', $carrierIds);
    }

    public function test_get_all_carrier_ids_with_failed_carriers_request(): void
    {
        Config::set('channel-lister.shipstation.api_key', 'test-api-key');

        Http::fake([
            '*/carriers' => Http::response([], 400),
        ]);

        $service = new ShippingCalculatorService;
        $carrierIds = $service->getAllCarrierIds();

        $this->assertEmpty($carrierIds);
    }

    // ===== EDGE CASES AND DATA VALIDATION =====

    public function test_format_rates_with_missing_data(): void
    {
        Config::set('channel-lister.shipstation.api_key', 'test-api-key');

        $mockResponse = [
            'rate_response' => [
                'rates' => [
                    [
                        // Missing most fields to test defaults
                        'shipping_amount' => ['amount' => 10.00],
                    ],
                    [
                        'rate_id' => 'se-123',
                        'service_type' => 'Test Service',
                        'carrier_friendly_name' => 'Test Carrier',
                        'shipping_amount' => ['amount' => 5.00, 'currency' => 'USD'],
                    ],
                ],
            ],
        ];

        Http::fake([
            '*/rates' => Http::response($mockResponse, 200),
        ]);

        $service = new ShippingCalculatorService;
        $fromAddress = ['city' => 'Bellingham', 'state' => 'WA', 'zip' => '98225'];
        $toAddress = ['city' => 'Seattle', 'state' => 'WA', 'zip' => '98101'];

        $result = $service->getShippingRates($fromAddress, $toAddress, 10.0, 8.0, 6.0, 2.0);

        $this->assertTrue($result['success']);
        $rates = $result['rates'];

        // Should be sorted by price (5.00 first, then 10.00)
        $this->assertEquals('5.00', $rates[0]['amount']);
        $this->assertEquals('10.00', $rates[1]['amount']);

        // Test default values for missing fields
        $this->assertEquals('Unknown Service', $rates[1]['service_name']);
        $this->assertEquals('Unknown Carrier', $rates[1]['carrier']);
        $this->assertEquals('USD', $rates[1]['currency']);
    }

    public function test_dimensional_weight_with_zero_dimensions(): void
    {
        $result = $this->service->calculateDimensionalWeight(0.0, 0.0, 0.0, 5.0);

        $this->assertEquals(0.0, $result['cubic_size']);
        $this->assertEquals(5.0, $result['actual_weight']);

        // With zero cubic size, dimensional weight should be 0, so actual weight is used
        foreach ($result['dimensional_weights'] as $weights) {
            $this->assertEquals(0.0, $weights['dimensional_weight']);
            $this->assertEquals(5.0, $weights['billable_weight']);
        }
    }
}
