<?php

namespace IGE\ChannelLister\View\Components;

use IGE\ChannelLister\ChannelLister;
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
            'marketplace' => $this->marketplace,
            'marketplace_name' => $marketplace_name,
            'classStrDefault' => $this->classStrDefault,
        ]);
    }

    /**
     * Maps lowercase marketplace names to form used in labels
     *
     * @return string Marketplace formatted as used in label
     */
    protected function mapMarketplaceToName(string $marketplace): string
    {
        return ChannelLister::marketplaceDisplayName($marketplace);
    }
}
