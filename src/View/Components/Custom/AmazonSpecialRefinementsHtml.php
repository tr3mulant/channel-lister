<?php

namespace IGE\ChannelLister\View\Components\Custom;

use IGE\ChannelLister\Models\ChannelListerField;
use Illuminate\View\Component;

/**
 * This is placeholder for v/ChannelLister.
 * I don't expect to keep this either as we should expect to extract the view components that exist
 * in v/ChannelLister into their own component classes and associated views.
 */
class AmazonSpecialRefinementsHtml extends Component
{

    public function __construct(public ChannelListerField $params)
    {
        //
    }

    public function render()
    {
        $element_name = $this->params->field_name;
		$options = $this->params->input_type_aux;
		$required = empty($this->params->required) ? '' : 'required';
		$label_text = empty($this->params->display_name) ? $this->params->field_name : $this->params->display_name;
		$id = $this->params->field_name . '-id';
		$tooltip = $this->params->tooltip;
		$placeholder = $this->params->example;
		$maps_to_text = 'Maps To: <code>' . $this->params->field_name . '</code>';
		$limit = $options["limit"];
		$display_sets = [];
		foreach ($options as $key => $option_set) {
			if ($key !== "limit") {
				$display_sets[ucwords(str_replace("_", " ", $key))] = [];
				$options_group = explode("||", $option_set);
				foreach ($options_group as $value) {
					$display_sets[ucwords(str_replace("_", " ", $key))][$value] = $value;
				}
			}
		}
		$checkbox_count = 1;

        return view('channel-lister::components.custom.amazon-special-refinements-html', data: [
            'element_name' => $element_name,
            'options' => $options,
            'required' => $required,
            'label_text' => $label_text,
            'id' => $id,
            'tooltip' => $tooltip,
            'placeholder' => $placeholder,
            'maps_to_text' => $maps_to_text,
            'limit' => $limit,
            'display_sets' => $display_sets,
            'checkbox_count' => $checkbox_count,
        ]);
    }

}