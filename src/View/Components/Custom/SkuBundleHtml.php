<?php

namespace IGE\ChannelLister\View\Components\Custom;

use IGE\ChannelLister\Models\ChannelListerField;
use Illuminate\View\Component;

/**
 * This is placeholder for v/ChannelLister.
 * I don't expect to keep this either as we should expect to extract the view components that exist
 * in v/ChannelLister into their own component classes and associated views.
 */
class SkuBundleHtml extends Component
{
    public function __construct(public ChannelListerField $params)
    {
        //
    }

    public function render()
    {

        $id = $this->params->field_name.'-id';
        $element_name = $this->params->field_name;
        $label_text = empty($this->params->display_name) ? $this->params->field_name : $this->params->display_name;
        $required = empty($this->params->required) ? '' : 'required';
        $label_text = empty($this->params->display_name) ? $this->params->field_name : $this->params->display_name;
        $tooltip = $this->params->tooltip;
        $pattern = empty($this->params->input_type_aux) ? '' : "pattern='{$this->params->input_type_aux}'";
        $placeholder = $this->params->example;
        $maps_to_text = 'Maps To: <code>'.$this->params->field_name.'</code>';

        return view('channel-lister::components.custom.sku-bundle-html', data: [
            'id' => $id,
            'element_name' => $element_name,
            'label_text' => $label_text,
            'required' => $required,
            'tooltip' => $tooltip,
            'pattern' => $pattern,
            'placeholder' => $placeholder,
            'maps_to_text' => $maps_to_text,
        ]);
    }
}
