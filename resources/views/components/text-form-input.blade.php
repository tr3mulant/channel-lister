<div class="form-group {{ $required }}">
    <label class="control-label" for="{{ $id }}">{{ $label_text }}</label>
    <input type="text" name="{{ $element_name }}" class="{{ $classStrDefault }}" id="{{ $id }}"
        {{ $pattern }} {{ $max_len_str }} placeholder="{{ $placeholder }}" {{ $required }}
        {{ $readonly }}>
    <p class="help-block">{!! $tooltip !!}</p>
    <p class="help-block">{!! $maps_to_text !!}</p>
</div>
