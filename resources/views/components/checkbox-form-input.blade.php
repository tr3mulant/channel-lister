<div @class(['form-group', 'required' => $params->required])>
    <label class="col-form-label" for="{{ $id }}">
        <input type="checkbox" name="{{ $element_name }}" id="{{ $id }}"
            @checked(old($element_name, $checked))>{{ $label_text }}
    </label>
    <p class="form-text">{!! $tooltip !!}</p>
    <p class="form-text">{!! $maps_to_text !!}</p>
</div>
