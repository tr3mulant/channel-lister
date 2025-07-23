<div id="bundle-components-div" class="container hidden">

    {{-- TODO check if this set up is correct --}}
    <x-write-sku-bundle-component-input-row params={{ $params }} />
    <!--< ?php echo $this->writeSkuBundleComponentInputRow(true); ?>-->
</div><!-- <br class="clearfloat"> -->
<!-- </div> -->
<div id="bundled-id" class="hidden form-group {{ $required }}">
    <label class="control-label" for="{{ $id }}">{{ $label_text }}</label>
    <input id="{{ $id }}" pattern="{{ $pattern }}" placeholder="{{ $placeholder }}" {{ $required }}
        class="form-control" readonly="" name="{{ $element_name }}">
    <p class="help-block">{{ $tooltip }}</p>
    <p class="help-block">{{ $maps_to_text }}</p>
</div>