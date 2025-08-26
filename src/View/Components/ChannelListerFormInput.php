<?php

namespace IGE\ChannelLister\View\Components;

use IGE\ChannelLister\Models\ChannelListerField;
use Illuminate\View\Component;

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
