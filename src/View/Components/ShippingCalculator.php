<?php

namespace IGE\ChannelLister\View\Components;

use Illuminate\View\Component;

class ShippingCalculator extends Component
{
    public function __construct(
        public string $classStrDefault = 'form-control',
        public bool $showDimensionalCalculator = true,
        public string $fromZip = '',
        public string $toZip = ''
    ) {
        //
    }

    public function render()
    {
        return view('channel-lister::components.shipping-calculator', [
            'classStrDefault' => $this->classStrDefault,
            'showDimensionalCalculator' => $this->showDimensionalCalculator,
            'fromZip' => $this->fromZip,
            'toZip' => $this->toZip,
        ]);
    }
}
