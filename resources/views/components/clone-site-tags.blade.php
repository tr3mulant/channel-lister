<div class="form-group {{ $required }}">
    <label class="control-label" for="{{ $id }}">{{ $label_text }}</label>
    <input name="{{ $element_name }}" id="{{ $id }}" class="{{ $this->class_str_default }}" type="text"
        {{ $required }} <p class="help-block">{{ $tooltip }}</p>
    <p class="help-block">{{ $maps_to_text }}</p>
    <div class="container">
        <?php
        $count = 0;
        foreach ($tags as $tag_name => $sub_tags) {
            natcasesort($sub_tags);
            if ($count % 3 == 0) {
                ?>
                <div class="row"><?php
            }
            $group = $marketplace . '_' . preg_replace("/[^A-Za-z0-9 ]/", '', $tag_name);
            ?>
                <div class="clonesite_tags col-sm-4" data-group="{{ $group }}">{{ $tag_name }}
                    <div class="clonesite_tags_inner" style="display: none;">
                        <?php
                        foreach ($sub_tags as $sub_tag) {
                            ?>
                            <span class="clonesite_tag" data-input-id="{{ $id }}">{{ $sub_tag }}</span>
                            <?php
                        }
                        ?>
                    </div>
                </div>
                <?php
                if (($count - 2) % 3 == 0) {
                    ?>
                </div><?php
                }
                $count++;
        }
        ?>
    </div>
</div>