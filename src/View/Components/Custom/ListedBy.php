<?php

namespace IGE\ChannelLister\View\Components\Custom;

use IGE\ChannelLister\Models\ChannelListerField;
use Illuminate\View\Component;

/**
 * This is placeholder for v/ChannelLister.
 * I don't expect to keep this either as we should expect to extract the view components that exist
 * in v/ChannelLister into their own component classes and associated views.
 */
class ListedBy extends Component
{
    public function __construct(public ChannelListerField $params, public string $classStrDefault = 'form-control')
    {
        //
    }

    public function render()
    {
        $element_name = $this->params->field_name;
        $patternRegex = $this->params->input_type_aux;
        if (! is_string($patternRegex)) {
            $patternRegex = '';
        }
        $pattern = $patternRegex === '' || $patternRegex === '0' ? '' : 'pattern="'.$patternRegex.'"';
        $required = empty($this->params->required) ? '' : 'required';
        $label_text = $this->params->display_name;
        $id = $this->params->field_name.'-id';
        $tooltip = $this->params->tooltip;
        $placeholder = $this->params->example;
        $maps_to_text = 'Maps To: <code>'.$this->params->field_name.'</code>';
        $ml_user = $_SERVER['REMOTE_USER'] ?? '';

        return view('channel-lister::components.custom.listed-by', data: [
            'element_name' => $element_name,
            'pattern' => $pattern,
            'required' => $required,
            'label_text' => $label_text,
            'id' => $id,
            'tooltip' => $tooltip,
            'placeholder' => $placeholder,
            'maps_to_text' => $maps_to_text,
            'ml_user' => $ml_user,
            'classStrDefault' => $this->classStrDefault,
        ]);
    }
}
