<div class="form-control {{ $required }}">
    <label class="col-form-label" for="{{ $id }}">{{ $label_text }}</label>
    <small>Limit: {{ $limit }}</small>
    <input type="text" name="{{ $element_name }}" data-limit="{{ $limit }}" class="{{ $classStrDefault }}"
        id="{{ $id }}" placeholder="{{ $placeholder }}" {{ $required }}>
    <div class="comma-sep-options">
        @foreach ($display_sets as $display_set_name => $display_names)
            <p>{{ $display_set_name }}</p>
            @foreach ($display_names as $option_val => $option_name)
                @php
                    $checkbox_id = $element_name . $label_text . "-checkbox" . $checkbox_count;
                    $checkbox_count++;
                @endphp
                <div class="">
                    <label for="{{ $checkbox_id }}" class="">
                        <input id="{{ $checkbox_id }}" value="{{ $option_val }}" type="checkbox">
                        {{ ucwords(preg_replace('/\s|_/', ' ', $option_name)) }}
                    </label>
                </div>
            @endforeach
        @endforeach
    </div>
    <p class="form-text">{!! $tooltip !!}</p>
    <p class="form-text">{!! $maps_to_text !!}</p>
</div>
