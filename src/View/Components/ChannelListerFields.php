<?php

namespace IGE\ChannelLister\View\Components;

use IGE\ChannelLister\Models\ChannelListerField;
use Illuminate\View\Component;

class ChannelListerFields extends Component
{
    public function __construct(public string $marketplace, public string $class_str_default = 'channel-lister-fields')
    {
        //
    }

    public function render()
    {
        $fields = ChannelListerField::query()
            ->where('marketplace', value: $this->marketplace)
            ->get()
            ->groupBy('grouping');

        $this->mapMarketplaceToName($this->marketplace);

        return view('channel-lister::components.channel-lister-fields', ['fields' => $fields]);
    }

    /**
     * Maps lowercase marketplace names to form used in labels
     *
     * @return string Marketplace formatted as used in label
     */
    protected function mapMarketplaceToName($marketplace): string
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
            default => ucwords((string) $marketplace),
        };
    }
}
