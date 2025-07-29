<?php

namespace IGE\ChannelLister\View\Components\Custom;

use IGE\ChannelLister\Models\ChannelListerField;
use Illuminate\View\Component;

/**
 * This is placeholder for v/ChannelLister.
 * I don't expect to keep this either as we should expect to extract the view components that exist
 * in v/ChannelLister into their own component classes and associated views.
 */
class Upc extends Component
{
    public function __construct(public ChannelListerField $params)
    {
        //
    }

    public function render()
    {

        $element_name = $this->params->field_name;
        $label_text = empty($this->params->display_name) ? 'UPC' : $this->params->display_name;
        $tooltip = empty($this->params->tooltip) ? 'UPC must be different for every listing.' : $this->params->tooltip;
        $required = empty($this->params->required) ? '' : 'required';
        $platform = $this->params->marketplace;
        $maps_to_text = 'Maps To: <code>'.$this->params->field_name.'</code>';
        $asr_upc_prefixes = config('channel-lister.upc_prefixes', []);

        return view('channel-lister::components.custom.upc', data: [
            'params' => $this->params,
            'element_name' => $element_name,
            'label_text' => $label_text,
            'tooltip' => $tooltip,
            'required' => $required,
            'platform' => $platform,
            'maps_to_text' => $maps_to_text,
            'asr_upc_prefixes' => $asr_upc_prefixes,
        ]);
    }
}
