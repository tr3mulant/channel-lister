<?php

namespace IGE\ChannelLister\Http\Controllers;

use IGE\ChannelLister\Models\ChannelListerField;
use IGE\ChannelLister\View\Components\ChannelLister;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * This is placeholder for c/ChannelLister.
 * This is a file that should contain a lot of the logic in c/ChannelLister but that file will have
 * quite a bit of refactoring
 */
class ChannelListerController extends Controller
{
    public function index(Request $request): View
    {
        /** @var string[]|string $disabledMarketplaces */
        $disabledMarketplaces = config('channel-lister.marketplaces.disabled', []);

        if (! is_array($disabledMarketplaces)) {
            $disabledMarketplaces = [$disabledMarketplaces];
        }

        /** @var string[] $marketplaces */
        $marketplaces = ChannelListerField::query()
            ->select('marketplace')
            ->whereNotIn('marketplace', $disabledMarketplaces)
            ->pluck('marketplace')
            ->sort()
            ->toArray();

        $platform_json = collect($marketplaces)
            ->map(fn (string $marketplace): array => [
                'id' => $marketplace,
                'name' => $this->mapMarketplaceToName($marketplace),
            ])
            ->toArray();

        return view('channel-lister::channel-lister.index', [
            'marketplaces' => $marketplaces,
            'platform_json' => $platform_json,
        ]);
    }

    /**
     * Maps lowercase marketplace names to form used in labels
     *
     * @param  string  $marketplace  lowercase name of marketplace
     * @return string Marketplace formatted as used in label
     */
    protected function mapMarketplaceToName($marketplace): string
    {
        return match ($marketplace) {
            'amazon', 'amazon-us', 'amazon_us' => 'Amazon US',
            'amazon-ca', 'amazon_ca' => 'Amazon CA',
            'amazon-au', 'amazon_au' => 'Amazon AU',
            'amazon-mx', 'amazon_mx' => 'Amazon MX',
            'dealsonly' => 'DealsOnly',
            'ebay' => 'eBay',
            'resourceridge' => 'Resource Ridge',
            'walmart', 'walmart-us', 'walmart_us' => 'Walmart US',
            'walmart-ca', 'walmart_ca' => 'Walmart CA',
            default => ucwords($marketplace),
        };
    }
}
