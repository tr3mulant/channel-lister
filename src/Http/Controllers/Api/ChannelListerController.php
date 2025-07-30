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

        /** @var Collection<string, Collection<int, ChannelListerField>> $fields */
        $fields = ChannelListerField::query()
            ->where('marketplace', $platform)
            ->orderBy('ordering')
            ->get()
            ->groupBy('grouping');

        // $key = array_key_first($fields->toArray());
        // $firstGroup = $fields->get($key);
        // $firstGroup = collect([$firstGroup->first()]);
        // $fields->put($key, $firstGroup);
        // foreach ($fields as $grouping => $groupFields) {
        //     if ($grouping !== $key) {
        //         $fields->pull($grouping);
        //     }
        // }


        $data = '';
        $panel_num = 0;
        foreach ($fields as $grouping => $groupFields) {
            /** @var \Illuminate\Support\Collection<int|string, \IGE\ChannelLister\Models\ChannelListerField> $groupFields */
            $data .= Blade::renderComponent(new Panel(
                fields: $groupFields,
                grouping_name: $grouping,
                title: $grouping,
                panel_num: $panel_num,
                id_count: $panel_num,
                wide: false,
                start_collapsed: false
            ));
            $panel_num++;
        }

        return response()->json(['data' => $data]);
    }
}
// ]);
