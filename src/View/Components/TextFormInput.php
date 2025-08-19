<?php

namespace IGE\ChannelLister\View\Components;

use IGE\ChannelLister\Models\ChannelListerField;
use Illuminate\View\Component;

/**
 * This is placeholder for v/ChannelLister.
 * I don't expect to keep this either as we should expect to extract the view components that exist
 * in v/ChannelLister into their own component classes and associated views.
 */
class TextFormInput extends Component
{
    public function __construct(public ChannelListerField $params, public string $classStrDefault)
    {
        //
    }

    public function render()
    {
        return view('channel-lister::components.text-form-input', data: $this->getOptions());
    }

    /**
     * Return an array of options to be used in the view.
     *
     * @return array<string, null|string|bool>
     */
    protected function getOptions(): array
    {
        $element_name = $this->params->field_name;
        $element_name_override = '';
        if (property_exists($this->params, 'field_name_override') && $this->params->field_name_override !== null && ! empty($this->params->field_name_override)) {
            $parts = explode('||', $this->params->field_name_override);
            foreach ($parts as $part) {
                $pieces = explode('==', $part);
                $element_name_override .= "Maps to: <code>{$pieces[1]}</code> if marketplace is $pieces[0]</br>";
            }
        }
        $pattern = empty($this->params->input_type_aux) ? '' : $this->params->input_type_aux;
        $required = $this->params->required ? 'required' : '';
        $readonly = property_exists($this->params, 'readonly') && $this->params->readonly;
        $label_text = empty($this->params->display_name) ? $this->params->field_name : $this->params->display_name;
        $id = $this->params->field_name.'-id';
        $tooltip = $this->params->tooltip;
        $maps_to_text = 'Maps To: <code>'.$this->params->field_name.'</code>';
        if ($element_name_override !== '' && $element_name_override !== '0') {
            $maps_to_text .= '</br>'.$element_name_override;
        }

        return [
            'element_name' => $element_name,
            'element_name_override' => $element_name_override,
            'pattern' => $pattern,
            'required' => $required,
            'readonly' => $readonly,
            'label_text' => $label_text,
            'id' => $id,
            'tooltip' => $tooltip,
            'placeholder' => $this->params->example,
            'max_len' => $this->getMaxLengthFromRegex($pattern),
            'maps_to_text' => $maps_to_text,
        ];
    }

    /**
     * Given a regex, looks for an ending number right before }$ and returns that as max length
     * Note: This method is naive but currently works for our needs
     *
     * @param  string  $regex_str  regex that possibly has a max length ex: ^[^®^™*_]{10,80}$
     * @return string|null returns the max length if found
     */
    protected function getMaxLengthFromRegex($regex_str): ?string
    {
        $regex = '/(?<maxlen>\d+)\}\$/';
        $matches = [];
        if (preg_match_all($regex, $regex_str, $matches) && substr_count($regex_str, '}') == 1) {
            return strval($matches['maxlen'][0]);
        }

        return null;
    }
}
