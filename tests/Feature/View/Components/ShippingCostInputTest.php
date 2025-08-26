<?php

namespace IGE\ChannelLister\Tests\Feature\View\Components;

use IGE\ChannelLister\Models\ChannelListerField;
use IGE\ChannelLister\Tests\TestCase;
use IGE\ChannelLister\View\Components\ShippingCostInput;

class ShippingCostInputTest extends TestCase
{
    /**
     * Test component instantiation with required parameters.
     */
    public function test_component_instantiation(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'shipping_cost',
            'display_name' => 'Shipping Cost',
            'required' => true,
        ]);

        $component = new ShippingCostInput($field, 'form-control');

        $this->assertEquals($field, $component->params);
        $this->assertEquals('form-control', $component->classStrDefault);
    }

    /**
     * Test component render method returns correct view and data.
     */
    public function test_component_render_method(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'shipping_cost',
            'display_name' => 'Shipping Cost',
            'tooltip' => 'Enter the shipping cost',
            'example' => '15.99',
            'required' => true,
        ]);

        $component = new ShippingCostInput($field, 'test-class');
        $view = $component->render();

        $this->assertEquals('channel-lister::components.shipping-cost-input', $view->name());

        $data = $view->getData();

        // Test basic field mapping
        $this->assertEquals($field, $data['params']);
        $this->assertEquals('shipping_cost', $data['element_name']);
        $this->assertEquals('required', $data['required']);
        $this->assertEquals('Shipping Cost', $data['label_text']);
        $this->assertEquals('shipping_cost-id', $data['id']);
        $this->assertEquals('Enter the shipping cost', $data['tooltip']);
        $this->assertEquals('15.99', $data['placeholder']);
        $this->assertEquals('Maps To: <code>shipping_cost</code>', $data['maps_to_text']);
    }

    /**
     * Test component with field that has empty required (false).
     */
    public function test_component_with_non_required_field(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'optional_shipping',
            'required' => false,
        ]);

        $component = new ShippingCostInput($field, 'form-control');
        $view = $component->render();
        $data = $view->getData();

        $this->assertEquals('', $data['required']);
    }

    /**
     * Test component with field that has true required.
     */
    public function test_component_with_required_field(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'required_shipping',
            'required' => true,
        ]);

        $component = new ShippingCostInput($field, 'form-control');
        $view = $component->render();
        $data = $view->getData();

        $this->assertEquals('required', $data['required']);
    }

    /**
     * Test component uses field_name as label when display_name is empty.
     */
    public function test_component_fallback_label_text(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'cost_field',
            'display_name' => null,
        ]);

        $component = new ShippingCostInput($field, 'form-control');
        $view = $component->render();
        $data = $view->getData();

        $this->assertEquals('Cost Field', $data['label_text']);
    }

    /**
     * Test component with empty display_name string.
     */
    public function test_component_with_empty_display_name(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'test_field',
            'display_name' => '',
        ]);

        $component = new ShippingCostInput($field, 'form-control');
        $view = $component->render();
        $data = $view->getData();

        $this->assertEquals('Test Field', $data['label_text']);
    }

    /**
     * Test component ID generation.
     */
    public function test_component_id_generation(): void
    {
        $testCases = [
            'shipping_cost' => 'shipping_cost-id',
            'cost' => 'cost-id',
            'my_shipping_field' => 'my_shipping_field-id',
            'special-field-name' => 'special-field-name-id',
        ];

        foreach ($testCases as $fieldName => $expectedId) {
            $field = ChannelListerField::factory()->create([
                'field_name' => $fieldName,
            ]);

            $component = new ShippingCostInput($field, 'form-control');
            $view = $component->render();
            $data = $view->getData();

            $this->assertEquals($expectedId, $data['id']);
        }
    }

    /**
     * Test component with null tooltip.
     */
    public function test_component_with_null_tooltip(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'shipping_cost',
            'tooltip' => null,
        ]);

        $component = new ShippingCostInput($field, 'form-control');
        $view = $component->render();
        $data = $view->getData();

        $this->assertNull($data['tooltip']);
    }

    /**
     * Test component with null example.
     */
    public function test_component_with_null_example(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'shipping_cost',
            'example' => null,
        ]);

        $component = new ShippingCostInput($field, 'form-control');
        $view = $component->render();
        $data = $view->getData();

        $this->assertNull($data['placeholder']);
    }

    /**
     * Test component maps_to_text generation.
     */
    public function test_component_maps_to_text(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'custom_shipping_field',
        ]);

        $component = new ShippingCostInput($field, 'form-control');
        $view = $component->render();
        $data = $view->getData();

        $this->assertEquals('Maps To: <code>custom_shipping_field</code>', $data['maps_to_text']);
    }

    /**
     * Test component with special characters in field name.
     */
    public function test_component_with_special_characters(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'shipping-cost_v2',
            'display_name' => 'Shipping Cost V2',
        ]);

        $component = new ShippingCostInput($field, 'form-control');
        $view = $component->render();
        $data = $view->getData();

        $this->assertEquals('shipping-cost_v2', $data['element_name']);
        $this->assertEquals('shipping-cost_v2-id', $data['id']);
        $this->assertEquals('Maps To: <code>shipping-cost_v2</code>', $data['maps_to_text']);
    }

    /**
     * Test component view rendering with Blade.
     */
    public function test_component_blade_rendering(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'shipping_cost',
            'display_name' => 'Shipping Cost',
            'tooltip' => 'Enter shipping cost',
            'example' => '25.99',
            'required' => true,
        ]);

        $view = $this->blade(
            '<x-channel-lister::shipping-cost-input :params="$field" class-str-default="custom-class" />',
            ['field' => $field]
        );

        // Test basic structure
        $view->assertSee('shipping_cost', false);
        $view->assertSee('Shipping Cost', false);
        $view->assertSee('Enter shipping cost', false);
        $view->assertSee('25.99', false);
        $view->assertSee('shipping_cost-id', false);
        $view->assertSee('Maps To: <code>shipping_cost</code>', false);
    }

    /**
     * Test component handles XSS prevention in output.
     */
    public function test_component_xss_prevention(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'shipping_cost',
            'display_name' => '<script>alert("xss")</script>Shipping Cost',
            'tooltip' => '<img src="x" onerror="alert(\'xss\')">Tooltip',
            'example' => '<script>console.log("xss")</script>15.99',
        ]);

        $component = new ShippingCostInput($field, 'form-control');
        $view = $component->render();
        $data = $view->getData();

        // Data should contain the raw values (not escaped at component level)
        $this->assertStringContainsString('<script>', $data['label_text']);
        $this->assertStringContainsString('<img', $data['tooltip']);
        $this->assertStringContainsString('<script>', $data['placeholder']);
    }

    /**
     * Test component data structure consistency.
     */
    public function test_component_data_structure(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'test_shipping',
            'display_name' => 'Test Shipping',
            'required' => false,
        ]);

        $component = new ShippingCostInput($field, 'form-control');
        $view = $component->render();
        $data = $view->getData();

        // Check all expected keys are present
        $expectedKeys = [
            'params', 'element_name', 'required', 'label_text',
            'id', 'tooltip', 'placeholder', 'maps_to_text',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $data);
        }

        // Check data types
        $this->assertInstanceOf(ChannelListerField::class, $data['params']);
        $this->assertIsString($data['element_name']);
        $this->assertIsString($data['required']);
        $this->assertIsString($data['label_text']);
        $this->assertIsString($data['id']);
        $this->assertIsString($data['maps_to_text']);
    }

    /**
     * Test component with different class string defaults.
     */
    public function test_component_with_different_class_strings(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'shipping_cost',
        ]);

        $classStrings = [
            'form-control',
            'form-control form-control-lg',
            'custom-input-class',
            'form-control shipping-input required-field',
            '',
        ];

        foreach ($classStrings as $classStr) {
            $component = new ShippingCostInput($field, $classStr);
            $this->assertEquals($classStr, $component->classStrDefault);
        }
    }

    /**
     * Test component with long field names.
     */
    public function test_component_with_long_field_names(): void
    {
        $longFieldName = 'very_long_field_name_for_shipping_cost_calculation_with_many_underscores_and_details';

        $field = ChannelListerField::factory()->create([
            'field_name' => $longFieldName,
            'display_name' => 'Very Long Display Name for Shipping Cost Field',
        ]);

        $component = new ShippingCostInput($field, 'form-control');
        $view = $component->render();
        $data = $view->getData();

        $this->assertEquals($longFieldName, $data['element_name']);
        $this->assertEquals($longFieldName.'-id', $data['id']);
        $this->assertStringContainsString($longFieldName, $data['maps_to_text']);
    }

    /**
     * Test component with empty field name (edge case).
     */
    public function test_component_with_empty_field_name(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => '',
            'display_name' => 'Empty Field Name',
        ]);

        $component = new ShippingCostInput($field, 'form-control');
        $view = $component->render();
        $data = $view->getData();

        $this->assertEquals('', $data['element_name']);
        $this->assertEquals('-id', $data['id']);
        $this->assertEquals('Maps To: <code></code>', $data['maps_to_text']);
    }

    /**
     * Test component consistency across multiple renders.
     */
    public function test_component_render_consistency(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'consistent_shipping',
            'display_name' => 'Consistent Shipping',
            'required' => true,
        ]);

        $component = new ShippingCostInput($field, 'form-control');

        $view1 = $component->render();
        $view2 = $component->render();

        $this->assertEquals($view1->name(), $view2->name());
        $this->assertEquals($view1->getData(), $view2->getData());
    }

    /**
     * Test component with numeric field names.
     */
    public function test_component_with_numeric_field_names(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => '123_shipping_cost',
            'display_name' => 'Numeric Shipping Cost',
        ]);

        $component = new ShippingCostInput($field, 'form-control');
        $view = $component->render();
        $data = $view->getData();

        $this->assertEquals('123_shipping_cost', $data['element_name']);
        $this->assertEquals('123_shipping_cost-id', $data['id']);
    }

    /**
     * Test component properties are accessible.
     */
    public function test_component_properties_accessibility(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'test_access',
        ]);

        $component = new ShippingCostInput($field, 'access-test-class');

        $this->assertEquals($field, $component->params);
        $this->assertEquals('access-test-class', $component->classStrDefault);
    }
}
