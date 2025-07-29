<div id="bundle-components-div" class="container hidden">
    <x-channel-lister::custom.sku-bundle-component-input-row :params="$params" />
</div>
<div id="bundled-id" class="hidden form-group {{ $required }}">
    <label class="control-label" for="{{ $id }}">{{ $label_text }}</label>
    <input id="{{ $id }}" pattern="{{ $pattern }}" placeholder="{{ $placeholder }}" {{ $required }}
        class="form-control" readonly="" name="{{ $element_name }}">
    <p class="help-block">{!! $tooltip !!}</p>
    <p class="help-block">{!! $maps_to_text !!}</p>
</div>
