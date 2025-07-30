<div class="form-group {{ $required }}">
    <label class="col-form-label" for="{{ $id }}">{{ $label_text }}</label>
    <input type="text" name="{{ $element_name }}" class="{{ $classStrDefault }}" id="{{ $id }}"
        {{ $pattern }} placeholder="{{ $placeholder }}" {{ $required }} readonly value="{{ $ml_user }}">
    <p class="form-text">{!! $tooltip !!}</p>
</div>
