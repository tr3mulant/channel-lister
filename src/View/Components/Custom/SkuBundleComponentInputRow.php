<?php

namespace IGE\ChannelLister\View\Components\Custom;

use IGE\ChannelLister\Models\ChannelListerField;
use Illuminate\View\Component;

/**
 * This is placeholder for v/ChannelLister.
 * I don't expect to keep this either as we should expect to extract the view components that exist
 * in v/ChannelLister into their own component classes and associated views.
 */
class SkuBundleComponentInputRow extends Component
{
    public string $id = '';

    public string $title = 'Remove Bundle Component Row';

    public string $class = 'remove-row btn btn-primary btn-md';

    public string $value = 'Remove Row';

    public string $icon_class = 'glyphicon glyphicon-minus-sign';

    public function __construct(public ChannelListerField $params, public bool $isFirst = false) {}

    public function render()
    {
        if ($this->isFirst) {
            $this->id = 'add-component-button';
            $this->title = 'Add Bundle Component Row';
            $this->class = 'btn btn-primary btn-md';
            $this->value = 'Add Row';
            $this->icon_class = 'glyphicon glyphicon-plus-sign';
        }

        return view('channel-lister::components.custom.sku-bundle-component-input-row');
    }
}
