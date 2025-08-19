<div @class(['form-group', 'required' => $required])>
    <label class="col-form-label font-weight-bold" for="{{ $id }}">{{ $label_text }}</label>
    <select name="{{ $element_name }}" @if ($select_type === 'selectpicker') data-style="bg-white border" @endif
        @class([
            $classStrDefault,
            $select_type,
            'bg-white' => $select_type !== 'selectpicker',
        ]) data-size="10" data-live-search="{{ $select_search }}" id="{{ $id }}"
        title="Select..." @required($required)>
        @foreach ($display_names as $option => $display_name)
            <option @style(['white-space:normal' => strlen($display_name) > 256]) value="{{ $option }}">{{ $display_name }}</option>
        @endforeach
    </select>
    <p class="form-text text-secondary">{!! $tooltip !!}</p>
    <p class="form-text text-secondary">{!! $maps_to_text !!}</p>
</div>
