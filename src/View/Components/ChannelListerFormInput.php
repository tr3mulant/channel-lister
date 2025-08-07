<?php

namespace IGE\ChannelLister\View\Components;

use IGE\ChannelLister\Models\ChannelListerField;
use Illuminate\View\Component;

/**
 * This is placeholder for v/ChannelLister.
 * I don't expect to keep this either as we should expect to extract the view components that exist
 * in v/ChannelLister into their own component classes and associated views.
 */
class ChannelListerFormInput extends Component
{
    public function __construct(public ChannelListerField $params, public string $classStrDefault = 'form-control')
    {
        //
    }

    public function render()
    {
        return view('channel-lister::components.channel-lister-form-input', [
            // 'params' => $this->params,
            // 'classStrDefault' => $this->classStrDefault,
        ]);
    }
}
