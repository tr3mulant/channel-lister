<div class="form-group <{{ $required }}">
    <label class="control-label" for="{{ $id }}">{{ $label_text }}</label>
    <input type="text" name="{{ $element_name }}" class="{{ $this->class_str_default }}" id="{{ $id }}"
        placeholder="{{ $placeholder }}" {{ $required }}>
    <div class="comma-sep-options">
        <?php
        foreach ($display_names as $option_val => $option_name) {
            $checkbox_id = $element_name . $label_text . "-checkbox$checkbox_count";
            $checkbox_count++;
            ?>
            <div class="checkbox-inline">
                <label for="{{ $checkbox_id }}" class="checkbox-inline">
                    <input id="{{ $checkbox_id }}" value="{{ $option_val }}" type="checkbox">{{ $option_name }}
                </label>
            </div>
            <?php
        }
        ?>
    </div>
    <p class="help-block"><?= $tooltip ?></p>
    <p class="help-block"><?= $maps_to_text ?></p>
</div>