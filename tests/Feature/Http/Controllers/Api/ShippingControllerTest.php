<?php

namespace IGE\ChannelLister\Tests\Feature\Http\Controllers\Api;

use IGE\ChannelLister\Services\ShippingCalculatorService;
use IGE\ChannelLister\Tests\TestCase;
use Illuminate\Support\Facades\Config;
use Mockery;

class ShippingControllerTest extends TestCase
{
    protected string $baseRoute = '/api/shipping';

    // ===== CHECK API AVAILABILITY TESTS =====

    public function test_check_api_availability_with_api_key(): void
    {
        Config::set('channel-lister.shipstation.api_key', 'test-api-key');

        $response = $this->getJson("{$this->baseRoute}/check-api");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'has_api_key' => true,
                'message' => 'API calculations available',
            ]);
    }

    public function test_check_api_availability_without_api_key(): void
    {
        Config::set('channel-lister.shipstation.api_key', null);

        $response = $this->getJson("{$this->baseRoute}/check-api");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'has_api_key' => false,
                'message' => 'Manual entry required - no API key configured',
            ]);
    }

    // ===== GET LOCATION TESTS =====

    public function test_get_location_returns_service_response(): void
    {
        $response = $this->getJson("{$this->baseRoute}/location");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'city',
                'state',
                'zip_code',
                'country',
                'latitude',
                'longitude',
                'is_default',
                'message',
            ]);

        // Test default location values
        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertEquals('Bellingham', $data['city']);
        $this->assertEquals('Washington', $data['state']);
        $this->assertEquals('98225', $data['zip_code']);
    }

    public function test_get_location_with_custom_ip(): void
    {
        $response = $this->withHeaders(['X-Forwarded-For' => '192.168.1.1'])
            ->getJson("{$this->baseRoute}/location");

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertTrue($data['success']);
        // Should still return default location since service returns hardcoded location
        $this->assertEquals('Bellingham', $data['city']);
    }

    // ===== CALCULATE RATES - MANUAL ENTRY TESTS =====

    public function test_calculate_rates_manual_entry_without_api_key(): void
    {
        Config::set('channel-lister.shipstation.api_key', null);

        $response = $this->postJson("{$this->baseRoute}/calculate", [
            'manual_shipping_cost' => 15.50,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'manual_entry' => true,
                'shipping_cost' => '15.50',
                'message' => 'Manual shipping cost entered',
            ]);
    }

    public function test_calculate_rates_manual_entry_validation_required(): void
    {
        Config::set('channel-lister.shipstation.api_key', null);

        $response = $this->postJson("{$this->baseRoute}/calculate", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['manual_shipping_cost']);
    }

    public function test_calculate_rates_manual_entry_validation_numeric(): void
    {
        Config::set('channel-lister.shipstation.api_key', null);

        $response = $this->postJson("{$this->baseRoute}/calculate", [
            'manual_shipping_cost' => 'not-a-number',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['manual_shipping_cost']);
    }

    public function test_calculate_rates_manual_entry_validation_min_zero(): void
    {
        Config::set('channel-lister.shipstation.api_key', null);

        $response = $this->postJson("{$this->baseRoute}/calculate", [
            'manual_shipping_cost' => -5.00,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['manual_shipping_cost']);
    }

    // ===== CALCULATE RATES - API TESTS =====

    public function test_calculate_rates_with_api_key_validation(): void
    {
        Config::set('channel-lister.shipstation.api_key', 'test-api-key');

        $response = $this->postJson("{$this->baseRoute}/calculate", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'from_zip', 'to_zip', 'length', 'width', 'height', 'weight',
            ]);
    }

    public function test_calculate_rates_zip_code_validation(): void
    {
        Config::set('channel-lister.shipstation.api_key', 'test-api-key');

        $response = $this->postJson("{$this->baseRoute}/calculate", [
            'from_zip' => '1234', // Invalid - too short
            'to_zip' => '12345',
            'length' => 10,
            'width' => 8,
            'height' => 6,
            'weight' => 2.5,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['from_zip']);
    }

    public function test_calculate_rates_invalid_from_zip_format(): void
    {
        Config::set('channel-lister.shipstation.api_key', 'test-api-key');

        $response = $this->postJson("{$this->baseRoute}/calculate", [
            'from_zip' => 'abcde',
            'to_zip' => '12345',
            'length' => 10,
            'width' => 8,
            'height' => 6,
            'weight' => 2.5,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid from ZIP code format',
            ]);
    }

    public function test_calculate_rates_invalid_to_zip_format(): void
    {
        Config::set('channel-lister.shipstation.api_key', 'test-api-key');

        $response = $this->postJson("{$this->baseRoute}/calculate", [
            'from_zip' => '98225',
            'to_zip' => '123456', // Invalid - too long
            'length' => 10,
            'width' => 8,
            'height' => 6,
            'weight' => 2.5,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['to_zip']);
    }

    public function test_calculate_rates_dimension_validation(): void
    {
        Config::set('channel-lister.shipstation.api_key', 'test-api-key');

        $response = $this->postJson("{$this->baseRoute}/calculate", [
            'from_zip' => '98225',
            'to_zip' => '12345',
            'length' => 0, // Invalid - too small
            'width' => 8,
            'height' => 6,
            'weight' => 2.5,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['length']);
    }

    public function test_calculate_rates_with_valid_data_calls_service(): void
    {
        Config::set('channel-lister.shipstation.api_key', 'test-api-key');

        // Mock the service to avoid actual API calls
        $mockService = Mockery::mock(ShippingCalculatorService::class);
        $mockService->shouldReceive('hasApiKey')->andReturn(true);
        $mockService->shouldReceive('validateZipCode')->with('98225')->andReturn(true);
        $mockService->shouldReceive('validateZipCode')->with('90210')->andReturn(true);
        $mockService->shouldReceive('getAllCarrierIds')->andReturn(['se-123456']);
        $mockService->shouldReceive('getShippingRates')->andReturn([
            'success' => true,
            'rates' => [
                [
                    'rate_id' => 'se-123456',
                    'service_name' => 'UPS Ground',
                    'carrier' => 'UPS',
                    'amount' => '12.50',
                ],
            ],
            'dimensional_data' => [
                'cubic_size' => 480,
                'actual_weight' => 2.5,
            ],
        ]);

        $this->app->instance(ShippingCalculatorService::class, $mockService);

        $response = $this->postJson("{$this->baseRoute}/calculate", [
            'from_zip' => '98225',
            'to_zip' => '90210',
            'length' => 10,
            'width' => 8,
            'height' => 6,
            'weight' => 2.5,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'rates' => [
                    [
                        'rate_id' => 'se-123456',
                        'service_name' => 'UPS Ground',
                        'carrier' => 'UPS',
                        'amount' => '12.50',
                    ],
                ],
            ]);
    }

    public function test_calculate_rates_with_specific_carrier(): void
    {
        Config::set('channel-lister.shipstation.api_key', 'test-api-key');

        $mockService = Mockery::mock(ShippingCalculatorService::class);
        $mockService->shouldReceive('hasApiKey')->andReturn(true);
        $mockService->shouldReceive('validateZipCode')->andReturn(true);
        $mockService->shouldReceive('getCarrierRates')->andReturn([
            'success' => true,
            'rates' => [
                [
                    'rate_id' => 'se-123456',
                    'service_name' => 'UPS Ground',
                    'carrier' => 'UPS',
                    'amount' => '12.50',
                ],
            ],
        ]);

        $this->app->instance(ShippingCalculatorService::class, $mockService);

        $response = $this->postJson("{$this->baseRoute}/calculate", [
            'from_zip' => '98225',
            'to_zip' => '90210',
            'length' => 10,
            'width' => 8,
            'height' => 6,
            'weight' => 2.5,
            'carrier' => 'ups',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'rates' => [
                    [
                        'carrier' => 'UPS',
                    ],
                ],
            ]);
    }

    public function test_calculate_rates_with_optional_city_state(): void
    {
        Config::set('channel-lister.shipstation.api_key', 'test-api-key');

        $mockService = Mockery::mock(ShippingCalculatorService::class);
        $mockService->shouldReceive('hasApiKey')->andReturn(true);
        $mockService->shouldReceive('validateZipCode')->andReturn(true);
        $mockService->shouldReceive('getAllCarrierIds')->andReturn([]);
        $mockService->shouldReceive('getShippingRates')->andReturn(['success' => true]);

        $this->app->instance(ShippingCalculatorService::class, $mockService);

        $response = $this->postJson("{$this->baseRoute}/calculate", [
            'from_zip' => '98225',
            'from_city' => 'Bellingham',
            'from_state' => 'WA',
            'to_zip' => '90210',
            'to_city' => 'Beverly Hills',
            'to_state' => 'CA',
            'length' => 10,
            'width' => 8,
            'height' => 6,
            'weight' => 2.5,
        ]);

        $response->assertStatus(200);
    }

    public function test_calculate_rates_carrier_validation(): void
    {
        Config::set('channel-lister.shipstation.api_key', 'test-api-key');

        $response = $this->postJson("{$this->baseRoute}/calculate", [
            'from_zip' => '98225',
            'to_zip' => '90210',
            'length' => 10,
            'width' => 8,
            'height' => 6,
            'weight' => 2.5,
            'carrier' => 'invalid-carrier',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['carrier']);
    }

    // ===== GET CARRIERS TESTS =====

    public function test_get_carriers_returns_service_response(): void
    {
        $mockService = Mockery::mock(ShippingCalculatorService::class);
        $mockService->shouldReceive('getAvailableCarriers')->andReturn([
            'success' => true,
            'carriers' => [
                [
                    'carrier_id' => 'se-123456',
                    'carrier_name' => 'ups',
                    'friendly_name' => 'UPS',
                ],
            ],
        ]);

        $this->app->instance(ShippingCalculatorService::class, $mockService);

        $response = $this->getJson("{$this->baseRoute}/carriers");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'carriers' => [
                    [
                        'carrier_id' => 'se-123456',
                        'carrier_name' => 'ups',
                        'friendly_name' => 'UPS',
                    ],
                ],
            ]);
    }

    public function test_get_carriers_with_service_error(): void
    {
        $mockService = Mockery::mock(ShippingCalculatorService::class);
        $mockService->shouldReceive('getAvailableCarriers')->andReturn([
            'success' => false,
            'message' => 'API error',
            'manual_entry_needed' => true,
        ]);

        $this->app->instance(ShippingCalculatorService::class, $mockService);

        $response = $this->getJson("{$this->baseRoute}/carriers");

        $response->assertStatus(200)
            ->assertJson([
                'success' => false,
                'message' => 'API error',
                'manual_entry_needed' => true,
            ]);
    }

    // ===== CALCULATE DIMENSIONAL WEIGHT TESTS =====

    public function test_calculate_dimensional_weight_validation(): void
    {
        $response = $this->postJson("{$this->baseRoute}/dimensional-weight", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['length', 'width', 'height', 'weight']);
    }

    public function test_calculate_dimensional_weight_min_validation(): void
    {
        $response = $this->postJson("{$this->baseRoute}/dimensional-weight", [
            'length' => 0, // Invalid - too small
            'width' => 8,
            'height' => 6,
            'weight' => 2.5,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['length']);
    }

    public function test_calculate_dimensional_weight_success(): void
    {
        $response = $this->postJson("{$this->baseRoute}/dimensional-weight", [
            'length' => 12,
            'width' => 8,
            'height' => 6,
            'weight' => 2.5,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'cubic_size' => 576, // 12 * 8 * 6
                    'actual_weight' => 2.5,
                ],
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'cubic_size',
                    'actual_weight',
                    'dimensional_weights' => [
                        'ups_commercial' => [
                            'dimensional_weight',
                            'billable_weight',
                            'divisor',
                        ],
                        'fedex' => [
                            'dimensional_weight',
                            'billable_weight',
                            'divisor',
                        ],
                        'usps' => [
                            'dimensional_weight',
                            'billable_weight',
                            'divisor',
                        ],
                    ],
                ],
            ]);
    }

    public function test_calculate_dimensional_weight_with_decimal_values(): void
    {
        $response = $this->postJson("{$this->baseRoute}/dimensional-weight", [
            'length' => 10.5,
            'width' => 8.25,
            'height' => 6.75,
            'weight' => 2.25,
        ]);

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertTrue($data['success']);
        $this->assertEquals(584.71875, $data['data']['cubic_size']); // 10.5 * 8.25 * 6.75
        $this->assertEquals(2.25, $data['data']['actual_weight']);
    }

    // ===== INTEGRATION TESTS =====

    public function test_endpoint_routes_are_configured(): void
    {
        // Test that all routes exist and return proper response structure
        $endpoints = [
            ['GET', '/check-api'],
            ['GET', '/location'],
            ['POST', '/calculate'],
            ['GET', '/carriers'],
            ['POST', '/dimensional-weight'],
        ];

        foreach ($endpoints as [$method, $path]) {
            $response = match ($method) {
                'GET' => $this->getJson($this->baseRoute.$path),
                'POST' => $this->postJson($this->baseRoute.$path, []),
                default => null,
            };

            // All endpoints should at least not return 404
            $this->assertNotEquals(404, $response->getStatusCode(),
                "Route {$method} {$path} returned 404");
        }
    }

    public function test_service_dependency_injection(): void
    {
        // Test that the controller properly receives the service dependency
        $response = $this->getJson("{$this->baseRoute}/check-api");

        // Should not fail with dependency injection errors
        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'has_api_key', 'message']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
