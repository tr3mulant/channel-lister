<div id="bundle-components-container" class="d-none">
    <div id="bundle-components-list" class="container">
        <div class="row">
            <div class="col-lg-5">
                <label class="col-form-label">Bundle Component SKUs</label>
            </div>
            <div class="col-lg-3">
                <label class="col-form-label">Supplier Codes</label>
            </div>
            <div class="col-lg-2">
                <label class="col-form-label">Quantity</label>
            </div>
        </div>
        <x-channel-lister::custom.sku-bundle-component-input-row :is-first="true" />
    </div>

    <div id="bundled-id" class="form-group {{ $required }}">
        <label class="col-form-label" for="{{ $id }}">{{ $label_text }}</label>
        <input id="{{ $id }}" pattern="{{ $pattern }}" placeholder="{{ $placeholder }}"
            {{ $required }} class="form-control" readonly="" name="{{ $element_name }}">
        <p class="form-text">{!! $tooltip !!}</p>
        <p class="form-text">{!! $maps_to_text !!}</p>
    </div>
</div>
