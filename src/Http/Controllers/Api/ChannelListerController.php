<?php

namespace IGE\ChannelLister\Http\Controllers\Api;

use IGE\ChannelLister\ChannelLister;
use IGE\ChannelLister\View\Components\ChannelListerFields;
use IGE\ChannelLister\View\Components\Modal\Header;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Blade;

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
}
