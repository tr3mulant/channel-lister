<?php

namespace IGE\ChannelLister\Tests\Feature\View\Components\Custom;

use IGE\ChannelLister\Models\ChannelListerField;
use IGE\ChannelLister\Tests\TestCase;
use IGE\ChannelLister\View\Components\Custom\CostShipping;

class CostShippingTest extends TestCase
{
    /**
     * Test that component renders basic cost shipping input correctly.
     */
    public function test_renders_basic_cost_shipping(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'shipping_cost',
            'display_name' => 'Shipping Cost',
            'tooltip' => 'Calculated shipping cost',
            'example' => '12.50',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.cost-shipping :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Check basic structure
        $view->assertSee('form-group', false);
        $view->assertSee('type="number"', false);
        $view->assertSee('step="0.01"', false);
        $view->assertSee('min="0"', false);
        $view->assertSee('readonly', false);

        // Check field-specific attributes
        $view->assertSee('name="shipping_cost"', false);
        $view->assertSee('id="shipping_cost-id"', false);
        $view->assertSee('Shipping Cost');
        $view->assertSee('placeholder="12.50"', false);

        // Check tooltip and maps to text
        $view->assertSee('Calculated shipping cost');
        $view->assertSee('Maps To: <code>shipping_cost</code>', false);
    }

    /**
     * Test that required fields are marked as required.
     */
    public function test_required_field_has_required_attribute(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'required_cost',
            'display_name' => 'Required Cost',
            'required' => true,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.cost-shipping :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('required', false);
        $view->assertSee('form-group required', false);
    }

    /**
     * Test that field is always readonly.
     */
    public function test_field_is_always_readonly(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'cost',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.cost-shipping :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('readonly', false);
    }

    /**
     * Test the component class directly.
     */
    public function test_component_class_data_preparation(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'test_cost',
            'display_name' => 'Test Cost',
            'tooltip' => 'Test tooltip',
            'example' => '25.99',
            'required' => true,
        ]);

        $component = new CostShipping($field, 'form-control');
        $view = $component->render();
        $data = $view->getData();

        $this->assertEquals('test_cost', $data['element_name']);
        $this->assertEquals('required', $data['required']);
        $this->assertEquals('Test Cost', $data['label_text']);
        $this->assertEquals('test_cost-id', $data['id']);
        $this->assertEquals('Test tooltip', $data['tooltip']);
        $this->assertEquals('25.99', $data['placeholder']);
        $this->assertEquals('Maps To: <code>test_cost</code>', $data['maps_to_text']);
    }

    /**
     * Test optional field behavior.
     */
    public function test_optional_field_behavior(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'optional_cost',
            'display_name' => 'Optional Cost',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.cost-shipping :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertDontSee('required', false);
        $view->assertSee('form-group ', false);
        $view->assertDontSee('form-group required', false);
        $view->assertSee('readonly', false); // Should still be readonly
    }

    /**
     * Test custom class string is applied.
     */
    public function test_custom_class_string_applied(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'custom_class_cost',
            'display_name' => 'Custom Class Cost',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.cost-shipping :params="$field" class-str-default="custom-cost-class" />',
            ['field' => $field]
        );

        $view->assertSee('class="custom-cost-class"', false);
    }

    /**
     * Test tooltip rendering with HTML.
     */
    public function test_tooltip_rendering(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'tooltip_cost',
            'display_name' => 'Tooltip Cost',
            'tooltip' => 'This cost is <strong>automatically calculated</strong>',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.cost-shipping :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('This cost is <strong>automatically calculated</strong>', false);
    }

    /**
     * Test number input attributes.
     */
    public function test_number_input_attributes(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'numeric_cost',
            'display_name' => 'Numeric Cost',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.cost-shipping :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('type="number"', false);
        $view->assertSee('step="0.01"', false);
        $view->assertSee('min="0"', false);
    }

    /**
     * Test field name fallback when display name is empty.
     */
    public function test_field_name_fallback(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'fallback_cost_field',
            'display_name' => '',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.cost-shipping :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Should use formatted field name as display name
        $view->assertSee('Fallback Cost Field');
    }

    /**
     * Test maps to text generation.
     */
    public function test_maps_to_text_generation(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'mapping_cost_test',
            'display_name' => 'Mapping Cost Test',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.cost-shipping :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('Maps To: <code>mapping_cost_test</code>', false);
    }

    /**
     * Test XSS prevention in display name.
     */
    public function test_xss_prevention_in_display_name(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'xss_cost_test',
            'display_name' => '<script>alert("xss")</script>Malicious Cost',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.cost-shipping :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Display name should be escaped
        $view->assertSee('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;Malicious Cost', false);
        $view->assertDontSee('<script>alert("xss")</script>', false);
    }

    /**
     * Test ID generation.
     */
    public function test_id_generation(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'id_cost_test',
            'display_name' => 'ID Cost Test',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.cost-shipping :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('id="id_cost_test-id"', false);
        $view->assertSee('for="id_cost_test-id"', false);
    }

    /**
     * Test Bootstrap classes are present.
     */
    public function test_bootstrap_classes_present(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'bootstrap_cost_test',
            'display_name' => 'Bootstrap Cost Test',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.cost-shipping :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('form-group', false);
        $view->assertSee('col-form-label', false);
        $view->assertSee('form-control', false);
    }

    /**
     * Test decimal placeholder handling.
     */
    public function test_decimal_placeholder_handling(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'decimal_cost',
            'display_name' => 'Decimal Cost',
            'example' => '199.99',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.cost-shipping :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('placeholder="199.99"', false);
    }

    /**
     * Test empty example handling.
     */
    public function test_empty_example_handling(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'no_example_cost',
            'display_name' => 'No Example Cost',
            'example' => null,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.cost-shipping :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Component still renders placeholder attribute, but should be empty or with default value
        $view->assertSee('No Example Cost');
        $view->assertSee('form-group', false);
    }

    /**
     * Test currency formatting context.
     */
    public function test_currency_formatting_context(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'currency_cost',
            'display_name' => 'Currency Cost',
            'example' => '15.50',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.cost-shipping :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Should be a number input suitable for currency
        $view->assertSee('type="number"', false);
        $view->assertSee('step="0.01"', false);
        $view->assertSee('readonly', false);
        $view->assertSee('placeholder="15.50"', false);
    }
}
