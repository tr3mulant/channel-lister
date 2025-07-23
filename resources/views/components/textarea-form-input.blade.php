<div class="form-group {{ $required }}">
    <label class="control-label" for="{{ $id }}">{{ $label_text }}</label>
    <textarea name="{{ $element_name }}" class="{{ $this->class_str_default }} col-xs-2" id="{{ $id }}"
        placeholder="{{ $placeholder }}" {{ $required }}></textarea>
    <p class="help-block">{{ $tooltip }}</p>
    <p class="help-block">{{ $maps_to_text }}</p>
</div>