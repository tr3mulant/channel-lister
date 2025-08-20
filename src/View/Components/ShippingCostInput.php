<?php

namespace IGE\ChannelLister\View\Components;

use IGE\ChannelLister\Models\ChannelListerField;
use Illuminate\View\Component;

class ShippingCostInput extends Component
{
    public function __construct(
        public ChannelListerField $params,
        public string $classStrDefault
    ) {
        //
    }

    public function render()
    {
        $element_name = $this->params->field_name;
        $required = $this->params->required ? 'required' : '';
        $label_text = empty($this->params->display_name) ? $this->params->field_name : $this->params->display_name;
        $name = $this->params->field_name;
        $id = $name.'-id';
        $tooltip = $this->params->tooltip;
        $placeholder = $this->params->example;
        $maps_to_text = 'Maps To: <code>'.$this->params->field_name.'</code>';

        return view('channel-lister::components.shipping-cost-input', data: [
            'params' => $this->params,
            'element_name' => $element_name,
            'required' => $required,
            'label_text' => $label_text,
            'id' => $id,
            'tooltip' => $tooltip,
            'placeholder' => $placeholder,
            'maps_to_text' => $maps_to_text,
        ]);
    }
}
