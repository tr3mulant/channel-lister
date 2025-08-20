<?php

namespace IGE\ChannelLister\Tests\Feature\View\Components;

use IGE\ChannelLister\Models\ChannelListerField;
use IGE\ChannelLister\Tests\TestCase;
use IGE\ChannelLister\View\Components\DecimalFormInput;
use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\DataProvider;

class DecimalFormInputTest extends TestCase
{
    /**
     * Test that component renders basic decimal input correctly.
     */
    public function test_renders_basic_decimal_input(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'weight',
            'display_name' => 'Product Weight',
            'tooltip' => 'Enter the product weight in pounds',
            'example' => '1.5',
            'input_type_aux' => '0.01',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::decimal-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Check basic structure
        $view->assertSee('form-group', false);
        $view->assertSee('type="number"', false);
        $view->assertSee('step="0.01"', false);
        $view->assertSee('min="0"', false);

        // Check field-specific attributes
        $view->assertSee('name="weight"', false);
        $view->assertSee('id="weight-id"', false);
        $view->assertSee('Product Weight');
        $view->assertSee('placeholder="1.5"', false);

        // Check tooltip and maps to text
        $view->assertSee('Enter the product weight in pounds');
        $view->assertSee('Maps To: <code>weight</code>', false);
    }

    /**
     * Test that default step size is used when input_type_aux is not provided.
     */
    public function test_default_step_size_when_no_aux(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'dimension',
            'display_name' => 'Dimension',
            'input_type_aux' => null,
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::decimal-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Should use default step size of 0.001
        $view->assertSee('step="0.001"', false);
    }

    /**
     * Test that default step size is used when input_type_aux is empty string.
     */
    public function test_default_step_size_when_aux_empty_string(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'measurement',
            'display_name' => 'Measurement',
            'input_type_aux' => '',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::decimal-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Should use default step size of 0.001
        $view->assertSee('step="0.001"', false);
    }

    /**
     * Test that default step size is used when input_type_aux is non-numeric.
     */
    public function test_default_step_size_when_aux_non_numeric(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'size',
            'display_name' => 'Size',
            'input_type_aux' => 'not-a-number',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::decimal-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Should use default step size of 0.001 when non-numeric
        $view->assertSee('step="0.001"', false);
    }

    /**
     * Test that custom step sizes work correctly.
     */
    #[DataProvider('stepSizeProvider')]
    public static function test_custom_step_sizes(string $stepSize): void
    {
        // $field = ChannelListerField::factory()->create([
        //     'field_name' => 'measurement',
        //     'input_type_aux' => $stepSize,
        //     'required' => false,
        // ]);
        $field = ChannelListerField::factory()->create([
            'field_name' => 'measurement',
            'input_type_aux' => $stepSize,
            'required' => false,
        ]);

        $renderedHtml = Blade::renderComponent(new DecimalFormInput($field, 'form-control'));

        self::assertStringContainsString("step=\"{$stepSize}\"", $renderedHtml);

        /*
        $view = Blade::renderComponent(new DecimalFormInput($field));(
            '<x-channel-lister::decimal-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee("step=\"{$stepSize}\"", false);
        */
    }

    /**
     * Data provider for step sizes.
     */
    public static function stepSizeProvider(): array
    {
        return [
            ['0.1'],
            ['0.01'],
            ['0.001'],
            ['0.0001'],
            ['1'],
            ['0.5'],
            ['0.25'],
            ['10'],
        ];
    }

    /**
     * Test that step size handles whitespace correctly.
     */
    public function test_step_size_with_whitespace(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'width',
            'input_type_aux' => '  0.05  ',  // Whitespace around the number
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::decimal-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // trim() should handle the whitespace
        $view->assertSee('step="0.05"', false);
    }

