<div class="form-group {{ $required }}">
    <label class="col-form-label font-weight-bold" for="{{ $id }}">{{ $label_text }}</label>
    <input type="text" name="{{ $element_name }}" class="{{ $classStrDefault }}" id="{{ $id }}"
        {{ $pattern }} placeholder="{{ $placeholder }}" {{ $required }} readonly value="Product Needs Review">
    <p class="form-text text-secondary">{!! $tooltip !!}</p>
    <p class="form-text text-secondary">{!! $maps_to_text !!}</p>
</div>
