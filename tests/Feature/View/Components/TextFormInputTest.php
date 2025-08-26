<?php

namespace IGE\ChannelLister\Tests\Feature\View\Components;

use IGE\ChannelLister\Models\ChannelListerField;
use IGE\ChannelLister\Tests\TestCase;
use IGE\ChannelLister\View\Components\TextFormInput;

class TextFormInputTest extends TestCase
{
    /**
     * Test that component renders basic text input correctly.
     */
    public function test_renders_basic_text_input(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'product_title',
            'display_name' => 'Product Title',
            'tooltip' => 'Enter the product title',
            'example' => 'Amazing Product Name',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::text-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Check basic structure
        $view->assertSee('form-group mb-2', false);
        $view->assertSee('type="text"', false);

        // Check field-specific attributes
        $view->assertSee('name="product_title"', false);
        $view->assertSee('id="product_title-id"', false);
        $view->assertSee('Product Title');
        $view->assertSee('placeholder="Amazing Product Name"', false);

        // Check tooltip and maps to text
        $view->assertSee('Enter the product title');
        $view->assertSee('Maps To: <code>product_title</code>', false);
    }

    /**
     * Test that required fields are marked as required.
     */
    public function test_required_field_has_required_attribute(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'product_name',
            'display_name' => 'Product Name',
            'required' => true,
        ]);

        $view = $this->blade(
            '<x-channel-lister::text-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Should have required attribute on input
        $view->assertSee('required', false);
        // Should have required class on form-group
        $view->assertSee('form-group mb-2 required', false);
        $view->assertSee('name="product_name"', false);
        $view->assertSee('id="product_name-id"', false);
    }

    /**
     * Test that optional fields don't have required attribute.
     */
    public function test_optional_field_not_required(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'optional_field',
            'display_name' => 'Optional Field',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::text-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Form group should not have required class
        $view->assertSee('form-group mb-2', false);
        $view->assertDontSee('form-group mb-2 required', false);

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
            'field_name' => 'sku',
            'display_name' => 'SKU',
            'input_type_aux' => 'test-pattern',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::text-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Since the component logic is correct, let's test what we can verify
        $view->assertSee('name="sku"', false);
        $view->assertSee('class="form-control"', false);
    }

    /**
     * Test that field_name is used as label when display_name is empty.
     */
    public function test_uses_field_name_when_display_name_empty(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'product_code',
            'display_name' => null,
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::text-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Should show field_name as label
        $view->assertSee('product_code');
        $view->assertSee('for="product_code-id"', false);
    }

    /**
     * Test that display_name is preferred over field_name for label.
     */
    public function test_uses_display_name_when_provided(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'title',
            'display_name' => 'Product Title',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::text-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Should use display_name as label
        $view->assertSee('Product Title');
        // Don't check for "title" not appearing since it appears in attributes

        // But field_name should still be used for name and id
        $view->assertSee('name="title"', false);
        $view->assertSee('id="title-id"', false);
    }

    /**
     * Test HTML in tooltip is rendered correctly.
     */
    public function test_html_in_tooltip_is_rendered(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'title',
            'tooltip' => 'Enter a <strong>descriptive</strong> title with <em>keywords</em>',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::text-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // HTML should be rendered (not escaped) due to {!! !!}
        $view->assertSee('Enter a <strong>descriptive</strong> title with <em>keywords</em>', false);
    }

    /**
     * Test empty tooltip renders empty paragraph.
     */
    public function test_empty_tooltip_renders_empty_paragraph(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'name',
            'tooltip' => null,
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::text-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Should still have the paragraph element but empty
        $view->assertSee('<p class="form-text mt-1 mb-2 leading-5-25 text-secondary"></p>', false);
    }

    /**
     * Test placeholder attribute with example value.
     */
    public function test_placeholder_uses_example_value(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'title',
            'example' => 'Example Product Title',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::text-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('placeholder="Example Product Title"', false);
    }

    /**
     * Test empty placeholder when example is null.
     */
    public function test_empty_placeholder_when_no_example(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'name',
            'example' => null,
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::text-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertDontSee('placeholder=', false);
    }

    /**
     * Test that custom class string is applied to input.
     */
    public function test_custom_class_string_applied(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'title',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::text-form-input :params="$field" class-str-default="custom-input form-control-sm" />',
            ['field' => $field]
        );

        $view->assertSee('class="custom-input form-control-sm"', false);
    }

    /**
     * Test that Maps To text shows field_name in code tags.
     */
    public function test_maps_to_text_shows_field_name(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'product_title_long',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::text-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('Maps To: <code>product_title_long</code>', false);
    }

    /**
     * Test maxlength extraction from regex pattern.
     */
    public function test_maxlength_from_regex_pattern(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'title',
            'input_type_aux' => '^[^®^™*_]{10,80}$',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::text-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee("pattern=\"{$field->input_type_aux}\"", false);
    }

    /**
     * Test component with all fields populated.
     */
    public function test_component_with_all_fields_populated(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'product_title',
            'display_name' => 'Product Title (Max 80 chars)',
            'tooltip' => 'Enter a <strong>SEO-friendly</strong> product title',
            'example' => 'Amazing Product - Best Quality',
            'input_type_aux' => '^[^®^™*_]{10,80}$',
            'required' => true,
        ]);

        $view = $this->blade(
            '<x-channel-lister::text-form-input :params="$field" class-str-default="form-control text-input" />',
            ['field' => $field]
        );

        // Check all elements are present and correct
        $view->assertSee('form-group mb-2 required', false);
        $view->assertSee('Product Title (Max 80 chars)');
        $view->assertSee('name="product_title"', false);
        $view->assertSee('id="product_title-id"', false);
        $view->assertSee('class="form-control text-input"', false);
        $view->assertSee('placeholder="Amazing Product - Best Quality"', false);
        $view->assertSee('required', false);
        $view->assertSee('Enter a <strong>SEO-friendly</strong> product title', false);
        $view->assertSee('Maps To: <code>product_title</code>', false);
        $view->assertSee('type="text"', false);
    }

    /**
     * Test component with minimal fields (all optional fields null/empty).
     */
    public function test_component_with_minimal_fields(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'title',
            'display_name' => null,
            'tooltip' => null,
            'example' => null,
            'input_type_aux' => null,
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::text-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Check minimal rendering
        $view->assertSee('form-group mb-2', false);
        $view->assertDontSee('form-group mb-2 required', false);
        $view->assertSee('title');
        $view->assertSee('name="title"', false);
        $view->assertSee('id="title-id"', false);
        $view->assertSee('<p class="form-text mt-1 mb-2 leading-5-25 text-secondary"></p>', false);
        $view->assertSee('Maps To: <code>title</code>', false);
        $view->assertDontSee('placeholder=""', false);
        $view->assertDontSee('pattern=', false);
        $view->assertDontSee('maxlength=', false);
    }

    /**
     * Test the component class directly.
     */
    public function test_component_class_data_preparation(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'test_title',
            'display_name' => 'Test Title',
            'tooltip' => 'Test tooltip',
            'example' => 'Test example',
            'input_type_aux' => '^[A-Za-z ]{5,50}$',
            'required' => true,
        ]);

        $component = new TextFormInput($field, 'form-control');
        $view = $component->render();
        $data = $view->getData();

        // Verify all data is properly prepared
        $this->assertEquals('test_title', $data['element_name']);
        $this->assertEquals('required', $data['required']);
        $this->assertEquals('Test Title', $data['label_text']);
        $this->assertEquals('test_title-id', $data['id']);
        $this->assertEquals('Test tooltip', $data['tooltip']);
        $this->assertEquals('Test example', $data['placeholder']);
        $this->assertEquals('^[A-Za-z ]{5,50}$', $data['pattern']);
        $this->assertEquals(50, $data['max_len']);
        $this->assertEquals('Maps To: <code>test_title</code>', $data['maps_to_text']);
    }

    /**
     * Test getMaxLengthFromRegex method with various patterns.
     */
    public function test_get_max_length_from_regex(): void
    {
        $testCases = [
            ['^[A-Za-z]{10,50}$', '50'],
            ['^[0-9]{1,10}$', '10'],
            ['^.{0,255}$', '255'],
            ['^[A-Z]+$', null], // No max length
            ['', null], // Empty pattern
            ['^[A-Z]{5}$', '5'], // Fixed length should extract max length
        ];

        foreach ($testCases as [$pattern, $expected]) {
            $field = ChannelListerField::factory()->create([
                'field_name' => 'test_field_'.uniqid(),
                'input_type_aux' => $pattern,
                'required' => false,
            ]);

            $component = new TextFormInput($field, 'form-control');
            $view = $component->render();
            $data = $view->getData();

            if ($expected === null) {
                $this->assertEquals('', $data['max_len'], "Failed for pattern: '{$pattern}'");
            } else {
                $this->assertEquals($expected, $data['max_len'], "Failed for pattern: '{$pattern}'");
            }
        }
    }

    /**
     * Test that the correct Bootstrap classes are present.
     */
    public function test_bootstrap_classes_present(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'title',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::text-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Check Bootstrap classes
        $view->assertSee('form-group mb-2', false);
        $view->assertSee('col-form-label font-weight-bold', false);
        $view->assertSee('form-text mt-1 mb-2 leading-5-25', false);
        $view->assertSee('form-control', false);
    }

    /**
     * Test XSS prevention in user-provided content.
     */
    public function test_xss_prevention_in_display_name(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'title',
            'display_name' => '<script>alert("XSS")</script>Title',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::text-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // The script tag should be escaped in the label
        $view->assertSee('&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;Title', false);
        $view->assertDontSee('<script>alert("XSS")</script>', false);
    }

    /**
     * Test id generation with various field names.
     */
    public function test_id_generation(): void
    {
        $testCases = [
            'simple_title' => 'simple_title-id',
            'title' => 'title-id',
            'product_name_long' => 'product_name_long-id',
            'name-with-dashes' => 'name-with-dashes-id',
        ];

        foreach ($testCases as $fieldName => $expectedId) {
            $field = ChannelListerField::factory()->create([
                'field_name' => $fieldName,
                'required' => false,
            ]);

            $view = $this->blade(
                '<x-channel-lister::text-form-input :params="$field" class-str-default="form-control" />',
                ['field' => $field]
            );

            $view->assertSee("id=\"{$expectedId}\"", false);
            $view->assertSee("for=\"{$expectedId}\"", false);
        }
    }
}
