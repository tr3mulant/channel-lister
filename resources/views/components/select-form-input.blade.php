<div class="form-group {{ $required }}">
    <label class="col-form-label" for="{{ $id }}">{{ $label_text }}</label>
    <select name="{{ $element_name }}" class="{{ $classStrDefault }} {{ $select_type }}" data-size="10"
        data-live-search="{{ $select_search }}" id="{{ $id }}" placeholder="{{ $placeholder }}" {{ $required }}
        title="Select...">
        @foreach ($display_names as $option => $display_name)
            @php
                $style = '';
                if (strlen($display_name) > 256) {
                    $style = 'white-space:normal;';
                }
            @endphp
            <option style="{{ $style }}" value="{{ $option }}">{{ $display_name }}</option>
        @endforeach
    </select>
    <p class="form-text">{!! $tooltip !!}</p>
    <p class="form-text">{!! $maps_to_text !!}</p>
</div>
