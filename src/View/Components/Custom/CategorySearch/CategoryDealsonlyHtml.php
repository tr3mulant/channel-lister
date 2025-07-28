<?php

namespace IGE\ChannelLister\View\Components\Custom\CategorySearch;

use IGE\ChannelLister\Models\ChannelListerField;
use Illuminate\View\Component;

/**
 * This is placeholder for v/ChannelLister.
 * I don't expect to keep this either as we should expect to extract the view components that exist
 * in v/ChannelLister into their own component classes and associated views.
 *
 * @deprecated This component is not implemented and should not be used.
 */
class CategoryDealsonlyHtml extends Component
{
    public function __construct(public ChannelListerField $params)
    {
        //
    }

    /**
     * Render the component.
     *
     * @throws \RuntimeException
     */
    public function render()
    {
        throw new \RuntimeException('should not have been called: '.$this->params);
    }
}
