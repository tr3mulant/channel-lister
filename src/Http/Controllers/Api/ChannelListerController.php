<?php

namespace IGE\ChannelLister\Http\Controllers\Api;

use IGE\ChannelLister\ChannelLister;
use IGE\ChannelLister\View\Components\ChannelListerFields;
use IGE\ChannelLister\View\Components\Custom\SkuBundleComponentInputRow;
use IGE\ChannelLister\View\Components\Modal\Header;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Blade;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ChannelListerController extends Controller
{
    public function buildModalView(): JsonResponse
    {
        return response()->json(['data' => Blade::renderComponent(new Header)]);
    }

    public function formDataByPlatform(Request $request, string $platform): JsonResponse
    {
        $request->merge(['platform' => $platform])->validate([
            'platform' => 'required|string|exists:channel_lister_fields,marketplace',
        ]);

        return response()->json(['data' => Blade::renderComponent(new ChannelListerFields($platform))]);
    }

    public function buildUpc(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'prefix' => 'nullable|string|max:11',
        ]);

        $prefix = $validated['prefix'] ?? '';

        try {
            $isPurchased = ChannelLister::isPurchasedUpcPrefix($prefix);

            return response()->json([
                'data' => ChannelLister::createUpc($prefix),
                'prefix' => $prefix,
                'is_purchased' => $isPurchased,
                'name' => $isPurchased ? ChannelLister::getNameByPrefix($prefix) : null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function isUpcValid(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'UPC' => 'required|string',
        ]);

        return response()->json(['UPC' => ChannelLister::isValidUpc($validated['UPC'])]);
    }

    public function addBundleComponentRow(): JsonResponse
    {
        return response()->json(['data' => Blade::renderComponent(new SkuBundleComponentInputRow)]);
    }

    public function getCountryCodeOptions(Request $request, string $country, string $digits): JsonResponse
    {
        $request->merge(['country' => $country, 'digits' => $digits])->validate([
            'country' => 'required|string',
            'digits' => 'required|in:2,3',
        ]);

        $countryCode = ChannelLister::getCountryCode($country, (int) $digits);

        if ($countryCode === null) {
            return response()->json([
                'error' => 'Country code not found for: ' . $country,
            ], 404);
        }

        return response()->json(['data' => $countryCode]);
    }

    public function submitProductData(Request $request): JsonResponse
    {
        $validated = $request->validate([
            // Basic validation for form data structure
            // Since fields are dynamic, we validate the overall structure
            '*' => 'nullable|string|max:65535', // Allow any field name with string values up to TEXT length
            
            // Specific validation for known critical fields if they exist
            'Title' => 'nullable|string|max:255',
            'Description' => 'nullable|string|max:10000',
            'UPC' => 'nullable|string|size:12|regex:/^\d{12}$/',
            'Price' => 'nullable|numeric|min:0|max:999999.99',
            'Total Quantity' => 'nullable|integer|min:0|max:999999',
            'Weight' => 'nullable|numeric|min:0|max:999999.99',
            
            // Image fields (can be multiple)
            'image*' => 'nullable|url|max:2048',
            
            // Bundle component fields (can be arrays)
            'sku_bundle_component_*' => 'nullable|string|max:255',
            'sku_bundle_quantity_*' => 'nullable|integer|min:1|max:999999',
        ]);

        $filename = ChannelLister::csv($validated);

        return response()->json([
            'success' => true,
            'download_url' => $filename,
        ]);

        //This return works when the return type is BinaryFileResponse
        //return response()->download($filename, 'channel_lister_export.csv');

    }
}
