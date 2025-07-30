<?php

namespace IGE\ChannelLister\View\Components;

use IGE\ChannelLister\Models\ChannelListerField;
use Illuminate\View\Component;

/**
 * This is placeholder for v/ChannelLister.
 * I don't expect to keep this either as we should expect to extract the view components that exist
 * in v/ChannelLister into their own component classes and associated views.
 */
class CloneSiteCategory extends Component
{
    public function __construct(public ChannelListerField $params, public string $classStrDefault = 'form-control')
    {
        //
    }

    public function render()
    {
        $element_name = $this->params->field_name;
        $required = empty($this->params->required) ? '' : 'required';
        $label_text = empty($this->params->display_name) ? $this->params->field_name : $this->params->display_name;
        $id = $this->params->field_name.'-id';
        $tooltip = $this->params->tooltip;
        $placeholder = $this->params->example;
        $maps_to_text = 'Maps To: <code>'.$this->params->field_name.'</code>';
        $categories = $this->params->getInputTypeAuxOptions();
        if (! is_array($categories)) {
            $categories = [];
        }
        $category_str = '';
        foreach ($categories as $category) {
            $category_str .= '||'.$this->writeCategorySlug($category).'=='.htmlentities((string) $category);
        }

        $this->params->fill([
            'element_name' => $element_name,
            'input_type_aux' => $category_str,
            'required' => $required,
            'label_text' => $label_text,
            'id' => $id,
            'tooltip' => $tooltip,
            'placeholder' => $placeholder,
            'maps_to_text' => $maps_to_text,
            'categories' => $categories,
            'category_str' => $category_str,
        ]);

        return view('channel-lister::components.clone-site-category');
    }

    /**
     * Explode a string on double bar '||' and return an array.
     *
     * @return string[]
     */
    protected function explodeOndoubleBar(?string $input_aux): array
    {
        return explode('||', (string) $input_aux);
    }

    /**
     * Takes a category name and converts it to a URL friendly format
     *
     * @param  string  $cat  category name
     *
     * @retrn string url friendly category string
     *
     * @author Glen MacKay <glen@mackaytech.com>
     */
    protected function writeCategorySlug($cat): ?string
    {
        $cat = str_replace(
            ['&', ','],
            [' ', ''],
            strtolower($cat)
        );

        return preg_replace('/\s+/', '-', $cat);
    }
}
