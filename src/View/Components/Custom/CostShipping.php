<?php

namespace IGE\ChannelLister\View\Components\Custom;

use IGE\ChannelLister\Models\ChannelListerField;
use Illuminate\View\Component;

/**
 * This is placeholder for v/ChannelLister.
 * I don't expect to keep this either as we should expect to extract the view components that exist
 * in v/ChannelLister into their own component classes and associated views.
 */
class CostShipping extends Component
{
    public function __construct(public ChannelListerField $params, public string $classStrDefault = 'form-group')
    {
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

        return view('channel-lister::components.custom.cost-shipping', data: [
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
