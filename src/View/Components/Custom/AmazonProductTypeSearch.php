<?php

namespace IGE\ChannelLister\View\Components\Custom;

use IGE\ChannelLister\Models\ChannelListerField;
use Illuminate\View\Component;

class AmazonProductTypeSearch extends Component
{
    public string $apiUrl = 'api/amazon-listing/search-product-types';

    public string $requirementsApiUrl = 'api/amazon-listing/listing-requirements';

    public string $existingListingApiUrl = 'api/amazon-listing/existing-listing';

    public function __construct(public ChannelListerField $params)
    {
        //
    }

    public function render()
    {
        return view('channel-lister::components.custom.amazon-product-type-search');
    }
}
