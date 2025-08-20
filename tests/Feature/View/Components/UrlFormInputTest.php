<?php

namespace IGE\ChannelLister\Tests\Feature\View\Components;

use IGE\ChannelLister\Models\ChannelListerField;
use IGE\ChannelLister\Tests\TestCase;
use IGE\ChannelLister\View\Components\UrlFormInput;

class UrlFormInputTest extends TestCase
{
    /**
     * Test that component renders basic URL input correctly.
     */
    public function test_renders_basic_url_input(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'website_url',
            'display_name' => 'Website URL',
            'tooltip' => 'Enter the website URL',
            'example' => 'https://example.com',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::url-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Check basic structure
        $view->assertSee('form-group', false);
        $view->assertSee('type="url"', false);

        // Check field-specific attributes
        $view->assertSee('name="website_url"', false);
        $view->assertSee('id="website_url-id"', false);
        $view->assertSee('Website URL');
        $view->assertSee('placeholder="https://example.com"', false);

        // Check tooltip and maps to text
        $view->assertSee('Enter the website URL');
        $view->assertSee('Maps To: <code>website_url</code>', false);

        // Check iframe preview element
        $view->assertSee('iframe-wrap', false);
        $view->assertSee('url-preview d-none', false);
    }

    /**
     * Test that required fields are marked as required.
     */
    public function test_required_field_has_required_attribute(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'product_url',
            'display_name' => 'Product URL',
            'required' => true,
        ]);

        $view = $this->blade(
            '<x-channel-lister::url-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Should have required attribute on input
        $view->assertSee('required', false);
        // Should have required class on form-group
        $view->assertSee('form-group required', false);
        $view->assertSee('name="product_url"', false);
        $view->assertSee('id="product_url-id"', false);
    }

    /**
     * Test that optional fields don't have required attribute.
     */
    public function test_optional_field_not_required(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'optional_url',
            'display_name' => 'Optional URL',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::url-form-input :params="$field" class-str-default="form-control" />',
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
            'field_name' => 'custom_url',
            'display_name' => 'Custom URL',
            'input_type_aux' => 'https://.*\.example\.com/.*',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::url-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('pattern="https://.*\.example\.com/.*"', false);
    }

    /**
     * Test that field_name is used as label when display_name is empty.
     */
    public function test_uses_field_name_when_display_name_empty(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'product_link',
            'display_name' => null,
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::url-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Should show field_name as label
        $view->assertSee('product_link');
        $view->assertSee('for="product_link-id"', false);
    }

    /**
     * Test that display_name is preferred over field_name for label.
     */
    public function test_uses_display_name_when_provided(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'url',
            'display_name' => 'Product Website',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::url-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Should use display_name as label
        $view->assertSee('Product Website');
        // The word 'url' appears in the HTML attributes, so this assertion is not valid
        // $view->assertDontSee('url', false);

        // But field_name should still be used for name and id
        $view->assertSee('name="url"', false);
        $view->assertSee('id="url-id"', false);
    }

    /**
     * Test HTML in tooltip is rendered correctly.
     */
    public function test_html_in_tooltip_is_rendered(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'website',
            'tooltip' => 'Enter a <strong>valid</strong> URL starting with <em>https://</em>',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::url-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // HTML should be rendered (not escaped) due to {!! !!}
        $view->assertSee('Enter a <strong>valid</strong> URL starting with <em>https://</em>', false);
    }

    /**
     * Test empty tooltip renders empty paragraph.
     */
    public function test_empty_tooltip_renders_empty_paragraph(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'link',
            'tooltip' => null,
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::url-form-input :params="$field" class-str-default="form-control" />',
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
            'field_name' => 'website',
            'example' => 'https://www.mystore.com',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::url-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('placeholder="https://www.mystore.com"', false);
    }

    /**
     * Test empty placeholder when example is null.
     */
    public function test_empty_placeholder_when_no_example(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'url',
            'example' => null,
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::url-form-input :params="$field" class-str-default="form-control" />',
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
            'field_name' => 'website',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::url-form-input :params="$field" class-str-default="custom-url form-control-lg" />',
            ['field' => $field]
        );

        $view->assertSee('class="custom-url form-control-lg"', false);
    }

    /**
     * Test that Maps To text shows field_name in code tags.
     */
    public function test_maps_to_text_shows_field_name(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'product_website_url',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::url-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('Maps To: <code>product_website_url</code>', false);
    }

    /**
     * Test iframe preview structure is present.
     */
    public function test_iframe_preview_structure(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'website',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::url-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Check iframe structure
        $view->assertSee('<div class="iframe-wrap">', false);
        $view->assertSee('<iframe class="url-preview d-none" src=""></iframe>', false);
        $view->assertSee('</div>', false);
    }

    /**
     * Test field with special characters in field_name.
     */
    public function test_field_name_with_special_characters(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'website-url-main',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::url-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('name="website-url-main"', false);
        $view->assertSee('id="website-url-main-id"', false);
        $view->assertSee('Maps To: <code>website-url-main</code>', false);
    }

    /**
     * Test component with all fields populated.
     */
    public function test_component_with_all_fields_populated(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'product_url',
            'display_name' => 'Product Website URL',
            'tooltip' => 'Enter the <strong>official</strong> product URL',
            'example' => 'https://www.manufacturer.com/product/12345',
            'input_type_aux' => 'https://.*',
            'required' => true,
        ]);

        $view = $this->blade(
            '<x-channel-lister::url-form-input :params="$field" class-str-default="form-control url-input" />',
            ['field' => $field]
        );

        // Check all elements are present and correct
        $view->assertSee('form-group required', false);
        $view->assertSee('Product Website URL');
        $view->assertSee('name="product_url"', false);
        $view->assertSee('id="product_url-id"', false);
        $view->assertSee('class="form-control url-input"', false);
        $view->assertSee('placeholder="https://www.manufacturer.com/product/12345"', false);
        $view->assertSee('pattern="https://.*"', false);
        $view->assertSee('required', false);
        $view->assertSee('Enter the <strong>official</strong> product URL', false);
        $view->assertSee('Maps To: <code>product_url</code>', false);
        $view->assertSee('type="url"', false);
        $view->assertSee('iframe-wrap', false);
        $view->assertSee('url-preview d-none', false);
    }

    /**
     * Test component with minimal fields (all optional fields null/empty).
     */
    public function test_component_with_minimal_fields(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'url',
            'display_name' => null,
            'tooltip' => null,
            'example' => null,
            'input_type_aux' => null,
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::url-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Check minimal rendering
        $view->assertSee('form-group', false);
        $view->assertDontSee('form-group required', false);
        $view->assertSee('url');
        $view->assertSee('name="url"', false);
        $view->assertSee('id="url-id"', false);
        $view->assertSee('<p class="form-text text-secondary"></p>', false);
        $view->assertSee('Maps To: <code>url</code>', false);
        $view->assertDontSee('pattern=', false);
        $view->assertDontSee('placeholder=""', false);
        $view->assertSee('iframe-wrap', false);
    }

    /**
     * Test the component class directly.
     */
    public function test_component_class_data_preparation(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'test_url',
            'display_name' => 'Test URL',
            'tooltip' => 'Test tooltip',
            'example' => 'https://test.com',
            'input_type_aux' => 'https://.*\.com/.*',
            'required' => true,
        ]);

        $component = new UrlFormInput($field, 'form-control');
        $view = $component->render();
        $data = $view->getData();

        // Verify all data is properly prepared
        $this->assertEquals($field, $data['params']);
        $this->assertEquals('test_url', $data['element_name']);
        $this->assertEquals('required', $data['required']);
        $this->assertEquals('Test URL', $data['label_text']);
        $this->assertEquals('test_url-id', $data['id']);
        $this->assertEquals('Test tooltip', $data['tooltip']);
        $this->assertEquals('https://test.com', $data['placeholder']);
        $this->assertEquals('https://.*\.com/.*', $data['pattern']);
        $this->assertEquals('Maps To: <code>test_url</code>', $data['maps_to_text']);
    }

    /**
     * Test component with empty string vs null values.
     */
    public function test_empty_string_vs_null_handling(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'website',
            'display_name' => '',  // Empty string
            'tooltip' => '',       // Empty string
            'example' => '',       // Empty string
            'input_type_aux' => '', // Empty string
            'required' => false,
        ]);

        $component = new UrlFormInput($field, 'form-control');
        $view = $component->render();
        $data = $view->getData();

        // Empty display_name should return formatted field_name due to model accessor
        $this->assertEquals('Website', $data['label_text']);

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
            'field_name' => 'website',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::url-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Check Bootstrap classes
        $view->assertSee('form-group', false);
        $view->assertSee('col-form-label', false);
        $view->assertSee('form-text', false);
        $view->assertSee('form-control', false);
        $view->assertSee('iframe-wrap', false);
    }

    /**
     * Test XSS prevention in user-provided content.
     */
    public function test_xss_prevention_in_display_name(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'website',
            'display_name' => '<script>alert("XSS")</script>Website',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::url-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // The script tag should be escaped in the label
        $view->assertSee('&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;Website', false);
        $view->assertDontSee('<script>alert(&quot;XSS&quot;)</script>', false);
    }

    /**
     * Test that tooltip HTML is intentionally not escaped.
     */
    public function test_tooltip_html_intentionally_not_escaped(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'website',
            'tooltip' => 'Visit <a href="/help">help page</a> for URL format',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::url-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Tooltip should not be escaped (using {!! !!})
        $view->assertSee('Visit <a href="/help">help page</a> for URL format', false);
    }

    /**
     * Test URL input type attribute.
     */
    public function test_url_input_type(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'website',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::url-form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Should have URL input type for browser validation
        $view->assertSee('type="url"', false);
    }

    /**
     * Test id generation with various field names.
     */
    public function test_id_generation(): void
    {
        $testCases = [
            'simple_url' => 'simple_url-id',
            'website' => 'website-id',
            'product_website_url' => 'product_website_url-id',
            'url-with-dashes' => 'url-with-dashes-id',
        ];

        foreach ($testCases as $fieldName => $expectedId) {
            $field = ChannelListerField::factory()->create([
                'field_name' => $fieldName,
                'required' => false,
            ]);

            $view = $this->blade(
                '<x-channel-lister::url-form-input :params="$field" class-str-default="form-control" />',
                ['field' => $field]
            );

            $view->assertSee("id=\"{$expectedId}\"", false);
            $view->assertSee("for=\"{$expectedId}\"", false);
        }
    }

    /**
     * Test pattern with various URL patterns.
     */
    public function test_various_url_patterns(): void
    {
        $patterns = [
            'https://.*',
            'https://.*\.com/.*',
            '.*amazon\.com.*',
            '(http|https)://.*',
        ];

        foreach ($patterns as $pattern) {
            $field = ChannelListerField::factory()->create([
                'field_name' => 'url_'.md5($pattern),
                'input_type_aux' => $pattern,
                'required' => false,
            ]);

            $view = $this->blade(
                '<x-channel-lister::url-form-input :params="$field" class-str-default="form-control" />',
                ['field' => $field]
            );

            $view->assertSee('pattern="'.$pattern.'"', false);
        }
    }
}
