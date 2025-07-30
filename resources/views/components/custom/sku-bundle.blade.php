<div id="bundle-components-div" class="container hidden">
    <x-channel-lister::custom.sku-bundle-component-input-row :params="$params" />
</div>
<div id="bundled-id" class="hidden form-control {{ $required }}">
    <label class="col-form-label" for="{{ $id }}">{{ $label_text }}</label>
    <input id="{{ $id }}" pattern="{{ $pattern }}" placeholder="{{ $placeholder }}" {{ $required }}
        class="form-control" readonly="" name="{{ $element_name }}">
    <p class="form-text">{!! $tooltip !!}</p>
    <p class="form-text">{!! $maps_to_text !!}</p>
</div>
