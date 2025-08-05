<div class="row bundle-component-row">
    <div class="col-md-5">
        <input class="form-control sku-bundle-input" maxlength="40" pattern="^[A-Z\-0-9]{5,40}$">
    </div>
    <div class="col-md-3">
        <input class="form-control supplier-code" minlength="1">
    </div>
    <div class="col-md-2">
        <input type="number" class="form-control sku-bundle-quantity" min="1">
    </div>
    <div class="col-md-2" @style(['padding-top: ' . 3 / 8 / 2 . 'rem'])>
        <button type="button" @if ($id !== '') id="{{ $id }}" @endif
            title="{{ $title }}" @class([$class, $icon_class]) @style(['width: 2rem', 'height: 2rem']) value="{{ $value }}">
        </button>
    </div>
</div>
