<?php

namespace IGE\ChannelLister\Tests\Feature\View\Components;

use IGE\ChannelLister\Models\ChannelListerField;
use IGE\ChannelLister\Tests\TestCase;
use IGE\ChannelLister\View\Components\TextareaFormInput;

class TextareaFormInputTest extends TestCase
{
    /**
     * Test that component renders basic textarea input correctly.
     */
    public function test_renders_basic_textarea_input(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'description',
            'display_name' => 'Product Description',
            'tooltip' => 'Enter a detailed product description',
            'example' => 'High-quality product...',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::textarea-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Check basic structure
        $view->assertSee('form-group', false);
        $view->assertSee('<textarea', false);

        // Check field-specific attributes
        $view->assertSee('name="description"', false);
        $view->assertSee('id="description-id"', false);
        $view->assertSee('Product Description');
        $view->assertSee('placeholder="High-quality product..."', false);

        // Check tooltip and maps to text
        $view->assertSee('Enter a detailed product description');
        $view->assertSee('Maps To: <code>description</code>', false);
    }

    /**
     * Test that required fields are marked as required.
     */
    public function test_required_field_has_required_attribute(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'product_details',
            'display_name' => 'Product Details',
            'required' => true,
        ]);

        $view = $this->blade(
            '<x-channel-lister::textarea-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Should have required attribute on textarea
        $view->assertSee('required', false);
        // Should have required class on form-group
        $view->assertSee('form-group required', false);
        $view->assertSee('name="product_details"', false);
        $view->assertSee('id="product_details-id"', false);
    }

    /**
     * Test that optional fields don't have required attribute.
     */
    public function test_optional_field_not_required(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'optional_notes',
            'display_name' => 'Optional Notes',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::textarea-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Form group should not have required class
        $view->assertSee('form-group', false);
        $view->assertDontSee('form-group required', false);

        // Should not have standalone required attribute in the textarea
        $rendered = (string) $view;
        $this->assertStringNotContainsString(' required>', $rendered);
    }

    /**
     * Test that field_name is used as label when display_name is empty.
     */
    public function test_uses_field_name_when_display_name_empty(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'product_summary',
            'display_name' => null,
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::textarea-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Should show field_name as label
        $view->assertSee('product_summary');
        $view->assertSee('for="product_summary-id"', false);
    }

    /**
     * Test that display_name is preferred over field_name for label.
     */
    public function test_uses_display_name_when_provided(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'desc',
            'display_name' => 'Detailed Description',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::textarea-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Should use display_name as label
        $view->assertSee('Detailed Description');
        // Note: We can't check that 'desc' doesn't appear since it's used in HTML attributes

        // But field_name should still be used for name and id
        $view->assertSee('name="desc"', false);
        $view->assertSee('id="desc-id"', false);
    }

    /**
     * Test HTML in tooltip is rendered correctly.
     */
    public function test_html_in_tooltip_is_rendered(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'description',
            'tooltip' => 'Enter a <strong>detailed</strong> description with <em>formatting</em>',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::textarea-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // HTML should be rendered (not escaped) due to {!! !!}
        $view->assertSee('Enter a <strong>detailed</strong> description with <em>formatting</em>', false);
    }

    /**
     * Test empty tooltip renders empty paragraph.
     */
    public function test_empty_tooltip_renders_empty_paragraph(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'notes',
            'tooltip' => null,
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::textarea-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Should still have the paragraph element but empty
        $view->assertSee('<p class="form-text"></p>', false);
    }

    /**
     * Test placeholder attribute with example value.
     */
    public function test_placeholder_uses_example_value(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'description',
            'example' => 'Example product description here...',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::textarea-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('placeholder="Example product description here..."', false);
    }

    /**
     * Test empty placeholder when example is null.
     */
    public function test_empty_placeholder_when_no_example(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'notes',
            'example' => null,
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::textarea-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('placeholder=""', false);
    }

    /**
     * Test that custom class string is applied to textarea.
     */
    public function test_custom_class_string_applied(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'content',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::textarea-form-input :params="$field" class-str-default="custom-textarea form-control-lg" />',
            ['field' => $field]
        );

        $view->assertSee('class="custom-textarea form-control-lg col-xs-2"', false);
    }

    /**
     * Test that Maps To text shows field_name in code tags.
     */
    public function test_maps_to_text_shows_field_name(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'product_long_description',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::textarea-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('Maps To: <code>product_long_description</code>', false);
    }

    /**
     * Test field with special characters in field_name.
     */
    public function test_field_name_with_special_characters(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'description-with-dashes',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::textarea-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('name="description-with-dashes"', false);
        $view->assertSee('id="description-with-dashes-id"', false);
        $view->assertSee('Maps To: <code>description-with-dashes</code>', false);
    }

    /**
     * Test component with all fields populated.
     */
    public function test_component_with_all_fields_populated(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'product_description',
            'display_name' => 'Product Description (HTML)',
            'tooltip' => 'Enter a detailed <strong>HTML</strong> description',
            'example' => 'This product is amazing...',
            'required' => true,
        ]);

        $view = $this->blade(
            '<x-channel-lister::textarea-form-input :params="$field" class-str-default="form-control textarea-input" />',
            ['field' => $field]
        );

        // Check all elements are present and correct
        $view->assertSee('form-group required', false);
        $view->assertSee('Product Description (HTML)');
        $view->assertSee('name="product_description"', false);
        $view->assertSee('id="product_description-id"', false);
        $view->assertSee('class="form-control textarea-input col-xs-2"', false);
        $view->assertSee('placeholder="This product is amazing..."', false);
        $view->assertSee('required', false);
        $view->assertSee('Enter a detailed <strong>HTML</strong> description', false);
        $view->assertSee('Maps To: <code>product_description</code>', false);
    }

    /**
     * Test component with minimal fields (all optional fields null/empty).
     */
    public function test_component_with_minimal_fields(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'notes',
            'display_name' => null,
            'tooltip' => null,
            'example' => null,
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::textarea-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Check minimal rendering
        $view->assertSee('form-group', false);
        $view->assertDontSee('form-group required', false);
        $view->assertSee('notes');
        $view->assertSee('name="notes"', false);
        $view->assertSee('id="notes-id"', false);
        $view->assertSee('placeholder=""', false);
        $view->assertSee('<p class="form-text"></p>', false);
        $view->assertSee('Maps To: <code>notes</code>', false);
    }

    /**
     * Test the component class directly.
     */
    public function test_component_class_data_preparation(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'test_textarea',
            'display_name' => 'Test Textarea',
            'tooltip' => 'Test tooltip',
            'example' => 'Test example content...',
            'required' => true,
        ]);

        $component = new TextareaFormInput($field, 'form-control');
        $view = $component->render();
        $data = $view->getData();

        // Verify all data is properly prepared
        $this->assertEquals($field, $data['params']);
        $this->assertEquals('test_textarea', $data['element_name']);
        $this->assertEquals('required', $data['required']);
        $this->assertEquals('Test Textarea', $data['label_text']);
        $this->assertEquals('test_textarea-id', $data['id']);
        $this->assertEquals('Test tooltip', $data['tooltip']);
        $this->assertEquals('Test example content...', $data['placeholder']);
        $this->assertEquals('Maps To: <code>test_textarea</code>', $data['maps_to_text']);
    }

    /**
     * Test component with empty string vs null values.
     */
    public function test_empty_string_vs_null_handling(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'content',
            'display_name' => '',  // Empty string
            'tooltip' => '',       // Empty string
            'example' => '',       // Empty string
            'required' => false,
        ]);

        $component = new TextareaFormInput($field, 'form-control');
        $view = $component->render();
        $data = $view->getData();

        // Empty display_name should return formatted field_name due to model accessor
        $this->assertEquals('Content', $data['label_text']);

        // Empty strings should be preserved
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
            'field_name' => 'description',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::textarea-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Check Bootstrap classes
        $view->assertSee('form-group', false);
        $view->assertSee('col-form-label', false);
        $view->assertSee('form-text', false);
        $view->assertSee('form-control', false);
        $view->assertSee('col-xs-2', false);
    }

    /**
     * Test XSS prevention in user-provided content.
     */
    public function test_xss_prevention_in_display_name(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'description',
            'display_name' => '<script>alert("XSS")</script>Description',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::textarea-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // The script tag should be escaped in the label
        $view->assertSee('&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;Description', false);
        $view->assertDontSee('<script>alert("XSS")</script>', false);
    }

    /**
     * Test that tooltip HTML is intentionally not escaped.
     */
    public function test_tooltip_html_intentionally_not_escaped(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'description',
            'tooltip' => 'Use <a href="/help">this guide</a> for formatting',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::textarea-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Tooltip should not be escaped (using {!! !!})
        $view->assertSee('Use <a href="/help">this guide</a> for formatting', false);
    }

    /**
     * Test textarea attributes.
     */
    public function test_textarea_attributes(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'content',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::textarea-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Check that textarea has proper opening and closing tags
        $view->assertSee('<textarea', false);
        $view->assertSee('</textarea>', false);
    }

    /**
     * Test id generation with various field names.
     */
    public function test_id_generation(): void
    {
        $testCases = [
            'simple_description' => 'simple_description-id',
            'content' => 'content-id',
            'product_detailed_description' => 'product_detailed_description-id',
            'notes-with-dashes' => 'notes-with-dashes-id',
        ];

        foreach ($testCases as $fieldName => $expectedId) {
            $field = ChannelListerField::factory()->create([
                'field_name' => $fieldName,
                'required' => false,
            ]);

            $view = $this->blade(
                '<x-channel-lister::textarea-form-input :params="$field" class-str-default="form-control" />',
                ['field' => $field]
            );

            $view->assertSee("id=\"{$expectedId}\"", false);
            $view->assertSee("for=\"{$expectedId}\"", false);
        }
    }
}
