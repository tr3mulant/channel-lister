<div class="form-group <{{ $required }}">
    <label class="control-label" for="{{ $id }}">{{ $label_text }}</label>
    <select name="{{ $element_name }}" class="{{ $classStrDefault }} {{ $select_type }}" data-size="10"
        data-live-search="{{ $select_search }}" id="{{ $id }}" placeholder="{{ $placeholder }}"
        {{ $required }} title="Select...">
        <?php
        foreach ($display_names as $option => $display_name) {
            $style = '';
            if (strlen($display_name) > 256) {
                $style = 'white-space:normal;';
            }
            echo '<option style="' . $style . '" value="' . $option . '">' . $display_name . '</option>';
        }
        ?>
    </select>
    <p class="help-block">{!! $tooltip !!}</p>
    <p class="help-block">{!! $maps_to_text !!}</p>
</div>
