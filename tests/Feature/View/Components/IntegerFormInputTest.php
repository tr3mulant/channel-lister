<?php

namespace IGE\ChannelLister\Tests\Feature\View\Components;

use IGE\ChannelLister\Models\ChannelListerField;
use IGE\ChannelLister\Tests\TestCase;

class IntegerFormInputTest extends TestCase
{
    /**
     * Test that component renders basic integer input correctly.
     */
    public function test_renders_basic_integer_input(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'quantity',
            'display_name' => 'Available Quantity',
            'tooltip' => 'Enter the number of items in stock',
            'example' => '10',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::integer-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Basic element structure
        $view->assertSee('form-group', false);
        $view->assertSee('type="number"', false);
        $view->assertSee('step="1"', false);
        $view->assertSee('min="0"', false);

        // Field-specific checks
        $view->assertSee('name="quantity"', false);
        $view->assertSee('id="quantity-id"', false);
        $view->assertSee('Available Quantity');
        $view->assertSee('placeholder="10"', false);

        // Tooltip and mapping info
        $view->assertSee('Enter the number of items in stock');
        $view->assertSee('Maps To: <code>quantity</code>', false);
    }

    /**
     * Test that required field renders "required" attribute and CSS class.
     */
    public function test_renders_required_attribute_and_class(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'stock',
            'display_name' => 'Stock',
            'tooltip' => '',
            'example' => '',
            'required' => true,
        ]);

        $view = $this->blade(
            '<x-channel-lister::integer-form-input :params="$field" class-str-default="input-sm" />',
            ['field' => $field]
        );

        $view->assertSee('required', false); // HTML attribute
        $view->assertSee('form-group required', false); // CSS class
    }

    /**
     * Test that non-required field does not have required attribute or class.
     */
    public function test_non_required_field_has_no_required_attributes(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'optional_field',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::integer-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Should see form-group but NOT form-group required
        $view->assertSee('form-group', false);
        $view->assertDontSee('form-group required', false);
        $view->assertDontSee('required', false); // No required attribute
    }

    /**
     * Test that display name falls back to field name when missing.
     */
    public function test_fallback_to_field_name_for_label(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'item_count',
            'display_name' => '', // empty display name
            'tooltip' => '',
            'example' => '',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::integer-form-input :params="$field" class-str-default="form-input" />',
            ['field' => $field]
        );

        $view->assertSee('item_count');
        $view->assertSee('Maps To: <code>item_count</code>', false);
    }

    /**
     * Test fallback when display_name is null.
     */
    public function test_fallback_to_field_name_when_display_name_is_null(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'test_field',
            'display_name' => null, // null display name
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::integer-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('test_field'); // Should use field_name as label
    }

    /**
     * Test that placeholder is rendered correctly.
     */
    public function test_placeholder_renders_correctly(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'threshold',
            'example' => '100',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::integer-form-input :params="$field" class-str-default="input-md" />',
            ['field' => $field]
        );

        $view->assertSee('placeholder="100"', false);
    }

    /**
     * Test empty placeholder when example is null.
     */
    public function test_empty_placeholder_when_example_is_null(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'no_example',
            'example' => null,
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::integer-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('placeholder=""', false);
    }

    /**
     * Test that component renders correctly with minimal field setup.
     */
    public function test_renders_with_minimal_field_data(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'simple_field',
            'display_name' => null,
            'tooltip' => null,
            'example' => null,
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::integer-form-input :params="$field" class-str-default="input-basic" />',
            ['field' => $field]
        );

        $view->assertSee('name="simple_field"', false);
        $view->assertSee('id="simple_field-id"', false);
        $view->assertSee('Maps To: <code>simple_field</code>', false);
    }

    /**
     * Test that all expected classes are applied to the input element.
     */
    public function test_custom_class_and_col_xs_applied(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'class_test',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::integer-form-input :params="$field" class-str-default="form-large" />',
            ['field' => $field]
        );

        $view->assertSee('class="form-large col-xs-2"', false);
    }

    /**
     * Test that empty custom class still includes col-xs-2.
     */
    public function test_empty_custom_class_still_includes_col_xs_2(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'minimal_class',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::integer-form-input :params="$field" class-str-default="" />',
            ['field' => $field]
        );

        $view->assertSee('class=" col-xs-2"', false);
    }

    /**
     * Test that tooltip handles null values.
     */
    public function test_handles_null_tooltip(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'no_tooltip',
            'tooltip' => null,
        ]);

        $view = $this->blade(
            '<x-channel-lister::integer-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Should render empty tooltip paragraph
        $view->assertSee('<p class="form-text"></p>', false);
    }

    /**
     * Test that tooltip handles empty string.
     */
    public function test_handles_empty_tooltip(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'empty_tooltip',
            'tooltip' => '',
        ]);

        $view = $this->blade(
            '<x-channel-lister::integer-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('<p class="form-text"></p>', false);
    }

    /**
     * Test tooltip with HTML content is rendered unescaped.
     */
    public function test_tooltip_with_html_renders_unescaped(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'html_tooltip',
            'tooltip' => 'This has <strong>bold</strong> text and <em>italic</em> text.',
        ]);

        $view = $this->blade(
            '<x-channel-lister::integer-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('<strong>bold</strong>', false);
        $view->assertSee('<em>italic</em>', false);
    }

    /**
     * Test that ID generation works with complex field names.
     */
    public function test_id_generation_with_complex_field_names(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'complex_field_name_123',
        ]);

        $view = $this->blade(
            '<x-channel-lister::integer-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('id="complex_field_name_123-id"', false);
    }

    /**
     * Test that field names with special characters are handled correctly.
     */
    public function test_field_names_with_underscores_and_numbers(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'test_field_123_abc',
            'display_name' => 'Test Field 123 ABC',
        ]);

        $view = $this->blade(
            '<x-channel-lister::integer-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('name="test_field_123_abc"', false);
        $view->assertSee('id="test_field_123_abc-id"', false);
        $view->assertSee('Test Field 123 ABC');
        $view->assertSee('Maps To: <code>test_field_123_abc</code>', false);
    }

    /**
     * Test all HTML attributes are present and correct.
     */
    public function test_all_html_attributes_present(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'complete_test',
            'display_name' => 'Complete Test Field',
            'tooltip' => 'Complete tooltip text',
            'example' => '42',
            'required' => true,
        ]);

        $view = $this->blade(
            '<x-channel-lister::integer-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // All input attributes
        $view->assertSee('type="number"', false);
        $view->assertSee('name="complete_test"', false);
        $view->assertSee('class="form-control col-xs-2"', false);
        $view->assertSee('id="complete_test-id"', false);
        $view->assertSee('step="1"', false);
        $view->assertSee('min="0"', false);
        $view->assertSee('placeholder="42"', false);
        $view->assertSee('required', false);

        // Label attributes
        $view->assertSee('class="col-form-label"', false);
        $view->assertSee('for="complete_test-id"', false);

        // Form group class
        $view->assertSee('class="form-group required"', false);
    }

    /**
     * Test component renders when all optional fields are empty strings.
     */
    public function test_renders_with_all_empty_optional_fields(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'empty_optionals',
            'display_name' => '',
            'tooltip' => '',
            'example' => '',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::integer-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Should still render basic structure
        $view->assertSee('name="empty_optionals"', false);
        $view->assertSee('id="empty_optionals-id"', false);
        $view->assertSee('empty_optionals'); // fallback label
        $view->assertSee('placeholder=""', false);
        $view->assertSee('<p class="form-text"></p>', false); // empty tooltip
        $view->assertSee('Maps To: <code>empty_optionals</code>', false);
    }

    /**
     * Test the maps to text is always rendered correctly.
     */
    public function test_maps_to_text_always_rendered(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'mapping_test',
        ]);

        $view = $this->blade(
            '<x-channel-lister::integer-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('Maps To: <code>mapping_test</code>', false);
        $view->assertSee('<p class="form-text">Maps To: <code>mapping_test</code></p>', false);
    }
}
