<div class="form-group {{ $required }}">
    <label class="control-label" for="{{ $id }}">{{ $label_text }}</label>
    <input type="number" name="{{ $element_name }}" class="{{ $this->class_str_default }}" id="{{ $id }}" min="0"
        step="{{ $step_size }}" placeholder="{{ $placeholder }}" {{ $required }}>
    <p class="help-block">{{ $tooltip }}</p>
    <p class="help-block">{{ $maps_to_text }}</p>
</div>