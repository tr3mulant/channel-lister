<?php

namespace IGE\ChannelLister\View\Components\Custom;

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

    public string $class = 'remove-row btn btn-primary p-1';

    public string $value = 'Remove Row';

    public string $icon_class = 'icon-minus';

    public function __construct(public bool $isFirst = false) {}

    public function render()
    {
        if ($this->isFirst) {
            $this->id = 'add-component-button';
            $this->title = 'Add Bundle Component Row';
            $this->class = 'add-row btn btn-primary p-1';
            $this->value = 'Add Row';
            $this->icon_class = 'icon-plus';
        }

        return view('channel-lister::components.custom.sku-bundle-component-input-row');
    }
}
