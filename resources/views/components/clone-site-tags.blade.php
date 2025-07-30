<div class="form-control {{ $required }}">
    <label class="col-form-label" for="{{ $id }}">{{ $label_text }}</label>
    <input name="{{ $element_name }}" id="{{ $id }}" class="{{ $classStrDefault }}" type="text"
        {{ $required }} <p class="form-text">{!! $tooltip !!}</p>
    <p class="form-text">{!! $maps_to_text !!}</p>
    <div class="container">
        @php $count = 0; @endphp
        @foreach ($tags as $tag_name => $sub_tags)
            @php
                natcasesort($sub_tags);
                $group = $marketplace . '_' . preg_replace("/[^A-Za-z0-9 ]/", '', $tag_name);
            @endphp
            @if ($count % 3 == 0)
                <div class="row">
            @endif
            <div class="clonesite_tags col-sm-4" data-group="{{ $group }}">{{ $tag_name }}
                <div class="clonesite_tags_inner" style="display: none;">
                    @foreach ($sub_tags as $sub_tag)
                        <span class="clonesite_tag" data-input-id="{{ $id }}">{{ $sub_tag }}</span>
                    @endforeach
                </div>
            </div>
            @if (($count - 2) % 3 == 0)
                </div>
            @endif
            @php $count++; @endphp
        @endforeach
    </div>
</div>
