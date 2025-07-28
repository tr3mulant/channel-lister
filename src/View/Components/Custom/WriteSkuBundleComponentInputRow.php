<?php

namespace IGE\ChannelLister\View\Components\Custom;

use IGE\ChannelLister\Models\ChannelListerField;
use Illuminate\View\Component;

/**
 * This is placeholder for v/ChannelLister.
 * I don't expect to keep this either as we should expect to extract the view components that exist
 * in v/ChannelLister into their own component classes and associated views.
 */
class WriteSkuBundleComponentInputRow extends Component
{
    public function __construct(public ChannelListerField $params, public bool $isFirst = false) {}

    public function render()
    {

        $id = '';
        $title = 'Remove Bundle Component Row';
        $class = 'remove-row btn btn-primary btn-md';
        $value = 'Remove Row';
        $icon_class = 'glyphicon glyphicon-minus-sign';

        if ($this->isFirst) {
            $id = 'add-component-button';
            $title = 'Add Bundle Component Row';
            $class = 'btn btn-primary btn-md';
            $value = 'Add Row';
            $icon_class = 'glyphicon glyphicon-plus-sign';
        }

        return view('channel-lister::components.custom.write-sku-bundle-component-input-html', data: [
            'id' => $id,
            'title' => $title,
            'class' => $class,
            'value' => $value,
            'icon_class' => $icon_class,
        ]);
    }
}
