<div @class(['form-group', 'required' => $params->required])>
    <label class="control-label" for="{{ $id }}">
        <input type="checkbox" name="{{ $element_name }}" id="{{ $id }}"
            @checked(old($element_name, $checked))>{{ $label_text }}
    </label>
    <p class="help-block">{!! $tooltip !!}</p>
    <p class="help-block">{!! $maps_to_text !!}</p>
</div>
