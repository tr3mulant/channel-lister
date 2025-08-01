<?php

namespace IGE\ChannelLister\Http\Controllers;

use IGE\ChannelLister\ChannelLister;
use IGE\ChannelLister\Models\ChannelListerField;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ChannelListerController extends Controller
{
    public function index(Request $request): View
    {
        /** @var string[]|string $disabledMarketplaces */
        $disabledMarketplaces = config('channel-lister.marketplaces.disabled', []);

        if (! is_array($disabledMarketplaces)) {
            $disabledMarketplaces = [$disabledMarketplaces];
        }

        $disabledMarketplaces = ChannelLister::disabledMarketplaces();

        /** @var string[] $marketplaces */
        $marketplaces = ChannelListerField::query()
            ->select('marketplace')
            ->groupBy('marketplace')
            ->whereNotIn('marketplace', $disabledMarketplaces)
            ->pluck('marketplace')
            ->sort()
            ->toArray();

        $platform_json = collect($marketplaces)
            ->map(fn (string $marketplace): array => [
                'id' => $marketplace,
                'name' => ChannelLister::marketplaceDisplayName($marketplace),
            ])
            ->toArray();

        return view('channel-lister::channel-lister.index', [
            'marketplaces' => $marketplaces,
            'platform_json' => $platform_json,
        ]);
    }
}
