<div @class(['form-group', 'required' => $params->required])>
    <label class="col-form-label font-weight-bold" for="{{ $id }}">{{ $label_text }}</label>
    <input type="url" name="{{ $element_name }}" class="{{ $classStrDefault }}" id="{{ $id }}"
        @if ($pattern) pattern="{{ $pattern }}" @endif
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif @required($params->required)>
    <p class="form-text text-secondary">{!! $tooltip !!}</p>
    <p class="form-text text-secondary">{!! $maps_to_text !!}</p>
    <div class="iframe-wrap">
        <iframe class="url-preview d-none" src=""></iframe>
    </div>
</div>
