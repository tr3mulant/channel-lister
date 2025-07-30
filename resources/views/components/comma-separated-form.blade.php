<div class="form-control {{ $required }}">
    <label class="col-form-label" for="{{ $id }}">{{ $label_text }}</label>
    <input type="text" name="{{ $element_name }}" class="{{ $classStrDefault }}" id="{{ $id }}"
        placeholder="{{ $placeholder }}" {{ $required }}>
    <div class="comma-sep-options">
        @php $checkbox_count = 0; @endphp
        @foreach ($display_names as $option_val => $option_name)
            @php
                $checkbox_id = $element_name . $label_text . "-checkbox" . $checkbox_count;
                $checkbox_count++;
            @endphp
            <div class="checkbox-inline">
                <label for="{{ $checkbox_id }}" class="checkbox-inline">
                    <input id="{{ $checkbox_id }}" value="{{ $option_val }}" type="checkbox">{{ $option_name }}
                </label>
            </div>
        @endforeach
    </div>
    <p class="form-text">{!! $tooltip !!}</p>
    <p class="form-text">{!! $maps_to_text !!}</p>
</div>
