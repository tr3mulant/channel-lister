<?php

namespace IGE\ChannelLister\View\Components\Custom\CategorySearch;

use IGE\ChannelLister\Models\ChannelListerField;
use Illuminate\View\Component;

/**
 * This is placeholder for v/ChannelLister.
 * I don't expect to keep this either as we should expect to extract the view components that exist
 * in v/ChannelLister into their own component classes and associated views.
 */
class NeweggCategoryHtml extends Component
{
    
    public string $apiUrl = 'api/ChannelLister/getNeweggCategoryOptions/';

    public function __construct(public ChannelListerField $params)
    {
        //
    }

    public function render()
    {
        return view('channel-lister::components.custom.category-search.newegg-category-html');
    }
}