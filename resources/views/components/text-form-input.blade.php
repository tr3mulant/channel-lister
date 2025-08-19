<div class="form-group mb-2 {{ $required }}">
    <label class="col-form-label font-weight-bold" for="{{ $id }}">{{ $label_text }}</label>
    <input type="text" name="{{ $element_name }}" class="{{ $classStrDefault }}" id="{{ $id }}"
        @if($pattern) pattern="{{ $pattern }}" @endif @if($max_len_str) {{ $max_len_str }} @endif placeholder="{{ $placeholder }}" {{ $required }}
        @readonly($readonly)>
    <p class="form-text mt-1 mb-2 leading-5-25 text-secondary">{!! $tooltip !!}</p>
    <p class="form-text mt-1 mb-2 leading-5-25 text-secondary">{!! $maps_to_text !!}</p>
</div>
