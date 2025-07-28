<?php

namespace IGE\ChannelLister\Http\Controllers\Api;

use IGE\ChannelLister\Models\ChannelListerField;
use IGE\ChannelLister\View\Components\Modal\Header;
use IGE\ChannelLister\View\Components\Panel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
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

        /** @var Collection<string, ChannelListerField> $fields */
        $fields = ChannelListerField::query()
            ->where('marketplace', $platform)
            ->orderBy('ordering')
            ->get();

        return response()->json([
            'data' => Blade::renderComponent(new Panel(
                fields: $fields,
                grouping_name: 'default',
                panel_num: 1,
                wide: false,
                start_collapsed: true
            )),
        ]);
    }
}
