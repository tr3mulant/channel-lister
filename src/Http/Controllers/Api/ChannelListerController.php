<?php

namespace IGE\ChannelLister\Http\Controllers\Api;

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
}
