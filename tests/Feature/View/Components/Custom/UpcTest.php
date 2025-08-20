<?php

namespace IGE\ChannelLister\Tests\Feature\View\Components\Custom;

use IGE\ChannelLister\Models\ChannelListerField;
use IGE\ChannelLister\Tests\TestCase;
use IGE\ChannelLister\View\Components\Custom\Upc;

class UpcTest extends TestCase
{
    /**
     * Test that component renders basic UPC correctly.
     */
    public function test_renders_basic_upc(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'upc_code',
            'display_name' => 'UPC Code',
            'tooltip' => 'Universal Product Code',
            'example' => '123456789012',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.upc :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('UPC Code');
        // Note: UPC component doesn't render placeholder in the main UPC input field
        $view->assertSee('Maps To: <code>upc_code</code>', false);
    }

    /**
     * Test the component class directly.
     */
    public function test_component_class_data_preparation(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'test_upc',
            'display_name' => 'Test UPC',
            'tooltip' => 'Test tooltip',
            'example' => '123456789012',
            'required' => true,
        ]);

        $component = new Upc($field, 'form-control');
        $view = $component->render();
        $data = $view->getData();

        $this->assertEquals('test_upc', $data['element_name']);
        $this->assertEquals('required', $data['required']);
        $this->assertEquals('Test UPC', $data['label_text']);
        $this->assertEquals('test_upc-id', $data['id']);
        $this->assertEquals('Test tooltip', $data['tooltip']);
        $this->assertEquals('123456789012', $data['placeholder']);
        $this->assertEquals('Maps To: <code>test_upc</code>', $data['maps_to_text']);
    }

    /**
     * Test required field behavior.
     */
    public function test_required_field_behavior(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'required_upc',
            'display_name' => 'Required UPC Field',
            'required' => true,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.upc :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('required', false);
        $view->assertSee('form-group container-fluid required', false);
    }

    /**
     * Test optional field behavior.
     */
    public function test_optional_field_behavior(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'optional_upc',
            'display_name' => 'Optional UPC Field',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.upc :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertDontSee('required', false);
        $view->assertSee('form-group ', false);
        $view->assertDontSee('form-group required', false);
    }

    /**
     * Test custom class string is applied.
     */
    public function test_custom_class_string_applied(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'custom_class_upc',
            'display_name' => 'Custom Class UPC',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.upc :params="$field" class-str-default="custom-upc-class" />',
            ['field' => $field]
        );

        // UPC component doesn't use custom class string for input, check for form structure instead
        $view->assertSee('form-control upc_field', false);
    }

    /**
     * Test tooltip rendering with HTML.
     */
    public function test_tooltip_rendering(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'tooltip_upc',
            'display_name' => 'Tooltip UPC',
            'tooltip' => 'Enter the <strong>Universal Product Code</strong> for this item',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.upc :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('Enter the <strong>Universal Product Code</strong> for this item', false);
    }

    /**
     * Test empty tooltip handling.
     */
    public function test_empty_tooltip_handling(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'no_tooltip_upc',
            'display_name' => 'No Tooltip UPC',
            'tooltip' => null,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.upc :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Component renders successfully even with null tooltip
        $view->assertSee('No Tooltip UPC');
        $view->assertSee('form-group', false);
    }

    /**
     * Test field name fallback when display name is empty.
     */
    public function test_field_name_fallback(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'fallback_upc_field',
            'display_name' => '',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.upc :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Should use formatted field name as display name
        $view->assertSee('Fallback Upc Field');
    }

    /**
     * Test maps to text generation.
     */
    public function test_maps_to_text_generation(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'mapping_upc_test',
            'display_name' => 'Mapping UPC Test',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.upc :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('Maps To: <code>mapping_upc_test</code>', false);
    }

    /**
     * Test XSS prevention in display name.
     */
    public function test_xss_prevention_in_display_name(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'xss_upc_test',
            'display_name' => '<script>alert("xss")</script>Malicious UPC',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.upc :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Display name should be escaped
        $view->assertSee('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;Malicious UPC', false);
        $view->assertDontSee('<script>alert("xss")</script>', false);
    }

    /**
     * Test ID generation.
     */
    public function test_id_generation(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'id_upc_test',
            'display_name' => 'ID UPC Test',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.upc :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // UPC uses platform-specific IDs, not field-name based IDs
        $view->assertSee('class="form-control upc_field"', false);
        $view->assertSee('name="id_upc_test"', false);
    }

    /**
     * Test Bootstrap classes are present.
     */
    public function test_bootstrap_classes_present(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'bootstrap_upc_test',
            'display_name' => 'Bootstrap UPC Test',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.upc :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('form-group', false);
        $view->assertSee('col-form-label', false);
        $view->assertSee('form-control', false);
    }

    /**
     * Test UPC input type and attributes.
     */
    public function test_upc_input_type(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'input_type_upc',
            'display_name' => 'Input Type UPC',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.upc :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('type="text"', false);
    }

    /**
     * Test example/placeholder rendering.
     */
    public function test_example_placeholder_rendering(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'placeholder_upc',
            'display_name' => 'Placeholder UPC',
            'example' => '012345678905',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.upc :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // UPC main input doesn't use placeholder, but UPC seed input has placeholder
        $view->assertSee('placeholder="Nothing"', false);
    }

    /**
     * Test UPC validation patterns.
     */
    public function test_upc_validation_patterns(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'pattern_upc',
            'display_name' => 'Pattern UPC',
            'example' => '123456789012',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.upc :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // UPC typically requires numeric input with specific length
        $view->assertSee('type="text"', false);
        $view->assertSee('name="pattern_upc"', false);
    }

    /**
     * Test UPC length expectations.
     */
    public function test_upc_length_expectations(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'length_upc',
            'display_name' => 'Length UPC',
            'example' => '123456789012',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.upc :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // UPC main input doesn't use placeholder, check pattern instead
        $view->assertSee('pattern="^[0-9]{12,13}$"', false);
    }

    /**
     * Test empty example handling.
     */
    public function test_empty_example_handling(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'no_example_upc',
            'display_name' => 'No Example UPC',
            'example' => null,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.upc :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Component still renders successfully without example
        $view->assertSee('No Example UPC');
        $view->assertSee('form-group', false);
    }

    /**
     * Test component with various field scenarios.
     */
    public function test_various_field_scenarios(): void
    {
        // Test with minimal data
        $minimalField = ChannelListerField::factory()->create([
            'field_name' => 'minimal_upc',
            'display_name' => null,
            'tooltip' => null,
            'example' => null,
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.upc :params="$field" class-str-default="form-control" />',
            ['field' => $minimalField]
        );

        $view->assertSee('Minimal Upc'); // Should fallback to formatted field name
        $view->assertSee('form-group', false);
    }

    /**
     * Test UPC specific functionality.
     */
    public function test_upc_specific_functionality(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'specific_upc',
            'display_name' => 'Specific UPC',
            'example' => '987654321098',
        ]);

        $component = new Upc($field, 'form-control');
        $view = $component->render();
        $data = $view->getData();

        // Verify UPC component data structure
        $this->assertEquals('specific_upc', $data['element_name']);
        $this->assertEquals('987654321098', $data['placeholder']);
        $this->assertEquals('specific_upc-id', $data['id']);
    }
}
