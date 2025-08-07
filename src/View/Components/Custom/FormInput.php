<?php

namespace IGE\ChannelLister\View\Components\Custom;

use IGE\ChannelLister\Models\ChannelListerField;
use Illuminate\View\Component;

class FormInput extends Component
{
    public function __construct(public ChannelListerField $params, public string $classStrDefault = 'form-group')
    {
        //
    }

    public function render()
    {
        return view('channel-lister::components.custom.form-input', [
            'params' => $this->params,
            'classStrDefault' => $this->classStrDefault,
        ]);
    }
}
