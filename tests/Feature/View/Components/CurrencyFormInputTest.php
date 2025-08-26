<?php

namespace IGE\ChannelLister\Tests\Feature\View\Components;

use IGE\ChannelLister\Models\ChannelListerField;
use IGE\ChannelLister\Tests\TestCase;
use IGE\ChannelLister\View\Components\CurrencyFormInput;

class CurrencyFormInputTest extends TestCase
{
    /**
     * Test that component renders basic currency input correctly.
     */
    public function test_renders_basic_currency_input(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'price',
            'display_name' => 'Product Price',
            'tooltip' => 'Enter the product price',
            'example' => '19.99',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::currency-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Check basic structure
        $view->assertSee('form-group', false);
        $view->assertSee('type="number"', false);
        $view->assertSee('step="0.01"', false);
        $view->assertSee('min="0"', false);

        // Check field-specific attributes
        $view->assertSee('name="price"', false);
        $view->assertSee('id="price-id"', false);
        $view->assertSee('Product Price');
        $view->assertSee('placeholder="19.99"', false);

        // Check tooltip and maps to text
        $view->assertSee('Enter the product price');
        $view->assertSee('Maps To: <code>price</code>', false);
    }

    /**
     * Test that required fields are marked as required.
     */
    public function test_required_field_has_required_attribute(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'minimum_price',
            'display_name' => 'Minimum Price',
            'required' => true,
        ]);

        $view = $this->blade(
            '<x-channel-lister::currency-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Should have required attribute on input
        $view->assertSee('required', false);
        // Should have required class on form-group
        $view->assertSee('form-group required', false);
        // Check that required appears as an attribute
        $view->assertSee('name="minimum_price"', false);
        $view->assertSee('id="minimum_price-id"', false);
        // The input should have the required attribute (not checking exact formatting)
        $rendered = (string) $view;
        $this->assertStringContainsString('required>', $rendered);
    }

    /**
     * Test that optional fields don't have required attribute.
     */
    public function test_optional_field_not_required(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'sale_price',
            'display_name' => 'Sale Price',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::currency-form-input :params="$field" class-str-default="form-control" />',
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
     * Test the ChannelListerField accessor behavior.
     */
    public function test_channel_lister_field_display_name_accessor(): void
    {
        // Test with null display_name
        $field = ChannelListerField::factory()->create([
            'field_name' => 'test_field_name',
            'display_name' => null,
        ]);

        // The accessor should transform field_name to Title Case
        $this->assertEquals('Test Field Name', $field->display_name);

        // Test with empty string display_name
        $field2 = ChannelListerField::factory()->create([
            'field_name' => 'another_test',
            'display_name' => '',
        ]);

        // The accessor should still transform field_name to Title Case
        $this->assertEquals('Another Test', $field2->display_name);

        // Test with actual display_name value
        $field3 = ChannelListerField::factory()->create([
            'field_name' => 'field_name',
            'display_name' => 'Custom Display Name',
        ]);

        // Should use the actual display_name
        $this->assertEquals('Custom Display Name', $field3->display_name);
    }

    /**
     * Test that field_name is used as label when display_name is empty.
     */
    public function test_uses_field_name_when_display_name_empty(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'cost_price',
            'display_name' => null,
            'required' => false,
        ]);

        // Test the component logic directly
        $component = new CurrencyFormInput($field, 'form-control');
        $view = $component->render();
        $data = $view->getData();

        // Due to the accessor, empty($this->params->display_name) will return false
        // because the accessor returns 'Cost Price' when display_name is null
        // So label_text will be the transformed display_name
        $this->assertEquals('Cost Price', $data['label_text']);

        // Now test the rendered view
        $bladeView = $this->blade(
            '<x-channel-lister::currency-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // The rendered view should show the transformed name
        $bladeView->assertSee('Cost Price');
        $bladeView->assertSee('for="cost_price-id"', false);
    }

    /**
     * Test that display_name is preferred over field_name for label.
     */
    public function test_uses_display_name_when_provided(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'msrp',
            'display_name' => 'Manufacturer Suggested Retail Price',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::currency-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Should use display_name as label
        $view->assertSee('Manufacturer Suggested Retail Price');
        $view->assertDontSee('msrp</label>', false);

        // But field_name should still be used for name and id
        $view->assertSee('name="msrp"', false);
        $view->assertSee('id="msrp-id"', false);
    }

    /**
     * Test HTML in tooltip is rendered correctly.
     */
    public function test_html_in_tooltip_is_rendered(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'price',
            'tooltip' => 'Enter price in <strong>USD</strong> format',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::currency-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // HTML should be rendered (not escaped) due to {!! !!}
        $view->assertSee('Enter price in <strong>USD</strong> format', false);
        $view->assertSee('<p class="form-text text-secondary">Enter price in <strong>USD</strong> format</p>', false);
    }

    /**
     * Test empty tooltip renders empty paragraph.
     */
    public function test_empty_tooltip_renders_empty_paragraph(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'price',
            'tooltip' => null,
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::currency-form-input :params="$field" class-str-default="form-control" />',
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
            'field_name' => 'price',
            'example' => '29.95',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::currency-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('placeholder="29.95"', false);
    }

    /**
     * Test empty placeholder when example is null.
     */
    public function test_empty_placeholder_when_no_example(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'price',
            'example' => null,
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::currency-form-input :params="$field" class-str-default="form-control" />',
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
            'field_name' => 'price',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::currency-form-input :params="$field" class-str-default="custom-input-class form-control-lg" />',
            ['field' => $field]
        );

        $view->assertSee('class="custom-input-class form-control-lg"', false);
    }

    /**
     * Test that Maps To text shows field_name in code tags.
     */
    public function test_maps_to_text_shows_field_name(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'wholesale_price',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::currency-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('Maps To: <code>wholesale_price</code>', false);
        $view->assertSee('<p class="form-text text-secondary">Maps To: <code>wholesale_price</code></p>', false);
    }

    /**
     * Test field with special characters in field_name.
     */
    public function test_field_name_with_special_characters(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'price-usd-wholesale',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::currency-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('name="price-usd-wholesale"', false);
        $view->assertSee('id="price-usd-wholesale-id"', false);
        $view->assertSee('Maps To: <code>price-usd-wholesale</code>', false);
    }

    /**
     * Test component with all fields populated.
     */
    public function test_component_with_all_fields_populated(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'product_price',
            'display_name' => 'Product Price (USD)',
            'tooltip' => 'Enter the price in <em>US Dollars</em>',
            'example' => '49.99',
            'required' => true,
        ]);

        $view = $this->blade(
            '<x-channel-lister::currency-form-input :params="$field" class-str-default="form-control currency-input" />',
            ['field' => $field]
        );

        // Check all elements are present and correct
        $view->assertSee('form-group required', false);
        $view->assertSee('Product Price (USD)');
        $view->assertSee('name="product_price"', false);
        $view->assertSee('id="product_price-id"', false);
        $view->assertSee('class="form-control currency-input"', false);
        $view->assertSee('placeholder="49.99"', false);
        $view->assertSee('required', false);
        $view->assertSee('Enter the price in <em>US Dollars</em>', false);
        $view->assertSee('Maps To: <code>product_price</code>', false);
        $view->assertSee('type="number"', false);
        $view->assertSee('step="0.01"', false);
        $view->assertSee('min="0"', false);
    }

    /**
     * Test component with minimal fields (all optional fields null/empty).
     */
    public function test_component_with_minimal_fields(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'amount',
            'display_name' => null,
            'tooltip' => null,
            'example' => null,
            'required' => false,
        ]);

        // Test component logic first
        $component = new CurrencyFormInput($field, 'form-control');
        $view = $component->render();
        $data = $view->getData();

        // Due to the accessor, display_name will return 'Amount' when null
        $this->assertEquals('Amount', $data['label_text']);

        // Test the rendered view
        $bladeView = $this->blade(
            '<x-channel-lister::currency-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Check minimal rendering
        $bladeView->assertSee('form-group', false);
        $bladeView->assertDontSee('form-group required', false);
        $bladeView->assertSee('Amount');  // Accessor transforms 'amount' to 'Amount'
        $bladeView->assertSee('name="amount"', false);
        $bladeView->assertSee('id="amount-id"', false);
        $bladeView->assertSee('placeholder=""', false);
        $bladeView->assertSee('<p class="form-text text-secondary"></p>', false);
        $bladeView->assertSee('Maps To: <code>amount</code>', false);
    }

    /**
     * Test the component class directly.
     */
    public function test_component_class_data_preparation(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'test_price',
            'display_name' => 'Test Price',
            'tooltip' => 'Test tooltip',
            'example' => '99.99',
            'required' => true,
        ]);

        $component = new CurrencyFormInput($field, 'form-control');
        $view = $component->render();
        $data = $view->getData();

        // Verify all data is properly prepared
        $this->assertEquals($field, $data['params']);
        $this->assertEquals('test_price', $data['element_name']);
        $this->assertEquals('required', $data['required']);
        $this->assertEquals('Test Price', $data['label_text']);
        $this->assertEquals('test_price-id', $data['id']);
        $this->assertEquals('Test tooltip', $data['tooltip']);
        $this->assertEquals('99.99', $data['placeholder']);
        $this->assertEquals('Maps To: <code>test_price</code>', $data['maps_to_text']);
    }

    /**
     * Test component with empty string vs null values.
     */
    public function test_empty_string_vs_null_handling(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'price',
            'display_name' => '',  // Empty string
            'tooltip' => '',       // Empty string
            'example' => '',       // Empty string
            'required' => false,
        ]);

        $component = new CurrencyFormInput($field, 'form-control');
        $view = $component->render();
        $data = $view->getData();

        // The accessor in the model transforms empty display_name to title case field_name
        // When display_name is empty string, the accessor returns 'Price'
        // So empty($this->params->display_name) is false, and it uses the accessor value
        $this->assertEquals('Price', $data['label_text']);

        // Empty strings should be preserved for other fields
        $this->assertEquals('', $data['tooltip']);
        $this->assertEquals('', $data['placeholder']);
        $this->assertEquals('', $data['required']);
    }

    /**
     * Test that the correct Bootstrap classes are present.
     */
    public function test_bootstrap_classes_present(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'price',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::currency-form-input :params="$field" class-str-default="form-control" />',
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
            'field_name' => 'price',
            'display_name' => '<script>alert("XSS")</script>Price',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::currency-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // The script tag should be escaped in the label (single escaping)
        $view->assertSee('&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;Price', false);
        $view->assertDontSee('<script>alert("XSS")</script>', false);
    }

    /**
     * Test that tooltip HTML is intentionally not escaped.
     */
    public function test_tooltip_html_intentionally_not_escaped(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'price',
            'tooltip' => 'Use <a href="/help">this guide</a> for pricing',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::currency-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Tooltip should not be escaped (using {!! !!})
        $view->assertSee('Use <a href="/help">this guide</a> for pricing', false);
    }

    /**
     * Test numeric field attributes for currency.
     */
    public function test_numeric_field_attributes(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'price',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::currency-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Check that all numeric attributes are present
        $view->assertSee('type="number"', false);
        $view->assertSee('step="0.01"', false);  // For cents
        $view->assertSee('min="0"', false);       // No negative prices
    }

    /**
     * Test id generation with various field names.
     */
    public function test_id_generation(): void
    {
        $testCases = [
            'simple_price' => 'simple_price-id',
            'price' => 'price-id',
            'wholesale_cost_price' => 'wholesale_cost_price-id',
            'price-with-dashes' => 'price-with-dashes-id',
        ];

        foreach ($testCases as $fieldName => $expectedId) {
            $field = ChannelListerField::factory()->create([
                'field_name' => $fieldName,
                'required' => false,
            ]);

            $view = $this->blade(
                '<x-channel-lister::currency-form-input :params="$field" class-str-default="form-control" />',
                ['field' => $field]
            );

            $view->assertSee("id=\"{$expectedId}\"", false);
            $view->assertSee("for=\"{$expectedId}\"", false);
        }
    }
}
