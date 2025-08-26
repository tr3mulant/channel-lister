<div @class(['form-group', 'required' => $params->required])>
    <label class="col-form-label font-weight-bold" for="{{ $id }}">{{ $label_text }}</label>
    <input type="number" name="{{ $element_name }}" class="{{ $classStrDefault }} col-xs-2" id="{{ $id }}"
        step="1" min="0" placeholder="{{ $placeholder }}" {{ $required }}>
    <p class="form-text text-secondary">{!! $tooltip !!}</p>
    <p class="form-text text-secondary">{!! $maps_to_text !!}</p>
</div>
