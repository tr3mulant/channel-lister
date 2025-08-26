<?php

namespace IGE\ChannelLister\View\Components\Custom;

use IGE\ChannelLister\Models\ChannelListerField;
use Illuminate\View\Component;

class Upc extends Component
{
    public function __construct(public ChannelListerField $params)
    {
        //
    }

    public function render()
    {
        $element_name = $this->params->field_name;
        $label_text = $this->params->display_name;
        $tooltip = empty($this->params->tooltip) ? 'UPC must be different for every listing.' : $this->params->tooltip;
        $id = $this->params->field_name.'-id';
        $placeholder = $this->params->example ?? '';
        $required = $this->params->required ? 'required' : '';
        $platform = $this->params->marketplace;
        $maps_to_text = 'Maps To: <code>'.$this->params->field_name.'</code>';
        $user_defined_upc_prefixes = config('channel-lister.upc_prefixes', []);

        return view('channel-lister::components.custom.upc', data: [
            'params' => $this->params,
            'element_name' => $element_name,
            'label_text' => $label_text,
            'tooltip' => $tooltip,
            'id' => $id,
            'placeholder' => $placeholder,
            'required' => $required,
            'platform' => $platform,
            'maps_to_text' => $maps_to_text,
            'user_defined_upc_prefixes' => $user_defined_upc_prefixes,
        ]);
    }
}
