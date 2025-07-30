<div class="form-control {{ $required }}">
    <label class="col-form-label" for="{{ $id }}">{{ $label_text }}</label>
    <textarea name="{{ $element_name }}" class="{{ $classStrDefault }} col-xs-2" id="{{ $id }}"
        placeholder="{{ $placeholder }}" {{ $required }}></textarea>
    <p class="form-text">{!! $tooltip !!}</p>
    <p class="form-text">{!! $maps_to_text !!}</p>
</div>
