<?php

namespace IGE\ChannelLister\View\Components;

use IGE\ChannelLister\Models\ChannelListerField;
use Illuminate\View\Component;

class ChannelListerFields extends Component
{
    public function __construct(public string $marketplace, public string $classStrDefault = 'panel-group')
    {
        //
    }

    public function render()
    {
        /** @var \Illuminate\Support\Collection<string, \Illuminate\Support\Collection<int, ChannelListerField>> $fields */
        $fields = ChannelListerField::query()
            ->where('marketplace', $this->marketplace)
            ->orderBy('ordering')
            ->get()
            ->groupBy('grouping');

        $marketplace_name = $this->mapMarketplaceToName($this->marketplace);

        return view('channel-lister::components.channel-lister-fields', [
            'fields' => $fields,
            'marketplace_name' => $marketplace_name,
        ]);
    }

    /**
     * Maps lowercase marketplace names to form used in labels
     *
     * @return string Marketplace formatted as used in label
     */
    protected function mapMarketplaceToName(string $marketplace): string
    {
        return match ($marketplace) {
            'amazon' => 'Amazon US',
            'amazon-ca' => 'Amazon CA',
            'amazon-au' => 'Amazon AU',
            'amazon-mx' => 'Amazon MX',
            'dealsonly' => 'DealsOnly',
            'ebay' => 'eBay',
            'resourceridge' => 'Resource Ridge',
            'walmart-ca' => 'Walmart CA',
            default => ucwords($marketplace),
        };
    }
}
