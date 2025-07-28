<?php

namespace IGE\ChannelLister\View\Components;

use IGE\ChannelLister\Models\ChannelListerField;
use Illuminate\View\Component;

class CustomFormInput extends Component
{
    public function __construct(public ChannelListerField $params)
    {
        //
    }

    public function render()
    {
        return view('channel-lister::components.custom-form-input');
    }
}
