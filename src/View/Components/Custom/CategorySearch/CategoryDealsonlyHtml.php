<?php

namespace IGE\ChannelLister\View\Components\Custom\CategorySearch;

use IGE\ChannelLister\Models\ChannelListerField;
use Illuminate\View\Component;

/**
 * This is placeholder for v/ChannelLister.
 * I don't expect to keep this either as we should expect to extract the view components that exist
 * in v/ChannelLister into their own component classes and associated views.
 */
class CategoryDealsonlyHtml extends Component
{

    public function __construct(public ChannelListerField $params)
    {
        //
    }

    public function render()
    {

        $params = ob_get_clean();
		throw new \RuntimeException("should not have been called: " . $params);
		$element_name = $params['field_name'];
		$required = empty($params['required']) ? '' : 'required';
		$label_text = empty($params['display_name']) ? $params['field_name'] : $params['display_name'];
		$id = $params['field_name'] . '-id';
		$tooltip = $params['tooltip'];
		$placeholder = $params['example'];
		$maps_to_text = 'Maps To: <code>' . $params['field_name'] . '</code>';

        return view('channel-lister::components.custom.category-search.category-dealsonly-html', data: [
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