# Claude Instructions

This file contains instructions and context for Claude to help with the channel-lister project.

I would like you to implement this application that the website claude app wrote. 
I am trying to add functionality to the missing api endpoint calculateShippingCost,
Here is the description I gave the website claude:

ow would we use the shipping station api to calculate the estimated shipping cost? In our algorithm, this is what we are thinking: Calculate the volumetric weight using height, width, length, and weight. Then we are going to have the user give us their IP address, and from that IP we will get their geolocation using the laravel Stevebauman package for the starting point of the shipment. Then the user will also give us a zip code for the end point of the distance for a specific shipment calculation. The user can change the zip code from place to place to get a rough estimate on how much shipping is going to cost for different locations in the country.

I don't know much about the Ship Station API call, how would we use it? Could you give me a prototype of this application working?

I also want it to be a feature to where if the user does not give the api key, The user should just fill in their estimated shipping cost in the form. If there is an api key, do all of the calculation logic. 

Please use the code below to intgrate this application into channel lister. I also want the UI to be uniform with what channel lister already has set up. 

Here is the code it spit out:
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Stevebauman\Location\Facades\Location;

class ShippingCalculatorService
{
    private $apiKey;
    private $baseUrl;
    
    public function __construct()
    {
        $this->apiKey = config('services.shipstation.api_key');
        $this->baseUrl = config('services.shipstation.base_url', 'https://api.shipengine.com/v1');
    }
    
