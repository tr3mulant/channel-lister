<?php

namespace IGE\ChannelLister\View\Components;

use IGE\ChannelLister\Models\ChannelListerField;
use Illuminate\View\Component;

/**
 * This is placeholder for v/ChannelLister.
 * I don't expect to keep this either as we should expect to extract the view components that exist
 * in v/ChannelLister into their own component classes and associated views.
 */
class AlertMessage extends Component
{
    const VALID_ALERT_TYPES = ['success', 'info', 'warning', 'danger'];

    public function __construct(public ChannelListerField $params, public string $classStrDefault = 'form-group')
    {
        //
    }

    public function render()
    {
        $alert_type = $this->params->getInputTypeAuxOptions();
        if ($alert_type === '' || $alert_type === '0' || $alert_type === [] || $alert_type === null) {
            $alert_type = 'info';
        } elseif (is_array($alert_type)) {
            $alert_type = $alert_type[0]; // Assuming the first element is the type
        }
        $alert_type = strtolower($alert_type);
        if (! in_array($alert_type, self::VALID_ALERT_TYPES)) {
            throw new \RuntimeException("Invalid alert type '$alert_type' in field 'input_type_aux' must be one of ".implode(', ', self::VALID_ALERT_TYPES));
        }
        $name = empty($this->params['display_name']) ? $this->params['field_name'] : $this->params['display_name'];
        $message = $this->params['tooltip'];
        $additional_text = $this->params['example'];

        //
        return view('channel-lister::components.alert-message', data: [
            'alert_type' => $alert_type,
            'name' => $name,
            'message' => $message,
            'additional_text' => $additional_text,
        ]);
    }
}
