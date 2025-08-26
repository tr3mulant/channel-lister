<div @class(['form-group mb-2', 'required' => $params->required])>
    <label class="col-form-label font-weight-bold" for="{{ $id }}">{{ $label_text }}</label>
    <input type="text" name="{{ $element_name }}" class="{{ $classStrDefault }}" id="{{ $id }}"
        @if ($pattern) pattern="{{ $pattern }}" @endif
        @if ($max_len) maxlength="{{ $max_len }}" @endif
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif @required($params->required) @readonly($readonly)>
    <p class="form-text mt-1 mb-2 leading-5-25 text-secondary">{!! $tooltip !!}</p>
    <p class="form-text mt-1 mb-2 leading-5-25 text-secondary">{!! $maps_to_text !!}</p>
</div>
