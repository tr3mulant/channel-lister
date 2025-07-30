<div class="form-group {{ $required }}">
    <label class="col-form-label" for="{{ $id }}">{{ $label_text }}</label>
    <input type="number" name="{{ $element_name }}" class="{{ $classStrDefault }}" id="{{ $id }}" step="0.01"
        min="0" placeholder="{{ $placeholder }}" {{ $required }} readonly>
    <p class="form-text">{!! $tooltip !!}</p>
    <p class="form-text">{!! $maps_to_text !!}</p>
</div>
