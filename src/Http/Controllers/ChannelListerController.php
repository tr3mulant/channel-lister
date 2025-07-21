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
        $marketplaces = ChannelListerField::query()->select(['id', 'name'])->get()->toArray();
        
        foreach (config('channel-lister.marketplaces.disabled') as $marketplace) {
            unset($marketplaces[array_search($marketplace, $marketplaces)]);
        }

        // $marketplaces = $this->dc->m_ChannelLister->getMarketplaces();
        // foreach (self::DEACTIVATED_MARKETPLACES as $marketplace) {
            // unset($marketplaces[array_search($marketplace, $marketplaces)]);
        // }
        sort($marketplaces);

        $platform_json = '[';
        foreach ($marketplaces as $marketplace) {
            $platform_json .= '{id: "' . $marketplace . '", name: "' . $this->mapMarketplaceToName($marketplace) . '"},';
        }
        $platform_json .= '];';



        return view('channel-lister::index', [
            'marketplaces' => $marketplaces,
            'platform_json' => $platform_json
        ]);
    }

    /**
	 * Maps lowercase marketplace names to form used in labels
	 * @param  string $marketplace lowercase name of marketplace
	 * @return string              Marketplace formatted as used in label
	 */
	protected function mapMarketplaceToName($marketplace)
	{
		switch ($marketplace) {
			case 'amazon':
				$marketplace = 'Amazon US';
				break;

			case 'amazon-ca':
				$marketplace = 'Amazon CA';
				break;

			case 'amazon-au':
				$marketplace = 'Amazon AU';
				break;

			case 'amazon-mx':
				$marketplace = 'Amazon MX';
				break;

			case 'dealsonly':
				$marketplace = 'DealsOnly';
				break;

			case 'ebay':
				$marketplace = 'eBay';
				break;

			case 'resourceridge':
				$marketplace = 'Resource Ridge';
				break;

			case 'walmart-ca':
				$marketplace = 'Walmart CA';
				break;

			default:
				$marketplace = ucwords($marketplace);
				break;
		}
		return $marketplace;
	}
}
