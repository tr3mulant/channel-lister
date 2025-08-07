<?php

namespace IGE\ChannelLister\Tests\Feature\View\Components;

use IGE\ChannelLister\Models\ChannelListerField;
use IGE\ChannelLister\Tests\TestCase;

class CheckboxFormInputTest extends TestCase
{
    /**
     * Test checkbox renders with basic required fields.
     */
    public function test_checkbox_renders_with_basic_fields(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'test_checkbox',
            'display_name' => 'Test Checkbox',
            'required' => false,
            'tooltip' => 'This is a test checkbox',
            'example' => 'example placeholder',
            'input_type_aux' => null,
        ]);

        $view = $this->blade(
            '<x-channel-lister::checkbox-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('Test Checkbox');
        $view->assertSee('This is a test checkbox');
        $view->assertSee('Maps To: <code>test_checkbox</code>', false);
        $view->assertSee('type="checkbox"', false);
        $view->assertSee('name="test_checkbox"', false);
        $view->assertSee('id="test_checkbox-id"', false);
    }

    /**
     * Test checkbox renders as checked when input_type_aux is set.
     */
    public function test_checkbox_renders_checked_when_aux_set(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'checked_checkbox',
            'display_name' => 'Checked Checkbox',
            'input_type_aux' => 'some_value', // Non-empty value should make it checked
        ]);

        $view = $this->blade(
            '<x-channel-lister::checkbox-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('checked', false);
        $view->assertSee('Checked Checkbox');
    }

    /**
     * Test checkbox renders as unchecked when input_type_aux is empty.
     */
    public function test_checkbox_renders_unchecked_when_aux_empty(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'unchecked_checkbox',
            'display_name' => 'Unchecked Checkbox',
            'input_type_aux' => null,
        ]);

        $view = $this->blade(
            '<x-channel-lister::checkbox-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('type="checkbox"', false);
        $view->assertSee('name="unchecked_checkbox"', false);
        $view->assertSee('Unchecked Checkbox');
    }

    /**
     * Test required field adds required class and attribute.
     */
    public function test_required_field_adds_required_class(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'required_checkbox',
            'display_name' => 'Required Checkbox',
            'required' => true,
        ]);

        $view = $this->blade(
            '<x-channel-lister::checkbox-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('form-group required', false);
        $view->assertSee('Required Checkbox');
    }

    /**
     * Test non-required field doesn't have required class.
     */
    public function test_non_required_field_no_required_class(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'optional_checkbox',
            'display_name' => 'Optional Checkbox',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::checkbox-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('form-group', false);
        $view->assertDontSee('form-group required', false);
        $view->assertSee('Optional Checkbox');
    }

    /**
     * Test field uses field_name as label when display_name is empty.
     */
    public function test_uses_field_name_as_label_when_display_name_empty(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'fallback_name',
            'display_name' => null,
        ]);

        $view = $this->blade(
            '<x-channel-lister::checkbox-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('fallback_name');
        $view->assertSee('Maps To: <code>fallback_name</code>', false);
    }

    /**
     * Test tooltip content is rendered when provided.
     */
    public function test_tooltip_content_rendered(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'tooltip_checkbox',
            'display_name' => 'Checkbox with Tooltip',
            'tooltip' => 'This is helpful tooltip text with <strong>HTML</strong>',
        ]);

        $view = $this->blade(
            '<x-channel-lister::checkbox-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('This is helpful tooltip text with <strong>HTML</strong>', false);
        $view->assertSee('Checkbox with Tooltip');
    }

    /**
     * Test maps to text is always rendered with field name.
     */
    public function test_maps_to_text_always_rendered(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'mapping_test',
            'display_name' => 'Mapping Test Field',
        ]);

        $view = $this->blade(
            '<x-channel-lister::checkbox-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('Maps To: <code>mapping_test</code>', false);
        $view->assertSee('Mapping Test Field');
    }

    /**
     * Test component handles empty tooltip gracefully.
     */
    public function test_handles_empty_tooltip(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'no_tooltip',
            'display_name' => 'No Tooltip Field',
            'tooltip' => null,
        ]);

        $view = $this->blade(
            '<x-channel-lister::checkbox-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('No Tooltip Field');
        $view->assertSee('Maps To: <code>no_tooltip</code>', false);
        // Should still render the tooltip paragraph, just empty
        $view->assertSee('<p class="form-text"></p>', false);
    }

    /**
     * Test component renders correctly with all fields populated.
     */
    public function test_component_with_all_fields_populated(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'complete_test',
            'display_name' => 'Complete Test Field',
            'required' => true,
            'tooltip' => 'Complete tooltip with <em>formatting</em>',
            'example' => 'example value',
            'input_type_aux' => 'checked_value',
        ]);

        $view = $this->blade(
            '<x-channel-lister::checkbox-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Test all visible content is rendered
        $view->assertSee('Complete Test Field');
        $view->assertSee('Complete tooltip with <em>formatting</em>', false);
        $view->assertSee('Maps To: <code>complete_test</code>', false);
        $view->assertSee('checked', false);
        $view->assertSee('form-group required', false);
    }
}