    /**
     * Test that required fields are marked as required.
     */
    public function test_required_field_has_required_attribute(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'minimum_weight',
            'display_name' => 'Minimum Weight',
            'required' => true,
        ]);

        $view = $this->blade(
            '<x-channel-lister::decimal-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Should have required attribute on input
        $view->assertSee('required', false);
        // Should have required class on form-group
        $view->assertSee('form-group required', false);
        // Check that required appears as an attribute
        $view->assertSee('name="minimum_weight"', false);
        $view->assertSee('id="minimum_weight-id"', false);
    }

    /**
     * Test that optional fields don't have required attribute.
     */
    public function test_optional_field_not_required(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'optional_measurement',
            'display_name' => 'Optional Measurement',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::decimal-form-input :params="$field" class-str-default="form-control" />',
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
     * Test that field_name is used as label when display_name is empty.
     */
    public function test_uses_field_name_when_display_name_empty(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'product_length',
            'display_name' => null,
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::decimal-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Due to the accessor, should show title-cased version
        $view->assertSee('Product Length');
        $view->assertSee('for="product_length-id"', false);
    }

    /**
     * Test that display_name is preferred over field_name for label.
     */
    public function test_uses_display_name_when_provided(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'dim_x',
            'display_name' => 'Width (inches)',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::decimal-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Should use display_name as label
        $view->assertSee('Width (inches)');
        $view->assertDontSee('Dim X', false);

        // But field_name should still be used for name and id
        $view->assertSee('name="dim_x"', false);
        $view->assertSee('id="dim_x-id"', false);
    }

    /**
     * Test HTML in tooltip is rendered correctly.
     */
    public function test_html_in_tooltip_is_rendered(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'weight',
            'tooltip' => 'Enter weight in <strong>kilograms</strong> with up to 3 decimal places',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::decimal-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // HTML should be rendered (not escaped) due to {!! !!}
        $view->assertSee('Enter weight in <strong>kilograms</strong> with up to 3 decimal places', false);
    }

    /**
     * Test empty tooltip renders empty paragraph.
     */
    public function test_empty_tooltip_renders_empty_paragraph(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'height',
            'tooltip' => null,
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::decimal-form-input :params="$field" class-str-default="form-control" />',
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
            'field_name' => 'length',
            'example' => '12.345',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::decimal-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('placeholder="12.345"', false);
    }

    /**
     * Test empty placeholder when example is null.
     */
    public function test_empty_placeholder_when_no_example(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'width',
            'example' => null,
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::decimal-form-input :params="$field" class-str-default="form-control" />',
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
            'field_name' => 'measurement',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::decimal-form-input :params="$field" class-str-default="custom-decimal-input form-control-sm" />',
            ['field' => $field]
        );

        $view->assertSee('class="custom-decimal-input form-control-sm"', false);
    }

    /**
     * Test that Maps To text shows field_name in code tags.
     */
    public function test_maps_to_text_shows_field_name(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'product_weight_kg',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::decimal-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('Maps To: <code>product_weight_kg</code>', false);
    }

    /**
     * Test field with special characters in field_name.
     */
    public function test_field_name_with_special_characters(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'weight-in-pounds',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::decimal-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('name="weight-in-pounds"', false);
        $view->assertSee('id="weight-in-pounds-id"', false);
        $view->assertSee('Maps To: <code>weight-in-pounds</code>', false);
    }

    /**
     * Test component with all fields populated.
     */
    public function test_component_with_all_fields_populated(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'product_dimension',
            'display_name' => 'Product Dimension (cm)',
            'tooltip' => 'Enter the dimension in <em>centimeters</em>',
            'example' => '25.75',
            'input_type_aux' => '0.25',
            'required' => true,
        ]);

        $view = $this->blade(
            '<x-channel-lister::decimal-form-input :params="$field" class-str-default="form-control decimal-input" />',
            ['field' => $field]
        );

        // Check all elements are present and correct
        $view->assertSee('form-group required', false);
        $view->assertSee('Product Dimension (cm)');
        $view->assertSee('name="product_dimension"', false);
        $view->assertSee('id="product_dimension-id"', false);
        $view->assertSee('class="form-control decimal-input"', false);
        $view->assertSee('placeholder="25.75"', false);
        $view->assertSee('step="0.25"', false);
        $view->assertSee('required', false);
        $view->assertSee('Enter the dimension in <em>centimeters</em>', false);
        $view->assertSee('Maps To: <code>product_dimension</code>', false);
        $view->assertSee('type="number"', false);
        $view->assertSee('min="0"', false);
    }

    /**
     * Test component with minimal fields (all optional fields null/empty).
     */
    public function test_component_with_minimal_fields(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'value',
            'display_name' => null,
            'tooltip' => null,
            'example' => null,
            'input_type_aux' => null,
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::decimal-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Check minimal rendering
        $view->assertSee('form-group', false);
        $view->assertDontSee('form-group required', false);
        $view->assertSee('Value');  // Accessor transforms 'value' to 'Value'
        $view->assertSee('name="value"', false);
        $view->assertSee('id="value-id"', false);
        $view->assertSee('placeholder=""', false);
        $view->assertSee('step="0.001"', false);  // Default step
        $view->assertSee('<p class="form-text text-secondary"></p>', false);
        $view->assertSee('Maps To: <code>value</code>', false);
    }

    /**
     * Test the component class directly.
     */
    public function test_component_class_data_preparation(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'test_measurement',
            'display_name' => 'Test Measurement',
            'tooltip' => 'Test tooltip',
            'example' => '99.999',
            'input_type_aux' => '0.1',
            'required' => true,
        ]);

        $component = new DecimalFormInput($field, 'form-control');
        $view = $component->render();
        $data = $view->getData();

        // Verify all data is properly prepared
        $this->assertEquals($field, $data['params']);
        $this->assertEquals('test_measurement', $data['element_name']);
        $this->assertEquals('required', $data['required']);
        $this->assertEquals('Test Measurement', $data['label_text']);
        $this->assertEquals('test_measurement-id', $data['id']);
        $this->assertEquals('Test tooltip', $data['tooltip']);
        $this->assertEquals('99.999', $data['placeholder']);
        $this->assertEquals('0.1', $data['step_size']);
        $this->assertEquals('Maps To: <code>test_measurement</code>', $data['maps_to_text']);
    }

    /**
     * Test component with empty string vs null values.
     */
    public function test_empty_string_vs_null_handling(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'measurement',
            'display_name' => '',  // Empty string
            'tooltip' => '',       // Empty string
            'example' => '',       // Empty string
            'input_type_aux' => '', // Empty string
            'required' => false,
        ]);

        $component = new DecimalFormInput($field, 'form-control');
        $view = $component->render();
        $data = $view->getData();

        // Due to accessor, empty display_name returns formatted field_name
        $this->assertEquals('Measurement', $data['label_text']);

        // Empty strings should be preserved
        $this->assertEquals('', $data['tooltip']);
        $this->assertEquals('', $data['placeholder']);
        $this->assertEquals('', $data['required']);

        // Empty input_type_aux should default to 0.001
        $this->assertEquals('0.001', $data['step_size']);
    }

    /**
     * Test that the correct Bootstrap classes are present.
     */
    public function test_bootstrap_classes_present(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'dimension',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::decimal-form-input :params="$field" class-str-default="form-control" />',
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
            'field_name' => 'weight',
            'display_name' => '<script>alert("XSS")</script>Weight',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::decimal-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // The script tag should be escaped in the label
        $view->assertSee('&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;Weight', false);
        $view->assertDontSee('<script>alert("XSS")</script>', false);
    }

    /**
     * Test that tooltip HTML is intentionally not escaped.
     */
    public function test_tooltip_html_intentionally_not_escaped(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'length',
            'tooltip' => 'Use <a href="/help">this guide</a> for measurements',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::decimal-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Tooltip should not be escaped (using {!! !!})
        $view->assertSee('Use <a href="/help">this guide</a> for measurements', false);
    }

    /**
     * Test numeric field attributes for decimal.
     */
    public function test_numeric_field_attributes(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'measurement',
            'input_type_aux' => '0.5',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::decimal-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Check that all numeric attributes are present
        $view->assertSee('type="number"', false);
        $view->assertSee('step="0.5"', false);
        $view->assertSee('min="0"', false);  // No negative values
    }

    /**
     * Test id generation with various field names.
     */
    public function test_id_generation(): void
    {
        $testCases = [
            'simple_measurement' => 'simple_measurement-id',
            'weight' => 'weight-id',
            'product_dimension_x' => 'product_dimension_x-id',
            'size-with-dashes' => 'size-with-dashes-id',
        ];

        foreach ($testCases as $fieldName => $expectedId) {
            $field = ChannelListerField::factory()->create([
                'field_name' => $fieldName,
                'required' => false,
            ]);

            $view = $this->blade(
                '<x-channel-lister::decimal-form-input :params="$field" class-str-default="form-control" />',
                ['field' => $field]
            );

            $view->assertSee("id=\"{$expectedId}\"", false);
            $view->assertSee("for=\"{$expectedId}\"", false);
        }
    }

    /**
     * Test edge cases for step size validation.
     */
    public function test_step_size_edge_cases(): void
    {
        $edgeCases = [
            ['0', '0.001'],      // Zero should be non-numeric in this context
            ['', '0.001'],       // Empty string
            [' ', '0.001'],      // Just whitespace
            ['abc', '0.001'],    // Letters
            ['1.2.3', '0.001'],  // Invalid decimal
            ['$0.01', '0.001'],  // Currency symbol
            ['-0.5', '-0.5'],    // Negative number (should work)
            ['1e-3', '1e-3'],    // Scientific notation (should work)
        ];

        foreach ($edgeCases as [$input, $expected]) {
            $field = ChannelListerField::factory()->create([
                'field_name' => 'test_field_'.uniqid(),
                'input_type_aux' => $input,
                'required' => false,
            ]);

            $component = new DecimalFormInput($field, 'form-control');
            $view = $component->render();
            $data = $view->getData();

            $this->assertEquals($expected, $data['step_size'], "Failed for input: '{$input}'");
        }
    }

    /**
     * Test very small and very large step sizes.
     */
    public function test_extreme_step_sizes(): void
    {
        $extremeCases = [
            '0.0000001',  // Very small
            '1000000',    // Very large
            '999.999',    // Large decimal
        ];

        foreach ($extremeCases as $stepSize) {
            $field = ChannelListerField::factory()->create([
                'field_name' => 'extreme_field'.uniqid(),
                'input_type_aux' => $stepSize,
                'required' => false,
            ]);

            $view = $this->blade(
                '<x-channel-lister::decimal-form-input :params="$field" class-str-default="form-control" />',
                ['field' => $field]
            );

            $view->assertSee("step=\"{$stepSize}\"", false);
        }
    }

    /**
     * Test component with various decimal precisions.
     */
    public function test_various_decimal_precisions(): void
    {
        $precisions = [
            ['weight_kg', '0.001', '1.234'],      // 3 decimal places
            ['length_cm', '0.01', '12.34'],       // 2 decimal places
            ['width_m', '0.1', '1.2'],            // 1 decimal place
            ['count', '1', '5'],                  // Whole numbers
        ];

        foreach ($precisions as [$fieldName, $stepSize, $example]) {
            $field = ChannelListerField::factory()->create([
                'field_name' => $fieldName,
                'input_type_aux' => $stepSize,
                'example' => $example,
                'required' => false,
            ]);

            $view = $this->blade(
                '<x-channel-lister::decimal-form-input :params="$field" class-str-default="form-control" />',
                ['field' => $field]
            );

            $view->assertSee("step=\"{$stepSize}\"", false);
            $view->assertSee("placeholder=\"{$example}\"", false);
        }
    }
}
