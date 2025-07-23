<div class="form-group {{ $required }}">
    <label class="control-label" for="{{ $id }}">{{ $label_text }}</label>
    <input type="text" name="{{ $element_name }}" class="{{ $this->class_str_default }}" id="{{ $id }}"
        {{ $pattern }} placeholder="{{ $placeholder }}" {{ $required }}>
    <p class="help-block">{{ $tooltip }}</p>
    <p class="help-block">{{ $maps_to_text }}</p>
</div>