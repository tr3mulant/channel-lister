<?php

namespace IGE\ChannelLister\Tests\Feature\View\Components;

use IGE\ChannelLister\Models\ChannelListerField;
use IGE\ChannelLister\Tests\TestCase;

class CommaSeparatedFormTest extends TestCase
{
    /**
     * Test comma separated form renders with basic field properties
     */
    public function test_comma_separated_form_renders_with_basic_properties(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'test_field',
            'display_name' => 'Test Field',
            'tooltip' => 'This is a test tooltip',
            'example' => 'Enter comma separated values',
            'required' => true,
            'input_type_aux' => null,
        ]);

        $view = $this->blade(
            '<x-channel-lister::comma-separated-form :params="$field" classStrDefault="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('form-group required', false);
        $view->assertSee('Test Field');
        $view->assertSee('test_field-id', false);
        $view->assertSee('name="test_field"', false);
        $view->assertSee('form-control', false);
        $view->assertSee('Enter comma separated values');
        $view->assertSee('required', false);
        $view->assertSee('This is a test tooltip', false);
        $view->assertSee('Maps To: <code>test_field</code>', false);
    }

    /**
     * Test comma separated form renders without required attribute when not required
     */
    public function test_comma_separated_form_renders_without_required_when_not_required(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'optional_field',
            'display_name' => 'Optional Field',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::comma-separated-form :params="$field" classStrDefault="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('form-group', false);
        $view->assertDontSee('form-group required', false);
        $view->assertDontSee('required', false);
    }

    /**
     * Test comma separated form uses field_name as label when display_name is empty
     */
    public function test_comma_separated_form_uses_field_name_as_label_when_display_name_empty(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'test_field_name',
            'display_name' => '',
        ]);

        $view = $this->blade(
            '<x-channel-lister::comma-separated-form :params="$field" classStrDefault="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('test_field_name');
        $view->assertSee('for="test_field_name-id"', false);
    }

    /**
     * Test comma separated form renders with options from input_type_aux
     */
    public function test_comma_separated_form_renders_with_options(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'category_field',
            'display_name' => 'Categories',
            'input_type_aux' => 'option1==Option One||option2==Option Two||option3',
        ]);

        $view = $this->blade(
            '<x-channel-lister::comma-separated-form :params="$field" classStrDefault="form-control" />',
            ['field' => $field]
        );

        // Check that checkboxes are rendered
        $view->assertSee('comma-sep-options', false);
        $view->assertSee('checkbox-inline', false);

        // Check option values and display names
        $view->assertSee('value="option1"', false);
        $view->assertSee('Option One');
        $view->assertSee('value="option2"', false);
        $view->assertSee('Option Two');
        $view->assertSee('value="option3"', false);
        $view->assertSee('Option3'); // ucwords applied when no display name provided

        // Check checkbox IDs are generated correctly
        $view->assertSee('id="category_fieldCategories-checkbox0"', false);
        $view->assertSee('id="category_fieldCategories-checkbox1"', false);
        $view->assertSee('id="category_fieldCategories-checkbox2"', false);
    }

    /**
     * Test comma separated form with a known working options format
     */
    public function test_comma_separated_form_with_working_options_format(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'test_field',
            'display_name' => 'Test Field',
            'input_type_aux' => 'option1==Option One||option2==Option Two||option3',
        ]);

        $view = $this->blade(
            '<x-channel-lister::comma-separated-form :params="$field" classStrDefault="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('comma-sep-options', false);
        $view->assertSee('checkbox-inline', false);
        $view->assertSee('type="checkbox"', false);
        $view->assertSee('value="option1"', false);
        $view->assertSee('Option One');
        $view->assertSee('value="option2"', false);
        $view->assertSee('Option Two');
        $view->assertSee('value="option3"', false);
        $view->assertSee('Option3'); // ucwords applied when no == separator
    }

    /**
     * Test comma separated form handles empty input_type_aux options
     */
    public function test_comma_separated_form_handles_empty_options(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'empty_options_field',
            'display_name' => 'Empty Options',
            'input_type_aux' => '',
        ]);

        $view = $this->blade(
            '<x-channel-lister::comma-separated-form :params="$field" classStrDefault="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('comma-sep-options', false);
        $view->assertDontSee('checkbox-inline', false);
        $view->assertDontSee('type="checkbox"', false);
    }

    /**
     * Test comma separated form handles null input_type_aux options
     */
    public function test_comma_separated_form_handles_null_options(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'null_options_field',
            'display_name' => 'Null Options',
            'input_type_aux' => null,
        ]);

        $view = $this->blade(
            '<x-channel-lister::comma-separated-form :params="$field" classStrDefault="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('comma-sep-options', false);
        $view->assertDontSee('checkbox-inline', false);
        $view->assertDontSee('type="checkbox"', false);
    }

    /**
     * Test comma separated form renders with complex options including special characters
     */
    public function test_comma_separated_form_renders_with_complex_options(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'complex_field',
            'display_name' => 'Complex Field',
            'input_type_aux' => 'key-with-dash==Display With Spaces||another_key==Another Display||simple_key',
        ]);

        $view = $this->blade(
            '<x-channel-lister::comma-separated-form :params="$field" classStrDefault="custom-class" />',
            ['field' => $field]
        );

        $view->assertSee('value="key-with-dash"', false);
        $view->assertSee('Display With Spaces');
        $view->assertSee('value="another_key"', false);
        $view->assertSee('Another Display');
        $view->assertSee('value="simple_key"', false);
        $view->assertSee('Simple_key'); // ucwords applied to the key itself
        $view->assertSee('custom-class', false);
        $view->assertSee('comma-sep-options', false);
        $view->assertSee('checkbox-inline', false);
    }

    /**
     * Test comma separated form renders all form elements correctly
     */
    public function test_comma_separated_form_renders_all_form_elements(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'complete_field',
            'display_name' => 'Complete Test Field',
            'tooltip' => '<strong>Bold tooltip</strong> with HTML',
            'example' => 'value1, value2, value3',
            'required' => true,
            'input_type_aux' => 'opt1==Option 1||opt2==Option 2',
        ]);

        $view = $this->blade(
            '<x-channel-lister::comma-separated-form :params="$field" classStrDefault="form-control test-class" />',
            ['field' => $field]
        );

        // Form group with required class
        $view->assertSee('form-group required', false);

        // Label
        $view->assertSee('<label class="col-form-label font-weight-bold" for="complete_field-id">Complete Test Field</label>', false);

        // Input field
        $view->assertSee('<input type="text" name="complete_field" class="form-control test-class" id="complete_field-id"', false);
        $view->assertSee('placeholder="value1, value2, value3"', false);
        $view->assertSee('required>', false);

        // Checkboxes
        $view->assertSee('comma-sep-options', false);
        $view->assertSee('checkbox-inline', false);
        $view->assertSee('Option 1');
        $view->assertSee('Option 2');

        // Tooltip with HTML
        $view->assertSee('<strong>Bold tooltip</strong> with HTML', false);

        // Maps to text
        $view->assertSee('Maps To: <code>complete_field</code>', false);
    }

    /**
     * Test comma separated form checkbox ID generation with special characters
     */
    public function test_comma_separated_form_checkbox_id_generation(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'special_field',
            'display_name' => 'Special & Field',
            'input_type_aux' => 'test1||test2||test3',
        ]);

        $view = $this->blade(
            '<x-channel-lister::comma-separated-form :params="$field" classStrDefault="form-control" />',
            ['field' => $field]
        );

        // Check that checkbox IDs increment correctly
        // Note: The & gets HTML encoded to &amp; in the rendered output
        $view->assertSee('id="special_fieldSpecial &amp; Field-checkbox0"', false);
        $view->assertSee('id="special_fieldSpecial &amp; Field-checkbox1"', false);
        $view->assertSee('id="special_fieldSpecial &amp; Field-checkbox2"', false);

        // Check corresponding labels
        $view->assertSee('for="special_fieldSpecial &amp; Field-checkbox0"', false);
        $view->assertSee('for="special_fieldSpecial &amp; Field-checkbox1"', false);
        $view->assertSee('for="special_fieldSpecial &amp; Field-checkbox2"', false);

        // Check checkbox values
        $view->assertSee('value="test1"', false);
        $view->assertSee('value="test2"', false);
        $view->assertSee('value="test3"', false);

        // Check display names (ucwords applied since no == separator)
        $view->assertSee('Test1');
        $view->assertSee('Test2');
        $view->assertSee('Test3');
    }

    /**
     * Test comma separated form with minimal field data
     */
    public function test_comma_separated_form_with_minimal_data(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'minimal',
            'display_name' => null,
            'tooltip' => null,
            'example' => null,
            'required' => false, // Set to false instead of null to avoid DB constraint violation
            'input_type_aux' => null,
        ]);

        $view = $this->blade(
            '<x-channel-lister::comma-separated-form :params="$field" classStrDefault="basic-class" />',
            ['field' => $field]
        );

        $view->assertSee('form-group', false);
        $view->assertDontSee('required', false);
        $view->assertSee('minimal'); // field_name used as label
        $view->assertSee('basic-class', false);
        $view->assertSee('Maps To: <code>minimal</code>', false);
    }
}
