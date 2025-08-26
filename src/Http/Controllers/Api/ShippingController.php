<?php

namespace IGE\ChannelLister\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ShippingController extends Controller
{
    public function __construct(private readonly \IGE\ChannelLister\Services\ShippingCalculatorService $shippingService) {}

    /**
     * Check if API key is available
     */
    public function checkApiAvailability(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'has_api_key' => $this->shippingService->hasApiKey(),
            'message' => $this->shippingService->hasApiKey()
                ? 'API calculations available'
                : 'Manual entry required - no API key configured',
        ]);
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
        // Check if manual cost is provided when API key is not available
        if (! $this->shippingService->hasApiKey()) {
            $validated = $request->validate([
                'manual_shipping_cost' => 'required|numeric|min:0',
            ]);

            return response()->json([
                'success' => true,
                'manual_entry' => true,
                'shipping_cost' => number_format($validated['manual_shipping_cost'], 2),
                'message' => 'Manual shipping cost entered',
            ]);
        }

        // Validate API calculation fields - make city/state optional for backward compatibility
        $validated = $request->validate([
            'from_zip' => 'required|string|size:5',
            'from_city' => 'sometimes|string|max:100',
            'from_state' => 'sometimes|string|size:2',
            'to_zip' => 'required|string|size:5',
            'to_city' => 'sometimes|string|max:100',
            'to_state' => 'sometimes|string|size:2',
            'length' => 'required|numeric|min:0.1',
            'width' => 'required|numeric|min:0.1',
            'height' => 'required|numeric|min:0.1',
            'weight' => 'required|numeric|min:0.1',
            'carrier' => 'sometimes|string|in:ups,fedex,usps',
            'service_types' => 'sometimes|array',
        ]);

        // Validate ZIP codes
        if (! $this->shippingService->validateZipCode($validated['from_zip'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid from ZIP code format',
            ], 400);
        }

        if (! $this->shippingService->validateZipCode($validated['to_zip'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid to ZIP code format',
            ], 400);
        }

        // Prepare address arrays with defaults if city/state not provided
        $fromAddress = [
            'city' => $validated['from_city'] ?? 'City',
            'state' => $validated['from_state'] ?? 'WA',
            'zip' => $validated['from_zip'],
        ];

        $toAddress = [
            'city' => $validated['to_city'] ?? 'City',
            'state' => $validated['to_state'] ?? 'CA',
            'zip' => $validated['to_zip'],
        ];

        // Filter by specific carrier if requested
        if (isset($validated['carrier'])) {
            $result = $this->shippingService->getCarrierRates(
                $fromAddress,
                $toAddress,
                (float) $validated['length'],
                (float) $validated['width'],
                (float) $validated['height'],
                (float) $validated['weight'],
                $validated['carrier']
            );
        } else {
            // Get all carrier rates - include all available carrier IDs
            $options = [
                'carrier_ids' => $this->shippingService->getAllCarrierIds(),
            ];

            if (isset($validated['service_types'])) {
                $options['service_types'] = $validated['service_types'];
            }

            $result = $this->shippingService->getShippingRates(
                $fromAddress,
                $toAddress,
                (float) $validated['length'],
                (float) $validated['width'],
                (float) $validated['height'],
                (float) $validated['weight'],
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
        $validated = $request->validate([
            'length' => 'required|numeric|min:0.1',
            'width' => 'required|numeric|min:0.1',
            'height' => 'required|numeric|min:0.1',
            'weight' => 'required|numeric|min:0.1',
        ]);

        $result = $this->shippingService->calculateDimensionalWeight(
            (float) $validated['length'],
            (float) $validated['width'],
            (float) $validated['height'],
            (float) $validated['weight']
        );

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}
