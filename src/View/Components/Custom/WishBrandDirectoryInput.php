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
        $brand_data = WishBrandDirectory::query()
            ->select('brand_id', 'brand_name')
            ->orderBy('brand_name')
            ->pluck('brand_id', 'brand_name')
            ->toArray();

        $element_name = $this->params->field_name;
        $required = $this->params->required ? 'required' : '';
        $label_text = $this->params->display_name;
        $id = $this->params->field_name.'-id';
        $tooltip = $this->params->tooltip;
        $placeholder = $this->params->example ?? '';
        $maps_to_text = 'Maps To: <code>'.$this->params->field_name.'</code>';

        return view('channel-lister::components.custom.wish-brand-directory-input', data: [
            'params' => $this->params,
            'element_name' => $element_name,
            'required' => $required,
            'label_text' => $label_text,
            'id' => $id,
            'tooltip' => $tooltip,
            'placeholder' => $placeholder,
            'maps_to_text' => $maps_to_text,
            'input_type_aux' => $brand_data,
        ]);
    }
}
