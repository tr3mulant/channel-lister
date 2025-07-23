<?php

namespace IGE\ChannelLister\View\Components;

use IGE\ChannelLister\Models\ChannelListerField;
use Illuminate\View\Component;

/**
 * This is placeholder for v/ChannelLister.
 * I don't expect to keep this either as we should expect to extract the view components that exist
 * in v/ChannelLister into their own component classes and associated views.
 */
class TextFormInputFormInput extends Component
{

    public function __construct(public ChannelListerField $params)
    {
        //
    }

    public function render()
    {

		$element_name = $this->params->field_name;
		$element_name_override = '';
		if (isset($this->params->field_name_override) && !empty($this->params->field_name_override)) {
			$parts = explode('||', $this->params->field_name_override);
			foreach ($parts as $n => $part) {
				$pieces = explode('==', $part);
				$element_name_override .= "Maps to: <code>{$pieces[1]}</code> if marketplace is $pieces[0]</br>";
			}
		}
		$pattern = empty($this->params->input_type_aux) ? '' : 'pattern="' . $this->params->input_type_aux . '"';
		$required = empty($this->params->required) ? '' : 'required';
		$readonly = empty($this->params->readonly) ? '' : 'readonly';
		$label_text = empty($this->params->display_name) ? $this->params->field_name : $this->params->display_name;
		$id = $this->params->field_name . '-id';
		$tooltip = $this->params->tooltip;
		$placeholder = $this->params->example;
		$maps_to_text = 'Maps To: <code>' . $this->params->field_name . '</code>';
		if (!empty($element_name_override)) {
			$maps_to_text .= "</br>" . $element_name_override;
		}
		//look for max length in pattern
		$max_len = $this->getMaxLengthFromRegex($pattern);
		$max_len_str = empty($max_len) ? '' : "maxlength='$max_len'";

		return view('channel-lister::components.text-form-input', data: [
			'params' => $this->params,
			'element_name' => $element_name,
			'element_name_override' => $element_name_override,
			'pattern' => $pattern,
			'required' => $required,
			'readonly' => $readonly,
			'label_text' => $label_text,
			'id' => $id,
			'tooltip' => $tooltip,
			'placeholder' => $placeholder,
			'max_len_str' => $max_len_str,
			'maps_to_text' => $maps_to_text,
		]);
    }

    	/**
	 * Given a regex, looks for an ending number right before }$ and returns that as max length
	 * Note: This method is naive but currently works for our needs
	 * @param  string $regex_str regex that possibly has a max length ex: ^[^®^™*_]{10,80}$
	 * @return int|null            returns the max length if found
	 */
	protected function getMaxLengthFromRegex($regex_str)
	{
		$regex = '/(?<maxlen>\d+)\}\$/';
		$matches = [];
		if (preg_match_all($regex, $regex_str, $matches) && substr_count($regex_str, '}') == 1) {
			return $matches['maxlen'][0];
		} else {
			return null;
		}
	}

}