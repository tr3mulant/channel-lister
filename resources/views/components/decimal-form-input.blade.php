<div class="form-group {{ $required }}">
    <label class="col-form-label font-weight-bold" for="{{ $id }}">{{ $label_text }}</label>
    <input type="number" name="{{ $element_name }}" class="{{ $classStrDefault }}" id="{{ $id }}" min="0"
        step="{{ $step_size }}" placeholder="{{ $placeholder }}" {{ $required }}>
    <p class="form-text text-secondary">{!! $tooltip !!}</p>
    <p class="form-text text-secondary">{!! $maps_to_text !!}</p>
</div>
