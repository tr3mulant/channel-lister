<div @class(['form-group', 'required' => $params->required])>
    <label class="col-form-label font-weight-bold" for="{{ $id }}">
        <input class="mr-2" type="checkbox" name="{{ $element_name }}" id="{{ $id }}"
            @checked(old($element_name, $checked))>{{ $label_text }}
    </label>
    <p class="form-text text-secondary">{!! $tooltip !!}</p>
    <p class="form-text text-secondary">{!! $maps_to_text !!}</p>
</div>
