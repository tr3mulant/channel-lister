<div class="form-group mb-2 {{ $required }}">
    <label class="col-form-label font-weight-bold" for="{{ $id }}">{{ $label_text }}</label>
    <input type="text" name="{{ $element_name }}" class="{{ $classStrDefault }}" id="{{ $id }}"
        pattern="{{ $pattern }}" {{ $max_len_str }} placeholder="{{ $placeholder }}" {{ $required }}
        @readonly($readonly)>
    <p class="form-text mt-1 mb-2 leading-5-25">{!! $tooltip !!}</p>
    <p class="form-text mt-1 mb-2 leading-5-25">{!! $maps_to_text !!}</p>
</div>
