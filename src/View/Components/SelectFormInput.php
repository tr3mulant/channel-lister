<?php

namespace IGE\ChannelLister\View\Components;

use IGE\ChannelLister\Models\ChannelListerField;
use Illuminate\View\Component;

/**
 * This is placeholder for v/ChannelLister.
 * I don't expect to keep this either as we should expect to extract the view components that exist
 * in v/ChannelLister into their own component classes and associated views.
 */
class SelectFormInput extends Component
{

    public function __construct(public ChannelListerField $params)
    {
        //
    }

    public function render()
    {
        //TODO may need to come back and make sure syntax is correct
        $element_name = $this->params->field_name;
		$options = $this->explodeOndoubleBar($this->params->input_type_aux);
		$required = empty($this->params->required) ? '' : 'required';
		$label_text = empty($this->params->display_name) ? $this->params->field_name : $this->params->display_name;
		$id = $this->params->field_name . '-id';
		$tooltip = $this->params->tooltip;
		$placeholder = $this->params->example;
		$maps_to_text = 'Maps To: <code>' . $this->params->field_name . '</code>';
		$display_names = [];
		$select_type = 'select-picker';//default select type
		$select_search = count($options) > 10 ? 'true' : 'false';
		$display_names = $this->explodeOnEqualsEquals($options);
		//make select into select-picker or editable-select
		if (array_key_exists('__OTHER__', $display_names)) {
			unset($display_names['__OTHER__']);
			$select_type = 'editable-select';
		}

        //
        return view('channel-lister::components.select-form-input', data: [
            'params' => $this->params,
            'element_name' => $element_name,
            'options' => $options,
            'required' => $required,
            'label_text' => $label_text,
            'id' => $id,
            'tooltip' => $tooltip,
            'placeholder' => $placeholder,
            'maps_to_text' => $maps_to_text,
            'display_names' => $display_names,
            'select_type' => $select_type,
            'select_search' => $select_search,
        ]);
    }

    protected function explodeOndoubleBar($input_aux)
	{
		return explode('||', $input_aux);
	}
    
	protected function explodeOnEqualsEquals($options)
	{
		$display_names = [];
		foreach ($options as $option) {
			$parts = explode('==', $option);
			$display_names[$parts[0]] = $parts[1] ?? ucwords($parts[0]);
		}
		return $display_names;
	}
}