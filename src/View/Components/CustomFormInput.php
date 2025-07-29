<?php

namespace IGE\ChannelLister\View\Components\Custom;

use IGE\ChannelLister\Models\ChannelListerField;
use Illuminate\View\Component;

/**
 * This is placeholder for v/ChannelLister.
 * I don't expect to keep this either as we should expect to extract the view components that exist
 * in v/ChannelLister into their own component classes and associated views.
 */
class FormInput extends Component
{
    public function __construct(public ChannelListerField $params, public string $classStrDefault)
    {
        //
    }

    public function render()
    {

        // HUGE switch case statement incoming:
        // TODO making customComponents for each of the switch case statement arguments

        return view('channel-lister::components.custom.form-input', data: [
            'params' => $this->params,
            'classStrDefault' => $this->classStrDefault,
        ]);
    }
}
