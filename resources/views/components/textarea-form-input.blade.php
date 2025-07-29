<div class="form-group {{ $required }}">
    <label class="control-label" for="{{ $id }}">{{ $label_text }}</label>
    <textarea name="{{ $element_name }}" class="{{ $classStrDefault }} col-xs-2" id="{{ $id }}"
        placeholder="{{ $placeholder }}" {{ $required }}></textarea>
    <p class="help-block">{!! $tooltip !!}</p>
    <p class="help-block">{!! $maps_to_text !!}</p>
</div>
