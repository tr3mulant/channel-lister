<?php

namespace IGE\ChannelLister\View\Components;

use IGE\ChannelLister\Models\ChannelListerField;
use Illuminate\View\Component;

/**
 * This is placeholder for v/ChannelLister.
 * I don't expect to keep this either as we should expect to extract the view components that exist
 * in v/ChannelLister into their own component classes and associated views.
 */
class ChannelListerFields extends Component
{
    public function __construct(public string $marketplace)
    {
        
    }

    public function render()
    {
        $fields = ChannelListerField::query()
            ->where('marketplace', value: $this->marketplace)
            ->get()
            ->groupBy('grouping');

        return view('channel-lister::components.channel-lister-fields', data: compact('fields'));
    }

}