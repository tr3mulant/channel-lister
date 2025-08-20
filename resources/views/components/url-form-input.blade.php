<div class="form-group {{ $required }}">
    <label class="col-form-label" for="{{ $id }}">{{ $label_text }}</label>
    <input type="url" name="{{ $element_name }}" class="{{ $classStrDefault }}" id="{{ $id }}"
        @if($pattern) {{ $pattern }} @endif placeholder="{{ $placeholder }}" {{ $required }}>
    <p class="form-text">{!! $tooltip !!}</p>
    <p class="form-text">{!! $maps_to_text !!}</p>
    <div class="iframe-wrap">
        <iframe class="url-preview d-none" src=""></iframe>
    </div>
</div>
