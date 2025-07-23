<?php

namespace IGE\ChannelLister\View\Components\Custom;

use IGE\ChannelLister\Models\ChannelListerField;
use IGE\ChannelLister\Models\WishBrandDirectory;
use Illuminate\View\Component;

/**
 * This is placeholder for v/ChannelLister.
 * I don't expect to keep this either as we should expect to extract the view components that exist
 * in v/ChannelLister into their own component classes and associated views.
 */
class WishBrandDirectoryInput extends Component
{

    const TABLE_WISH_BRAND_DIRECTORY = 'wish_brand_directory';


    public function __construct(public ChannelListerField $params)
    {
        //
    }

    public function render()
    {

        $brand_data = WishBrandDirectory::select('brand_id', 'brand_name')
            ->orderBy('brand_name')
            ->pluck('brand_id', 'brand_name')
            ->toArray();
		$params['input_type_aux'] = $brand_data;

        return view('channel-lister::components.custom.wish-brand-directory-input', data: [
            'input_type_aux' => $params['input_type_aux'],
        ]);
    }

}