    /**
     * Get user location from IP address using Stevebauman\Location
     */
    public function getLocationFromIP($ipAddress = null)
    {
        try {
            $location = Location::get($ipAddress);
            
            if ($location) {
                return [
                    'success' => true,
                    'city' => $location->cityName,
                    'state' => $location->regionName,
                    'zip_code' => $location->zipCode,
                    'country' => $location->countryName,
                    'latitude' => $location->latitude,
                    'longitude' => $location->longitude
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Could not determine location'
            ];
            
        } catch (\Exception $e) {
            Log::error('Location detection failed: ' . $e->getMessage());
            
            // Return default location (Bellingham, WA)
            return [
                'success' => true,
                'city' => 'Bellingham',
                'state' => 'Washington',
                'zip_code' => '98225',
                'country' => 'United States',
                'latitude' => 48.7519,
                'longitude' => -122.4787,
                'is_default' => true
            ];
        }
    }
    
    /**
     * Calculate dimensional weight for different carriers
     */
    public function calculateDimensionalWeight($length, $width, $height, $actualWeight)
    {
        $cubicSize = $length * $width * $height;
        
        // Carrier divisors for commercial rates
        $divisors = [
            'ups_commercial' => 139,
            'fedex' => 139,
            'usps' => 166
        ];
        
        $dimensionalWeights = [];
        
        foreach ($divisors as $carrier => $divisor) {
            $dimWeight = $cubicSize / $divisor;
            $billableWeight = max($actualWeight, $dimWeight);
            
            $dimensionalWeights[$carrier] = [
                'dimensional_weight' => round($dimWeight, 2),
                'billable_weight' => round($billableWeight, 2),
                'divisor' => $divisor
            ];
        }
        
        return [
            'cubic_size' => $cubicSize,
            'actual_weight' => $actualWeight,
            'dimensional_weights' => $dimensionalWeights
        ];
    }
    
    /**
     * Get shipping rates from ShipStation API
     */
    public function getShippingRates($fromZip, $toZip, $length, $width, $height, $weight, $options = [])
    {
        try {
            // Calculate dimensional weights first
            $dimensionalData = $this->calculateDimensionalWeight($length, $width, $height, $weight);
            
            // Prepare API payload
            $payload = [
                'rate_options' => [
                    'carrier_ids' => $options['carrier_ids'] ?? [], // Empty array gets all carriers
                ],
                'shipment' => [
                    'ship_to' => [
                        'postal_code' => $toZip,
                        'country_code' => 'US'
                    ],
                    'ship_from' => [
                        'postal_code' => $fromZip,
                        'country_code' => 'US'
                    ],
                    'packages' => [
                        [
                            'weight' => [
                                'value' => $weight,
                                'unit' => 'pound'
                            ],
                            'dimensions' => [
                                'length' => $length,
                                'width' => $width,
                                'height' => $height,
                                'unit' => 'inch'
                            ]
                        ]
                    ]
                ]
            ];
            
            // Add service types filter if specified
            if (!empty($options['service_types'])) {
                $payload['rate_options']['service_codes'] = $options['service_types'];
            }
            
            // Make API call to ShipStation
            $response = Http::withHeaders([
                'API-Key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl . '/rates', $payload);
            
            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'success' => true,
                    'rates' => $this->formatRates($data['rate_response']['rates'] ?? []),
                    'dimensional_data' => $dimensionalData,
                    'request_id' => $data['rate_response']['request_id'] ?? null
                ];
            } else {
                Log::error('ShipStation API Error: ' . $response->body());
                
                return [
                    'success' => false,
                    'message' => 'Failed to get shipping rates',
                    'error' => $response->json()
                ];
            }
            
        } catch (\Exception $e) {
            Log::error('Shipping calculation error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error calculating shipping rates',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Format rates for consistent response
     */
    private function formatRates($rates)
    {
        $formattedRates = [];
        
        foreach ($rates as $rate) {
            $formattedRates[] = [
                'rate_id' => $rate['rate_id'] ?? null,
                'service_name' => $rate['service_type'] ?? 'Unknown Service',
                'carrier' => $rate['carrier_friendly_name'] ?? 'Unknown Carrier',
                'service_code' => $rate['service_code'] ?? null,
                'amount' => number_format($rate['shipping_amount']['amount'] ?? 0, 2),
                'currency' => $rate['shipping_amount']['currency'] ?? 'USD',
                'delivery_days' => $rate['delivery_days'] ?? null,
                'estimated_delivery_date' => $rate['estimated_delivery_date'] ?? null,
                'carrier_delivery_days' => $rate['carrier_delivery_days'] ?? null,
                'negotiated_rate' => $rate['negotiated_rate'] ?? false,
                'zone' => $rate['zone'] ?? null,
                'package_type' => $rate['package_type'] ?? null
            ];
        }
        
        // Sort by price (ascending)
        usort($formattedRates, function($a, $b) {
            return floatval($a['amount']) <=> floatval($b['amount']);
        });
        
        return $formattedRates;
    }
    
    /**
     * Validate ZIP codes
     */
    public function validateZipCode($zipCode)
    {
        return preg_match('/^\d{5}(-\d{4})?$/', $zipCode);
    }
    
    /**
     * Get carrier-specific rates
     */
    public function getCarrierRates($fromZip, $toZip, $length, $width, $height, $weight, $carrierCode)
    {
        $carrierIds = [
            'ups' => ['se-123456'], // Replace with actual UPS carrier ID
            'fedex' => ['se-789012'], // Replace with actual FedEx carrier ID
            'usps' => ['se-345678'] // Replace with actual USPS carrier ID
        ];
        
        $options = [
            'carrier_ids' => $carrierIds[$carrierCode] ?? []
        ];
        
        return $this->getShippingRates($fromZip, $toZip, $length, $width, $height, $weight, $options);
    }
    
    /**
     * Get available carriers from ShipStation
     */
    public function getAvailableCarriers()
    {
        try {
            $response = Http::withHeaders([
                'API-Key' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->get($this->baseUrl . '/carriers');
            
            if ($response->successful()) {
                return [
                    'success' => true,
                    'carriers' => $response->json()['carriers'] ?? []
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Failed to fetch carriers'
            ];
            
        } catch (\Exception $e) {
            Log::error('Error fetching carriers: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error fetching available carriers',
                'error' => $e->getMessage()
            ];
        }
    }
}

// Controller Example
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ShippingCalculatorService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ShippingController extends Controller
{
    private $shippingService;
    
    public function __construct(ShippingCalculatorService $shippingService)
    {
        $this->shippingService = $shippingService;
    }
    
    /**
     * Get user location from IP
     */
    public function getLocation(Request $request): JsonResponse
    {
        $ipAddress = $request->ip();
        $location = $this->shippingService->getLocationFromIP($ipAddress);
        
        return response()->json($location);
    }
    
    /**
     * Calculate shipping rates
     */
    public function calculateRates(Request $request): JsonResponse
    {
        $request->validate([
            'from_zip' => 'required|string|size:5',
            'to_zip' => 'required|string|size:5',
            'length' => 'required|numeric|min:0.1',
            'width' => 'required|numeric|min:0.1',
            'height' => 'required|numeric|min:0.1',
            'weight' => 'required|numeric|min:0.1',
            'carrier' => 'sometimes|string|in:ups,fedex,usps',
            'service_types' => 'sometimes|array'
        ]);
        
        // Validate ZIP codes
        if (!$this->shippingService->validateZipCode($request->from_zip)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid from ZIP code format'
            ], 400);
        }
        
        if (!$this->shippingService->validateZipCode($request->to_zip)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid to ZIP code format'
            ], 400);
        }
        
        $options = [];
        
        // Filter by specific carrier if requested
        if ($request->has('carrier')) {
            $result = $this->shippingService->getCarrierRates(
                $request->from_zip,
                $request->to_zip,
                $request->length,
                $request->width,
                $request->height,
                $request->weight,
                $request->carrier
            );
        } else {
            // Get all carrier rates
            if ($request->has('service_types')) {
                $options['service_types'] = $request->service_types;
            }
            
            $result = $this->shippingService->getShippingRates(
                $request->from_zip,
                $request->to_zip,
                $request->length,
                $request->width,
                $request->height,
                $request->weight,
                $options
            );
        }
        
        return response()->json($result);
    }
    
    /**
     * Get available carriers
     */
    public function getCarriers(): JsonResponse
    {
        $carriers = $this->shippingService->getAvailableCarriers();
        return response()->json($carriers);
    }
    
    /**
     * Calculate dimensional weight only
     */
    public function calculateDimensionalWeight(Request $request): JsonResponse
    {
        $request->validate([
            'length' => 'required|numeric|min:0.1',
            'width' => 'required|numeric|min:0.1',
            'height' => 'required|numeric|min:0.1',
            'weight' => 'required|numeric|min:0.1'
        ]);
        
        $result = $this->shippingService->calculateDimensionalWeight(
            $request->length,
            $request->width,
            $request->height,
            $request->weight
        );
        
        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }
}

// Routes (routes/api.php)
/*
Route::prefix('shipping')->group(function () {
    Route::get('/location', [ShippingController::class, 'getLocation']);
    Route::post('/calculate', [ShippingController::class, 'calculateRates']);
    Route::get('/carriers', [ShippingController::class, 'getCarriers']);
    Route::post('/dimensional-weight', [ShippingController::class, 'calculateDimensionalWeight']);
});
*/

// Configuration (config/services.php)
/*
'shipstation' => [
    'api_key' => env('SHIPSTATION_API_KEY'),
    'base_url' => env('SHIPSTATION_BASE_URL', 'https://api.shipengine.com/v1'),
],
*/

// Environment Variables (.env)
/*
SHIPSTATION_API_KEY=TEST_your_sandbox_api_key_here
SHIPSTATION_BASE_URL=https://api.shipengine.com/v1
*/

// Frontend Integration Service (JavaScript)
class ShippingAPIService {
    constructor(baseUrl = '/api/shipping') {
        this.baseUrl = baseUrl;
    }
    
    async getLocation() {
        try {
            const response = await fetch(`${this.baseUrl}/location`);
            return await response.json();
        } catch (error) {
            console.error('Error getting location:', error);
            throw error;
        }
    }
    
    async calculateRates(rateData) {
        try {
            const response = await fetch(`${this.baseUrl}/calculate`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(rateData)
            });
            
            return await response.json();
        } catch (error) {
            console.error('Error calculating rates:', error);
            throw error;
        }
    }
    
    async getCarriers() {
        try {
            const response = await fetch(`${this.baseUrl}/carriers`);
            return await response.json();
        } catch (error) {
            console.error('Error getting carriers:', error);
            throw error;
        }
    }
    
    async calculateDimensionalWeight(dimensions) {
        try {
            const response = await fetch(`${this.baseUrl}/dimensional-weight`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(dimensions)
            });
            
            return await response.json();
        } catch (error) {
            console.error('Error calculating dimensional weight:', error);
            throw error;
        }
    }
}

// Database Migration for storing shipping calculations (optional)
/*
Schema::create('shipping_calculations', function (Blueprint $table) {
    $table->id();
    $table->string('from_zip', 10);
    $table->string('to_zip', 10);
    $table->decimal('length', 8, 2);
    $table->decimal('width', 8, 2);
    $table->decimal('height', 8, 2);
    $table->decimal('weight', 8, 2);
    $table->decimal('cubic_size', 10, 2);
    $table->json('dimensional_weights');
    $table->json('rates');
    $table->string('request_id')->nullable();
    $table->string('user_ip')->nullable();
    $table->timestamps();
    
    $table->index(['from_zip', 'to_zip']);
    $table->index('created_at');
});
*/

// Model for storing calculations (optional)
/*
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingCalculation extends Model
{
    protected $fillable = [
        'from_zip',
        'to_zip',
        'length',
        'width',
        'height',
        'weight',
        'cubic_size',
        'dimensional_weights',
        'rates',
        'request_id',
        'user_ip'
    ];
    
    protected $casts = [
        'dimensional_weights' => 'array',
        'rates' => 'array',
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'weight' => 'decimal:2',
        'cubic_size' => 'decimal:2'
    ];
}
*/