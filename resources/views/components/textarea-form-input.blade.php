<div class="form-group {{ $required }}">
    <label class="col-form-label font-weight-bold" for="{{ $id }}">{{ $label_text }}</label>
    <textarea name="{{ $element_name }}" class="{{ $classStrDefault }} col-xs-2" id="{{ $id }}"
        placeholder="{{ $placeholder }}" {{ $required }}></textarea>
    <p class="form-text text-secondary">{!! $tooltip !!}</p>
    <p class="form-text text-secondary">{!! $maps_to_text !!}</p>
</div>
