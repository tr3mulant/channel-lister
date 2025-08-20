<?php

namespace IGE\ChannelLister\Tests\Feature\View\Components\Custom;

use IGE\ChannelLister\Models\ChannelListerField;
use IGE\ChannelLister\Tests\TestCase;
use IGE\ChannelLister\View\Components\Custom\CalculatedShippingService;

class CalculatedShippingServiceTest extends TestCase
{
    /**
     * Test that component renders basic calculated shipping service input correctly.
     */
    public function test_renders_basic_calculated_shipping_service(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'shipping_service',
            'display_name' => 'Calculated Shipping Service',
            'tooltip' => 'Enter the shipping service name',
            'example' => 'UPS Ground',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.calculated-shipping-service :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Check basic structure
        $view->assertSee('form-group', false);
        $view->assertSee('type="text"', false);

        // Check field-specific attributes
        $view->assertSee('name="shipping_service"', false);
        $view->assertSee('id="shipping_service-id"', false);
        $view->assertSee('Calculated Shipping Service');
        $view->assertSee('placeholder="UPS Ground"', false);

        // Check tooltip and maps to text
        $view->assertSee('Enter the shipping service name');
        $view->assertSee('Maps To: <code>shipping_service</code>', false);
    }

    /**
     * Test that required fields are marked as required.
     */
    public function test_required_field_has_required_attribute(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'required_shipping',
            'display_name' => 'Required Shipping Service',
            'required' => true,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.calculated-shipping-service :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Should have required attribute on input
        $view->assertSee('required', false);
        // Should have required class on form-group
        $view->assertSee('form-group required', false);
        $view->assertSee('name="required_shipping"', false);
        $view->assertSee('id="required_shipping-id"', false);
    }

    /**
     * Test that optional fields don't have required attribute.
     */
    public function test_optional_field_not_required(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'optional_shipping',
            'display_name' => 'Optional Shipping Service',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.calculated-shipping-service :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Form group should not have required class
        $view->assertSee('form-group', false);
        $view->assertDontSee('form-group required', false);

        // Should not have standalone required attribute in the input
        $rendered = (string) $view;
        $this->assertStringNotContainsString(' required>', $rendered);
    }

    /**
     * Test pattern attribute when input_type_aux is provided.
     */
    public function test_pattern_attribute_with_input_type_aux(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'shipping_service',
            'display_name' => 'Shipping Service',
            'input_type_aux' => '^(UPS|FedEx|USPS).*',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.calculated-shipping-service :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('pattern=&quot;^(UPS|FedEx|USPS).*&quot;', false);
    }

    /**
     * Test that field_name is used as label when display_name is empty.
     */
    public function test_uses_field_name_when_display_name_empty(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'shipping_method',
            'display_name' => null,
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.calculated-shipping-service :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Should show field_name as label
        $view->assertSee('shipping_method');
        $view->assertSee('for="shipping_method-id"', false);
    }

    /**
     * Test that display_name is preferred over field_name for label.
     */
    public function test_uses_display_name_when_provided(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'service',
            'display_name' => 'Shipping Service Type',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.calculated-shipping-service :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Should use display_name as label
        $view->assertSee('Shipping Service Type');

        // But field_name should still be used for name and id
        $view->assertSee('name="service"', false);
        $view->assertSee('id="service-id"', false);
    }

    /**
     * Test HTML in tooltip is rendered correctly.
     */
    public function test_html_in_tooltip_is_rendered(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'shipping_service',
            'tooltip' => 'Enter a <strong>valid</strong> shipping service like <em>UPS Ground</em>',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.calculated-shipping-service :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // HTML should be rendered (not escaped) due to {!! !!}
        $view->assertSee('Enter a <strong>valid</strong> shipping service like <em>UPS Ground</em>', false);
    }

    /**
     * Test empty tooltip renders empty paragraph.
     */
    public function test_empty_tooltip_renders_empty_paragraph(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'shipping_service',
            'tooltip' => null,
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.calculated-shipping-service :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Should still have the paragraph element but empty
        $view->assertSee('<p class="form-text text-secondary"></p>', false);
    }

    /**
     * Test placeholder attribute with example value.
     */
    public function test_placeholder_uses_example_value(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'shipping_service',
            'example' => 'FedEx Express Saver',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.calculated-shipping-service :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('placeholder="FedEx Express Saver"', false);
    }

