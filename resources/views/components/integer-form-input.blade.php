<div @class=(['form-group', 'required'=> $params->required])>
    <label class="control-label" for="{{ $id }}">{{ $label_text }}</label>
    <input type="number" name="{{ $element_name }}" class="{{ $classStrDefault }} col-xs-2" id="{{ $id }}"
        step="1" min="0" placeholder="{{ $placeholder }}" {{ $required }}>
    <p class="help-block">{!! $tooltip !!}</p>
    <p class="help-block">{!! $maps_to_text !!}</p>
</div>