    /**
     * Test empty placeholder when example is null.
     */
    public function test_empty_placeholder_when_no_example(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'shipping_service',
            'example' => null,
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.calculated-shipping-service :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('placeholder=""', false);
    }

    /**
     * Test that custom class string is applied to input.
     */
    public function test_custom_class_string_applied(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'shipping_service',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.calculated-shipping-service :params="$field" class-str-default="custom-shipping form-control-lg" />',
            ['field' => $field]
        );

        $view->assertSee('class="custom-shipping form-control-lg"', false);
    }

    /**
     * Test that Maps To text shows field_name in code tags.
     */
    public function test_maps_to_text_shows_field_name(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'calculated_shipping_service_type',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.calculated-shipping-service :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('Maps To: <code>calculated_shipping_service_type</code>', false);
    }

    /**
     * Test field with special characters in field_name.
     */
    public function test_field_name_with_special_characters(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'shipping-service-type',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.calculated-shipping-service :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('name="shipping-service-type"', false);
        $view->assertSee('id="shipping-service-type-id"', false);
        $view->assertSee('Maps To: <code>shipping-service-type</code>', false);
    }

    /**
     * Test component with all fields populated.
     */
    public function test_component_with_all_fields_populated(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'shipping_service',
            'display_name' => 'Calculated Shipping Service Type',
            'tooltip' => 'Enter the <strong>shipping</strong> service provider',
            'example' => 'UPS 2nd Day Air',
            'input_type_aux' => '^(UPS|FedEx|USPS|DHL).*',
            'required' => true,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.calculated-shipping-service :params="$field" class-str-default="form-control shipping-input" />',
            ['field' => $field]
        );

        // Check all elements are present and correct
        $view->assertSee('form-group required', false);
        $view->assertSee('Calculated Shipping Service Type');
        $view->assertSee('name="shipping_service"', false);
        $view->assertSee('id="shipping_service-id"', false);
        $view->assertSee('class="form-control shipping-input"', false);
        $view->assertSee('placeholder="UPS 2nd Day Air"', false);
        $view->assertSee('pattern=&quot;^(UPS|FedEx|USPS|DHL).*&quot;', false);
        $view->assertSee('required', false);
        $view->assertSee('Enter the <strong>shipping</strong> service provider', false);
        $view->assertSee('Maps To: <code>shipping_service</code>', false);
        $view->assertSee('type="text"', false);
    }

    /**
     * Test component with minimal fields (all optional fields null/empty).
     */
    public function test_component_with_minimal_fields(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'service',
            'display_name' => null,
            'tooltip' => null,
            'example' => null,
            'input_type_aux' => null,
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.calculated-shipping-service :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Check minimal rendering
        $view->assertSee('form-group', false);
        $view->assertDontSee('form-group required', false);
        $view->assertSee('service');
        $view->assertSee('name="service"', false);
        $view->assertSee('id="service-id"', false);
        $view->assertSee('placeholder=""', false);
        $view->assertSee('<p class="form-text text-secondary"></p>', false);
        $view->assertSee('Maps To: <code>service</code>', false);
        $view->assertDontSee('pattern=', false);
    }

    /**
     * Test the component class directly.
     */
    public function test_component_class_data_preparation(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'test_shipping',
            'display_name' => 'Test Shipping Service',
            'tooltip' => 'Test tooltip',
            'example' => 'Test service',
            'input_type_aux' => '^Test.*',
            'required' => true,
        ]);

        $component = new CalculatedShippingService($field, 'form-control');
        $view = $component->render();
        $data = $view->getData();

        // Verify all data is properly prepared
        $this->assertEquals('test_shipping', $data['element_name']);
        $this->assertEquals('required', $data['required']);
        $this->assertEquals('Test Shipping Service', $data['label_text']);
        $this->assertEquals('test_shipping-id', $data['id']);
        $this->assertEquals('Test tooltip', $data['tooltip']);
        $this->assertEquals('Test service', $data['placeholder']);
        $this->assertEquals('pattern="^Test.*"', $data['pattern']);
        $this->assertEquals('Maps To: <code>test_shipping</code>', $data['maps_to_text']);
    }

    /**
     * Test component with empty string vs null values.
     */
    public function test_empty_string_vs_null_handling(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'shipping_service',
            'display_name' => '',  // Empty string
            'tooltip' => '',       // Empty string
            'example' => '',       // Empty string
            'input_type_aux' => '', // Empty string
            'required' => false,
        ]);

        $component = new CalculatedShippingService($field, 'form-control');
        $view = $component->render();
        $data = $view->getData();

        // Empty display_name should return formatted field_name due to model accessor
        $this->assertEquals('Shipping Service', $data['label_text']);

        // Empty strings should be preserved
        $this->assertEquals('', $data['tooltip']);
        $this->assertEquals('', $data['placeholder']);
        $this->assertEquals('', $data['required']);
        $this->assertEquals('', $data['pattern']);
    }

    /**
     * Test that the correct Bootstrap classes are present.
     */
    public function test_bootstrap_classes_present(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'shipping_service',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.calculated-shipping-service :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Check Bootstrap classes
        $view->assertSee('form-group', false);
        $view->assertSee('col-form-label', false);
        $view->assertSee('form-text', false);
        $view->assertSee('form-control', false);
    }

    /**
     * Test XSS prevention in user-provided content.
     */
    public function test_xss_prevention_in_display_name(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'shipping_service',
            'display_name' => '<script>alert("XSS")</script>Shipping',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.calculated-shipping-service :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // The script tag should be escaped in the label
        $view->assertSee('&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;Shipping', false);
        $view->assertDontSee('<script>alert("XSS")</script>', false);
    }

    /**
     * Test that tooltip HTML is intentionally not escaped.
     */
    public function test_tooltip_html_intentionally_not_escaped(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'shipping_service',
            'tooltip' => 'Check <a href="/shipping">shipping guide</a> for options',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.calculated-shipping-service :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Tooltip should not be escaped (using {!! !!})
        $view->assertSee('Check <a href="/shipping">shipping guide</a> for options', false);
    }

    /**
     * Test text input type attribute.
     */
    public function test_text_input_type(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'shipping_service',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.calculated-shipping-service :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Should have text input type
        $view->assertSee('type="text"', false);
    }

    /**
     * Test id generation with various field names.
     */
    public function test_id_generation(): void
    {
        $testCases = [
            'simple_service' => 'simple_service-id',
            'shipping' => 'shipping-id',
            'calculated_shipping_service' => 'calculated_shipping_service-id',
            'service-with-dashes' => 'service-with-dashes-id',
        ];

        foreach ($testCases as $fieldName => $expectedId) {
            $field = ChannelListerField::factory()->create([
                'field_name' => $fieldName,
                'required' => false,
            ]);

            $view = $this->blade(
                '<x-channel-lister::custom.calculated-shipping-service :params="$field" class-str-default="form-control" />',
                ['field' => $field]
            );

            $view->assertSee("id=\"{$expectedId}\"", false);
            $view->assertSee("for=\"{$expectedId}\"", false);
        }
    }

    /**
     * Test default class string parameter.
     */
    public function test_default_class_string_parameter(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'shipping_service',
            'required' => false,
        ]);

        // Test with default class string
        $component = new CalculatedShippingService($field);
        $this->assertEquals('form-group', $component->classStrDefault);

        // Test with custom class string
        $component = new CalculatedShippingService($field, 'custom-class');
        $this->assertEquals('custom-class', $component->classStrDefault);
    }

    /**
     * Test various shipping service patterns.
     */
    public function test_various_shipping_service_patterns(): void
    {
        $patterns = [
            '^UPS.*',
            '^(UPS|FedEx|USPS).*',
            '.*Ground.*',
            '^[A-Z]{3,10}\\s+.*',
        ];

        foreach ($patterns as $pattern) {
            $field = ChannelListerField::factory()->create([
                'field_name' => 'service_'.md5($pattern),
                'input_type_aux' => $pattern,
                'required' => false,
            ]);

            $view = $this->blade(
                '<x-channel-lister::custom.calculated-shipping-service :params="$field" class-str-default="form-control" />',
                ['field' => $field]
            );

            $view->assertSee('pattern=&quot;'.$pattern.'&quot;', false);
        }
    }
}
