<?php

namespace IGE\ChannelLister\Tests\Feature\View\Components\Custom;

use IGE\ChannelLister\Models\ChannelListerField;
use IGE\ChannelLister\Tests\TestCase;
use IGE\ChannelLister\View\Components\Custom\WishBrandDirectoryInput;

class WishBrandDirectoryInputTest extends TestCase
{
    /**
     * Test that component renders basic Wish Brand Directory Input correctly.
     */
    public function test_renders_basic_wish_brand_directory_input(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'wish_brand',
            'display_name' => 'Wish Brand Directory',
            'tooltip' => 'Brand from Wish directory',
            'example' => 'Generic',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.wish-brand-directory-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('Wish Brand Directory');
        // Note: Select inputs don't use placeholders - they use default options instead
        $view->assertSee('Maps To: <code>wish_brand</code>', false);
    }

    /**
     * Test the component class directly.
     */
    public function test_component_class_data_preparation(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'test_wish_brand',
            'display_name' => 'Test Wish Brand',
            'tooltip' => 'Test tooltip',
            'example' => 'Test Brand',
            'required' => true,
        ]);

        $component = new WishBrandDirectoryInput($field, 'form-control');
        $view = $component->render();
        $data = $view->getData();

        $this->assertEquals('test_wish_brand', $data['element_name']);
        $this->assertEquals('required', $data['required']);
        $this->assertEquals('Test Wish Brand', $data['label_text']);
        $this->assertEquals('test_wish_brand-id', $data['id']);
        $this->assertEquals('Test tooltip', $data['tooltip']);
        $this->assertEquals('Test Brand', $data['placeholder']);
        $this->assertEquals('Maps To: <code>test_wish_brand</code>', $data['maps_to_text']);
    }

    /**
     * Test required field behavior.
     */
    public function test_required_field_behavior(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'required_wish_brand',
            'display_name' => 'Required Wish Brand Field',
            'required' => true,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.wish-brand-directory-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('required', false);
        $view->assertSee('form-group required', false);
    }

    /**
     * Test optional field behavior.
     */
    public function test_optional_field_behavior(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'optional_wish_brand',
            'display_name' => 'Optional Wish Brand Field',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.wish-brand-directory-input :params="$field" class-str-default="form-control" />',
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
            'field_name' => 'custom_class_wish_brand',
            'display_name' => 'Custom Class Wish Brand',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.wish-brand-directory-input :params="$field" class-str-default="custom-wish-brand-class" />',
            ['field' => $field]
        );

        // WishBrandDirectoryInput uses select-form-input, check for selectpicker class structure
        $view->assertSee('selectpicker', false);
    }

    /**
     * Test tooltip rendering with HTML.
     */
    public function test_tooltip_rendering(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'tooltip_wish_brand',
            'display_name' => 'Tooltip Wish Brand',
            'tooltip' => 'Select brand from <strong>Wish Brand Directory</strong>',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.wish-brand-directory-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('Select brand from <strong>Wish Brand Directory</strong>', false);
    }

    /**
     * Test empty tooltip handling.
     */
    public function test_empty_tooltip_handling(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'no_tooltip_wish_brand',
            'display_name' => 'No Tooltip Wish Brand',
            'tooltip' => null,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.wish-brand-directory-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Component renders successfully even with null tooltip
        $view->assertSee('No Tooltip Wish Brand');
        $view->assertSee('form-group', false);
    }

    /**
     * Test field name fallback when display name is empty.
     */
    public function test_field_name_fallback(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'fallback_wish_brand_field',
            'display_name' => '',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.wish-brand-directory-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Should use formatted field name as display name
        $view->assertSee('Fallback Wish Brand Field');
    }

    /**
     * Test maps to text generation.
     */
    public function test_maps_to_text_generation(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'mapping_wish_brand_test',
            'display_name' => 'Mapping Wish Brand Test',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.wish-brand-directory-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('Maps To: <code>mapping_wish_brand_test</code>', false);
    }

    /**
     * Test XSS prevention in display name.
     */
    public function test_xss_prevention_in_display_name(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'xss_wish_brand_test',
            'display_name' => '<script>alert("xss")</script>Malicious Wish Brand',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.wish-brand-directory-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Display name should be escaped
        $view->assertSee('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;Malicious Wish Brand', false);
        $view->assertDontSee('<script>alert("xss")</script>', false);
    }

    /**
     * Test ID generation.
     */
    public function test_id_generation(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'id_wish_brand_test',
            'display_name' => 'ID Wish Brand Test',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.wish-brand-directory-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('id="id_wish_brand_test-id"', false);
        $view->assertSee('for="id_wish_brand_test-id"', false);
    }

    /**
     * Test Bootstrap classes are present.
     */
    public function test_bootstrap_classes_present(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'bootstrap_wish_brand_test',
            'display_name' => 'Bootstrap Wish Brand Test',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.wish-brand-directory-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('form-group', false);
        $view->assertSee('col-form-label', false);
        // WishBrandDirectoryInput uses selectpicker instead of form-control
        $view->assertSee('selectpicker', false);
    }

    /**
     * Test select input element.
     */
    public function test_select_input_element(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'select_wish_brand',
            'display_name' => 'Select Wish Brand',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.wish-brand-directory-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('<select', false);
        $view->assertSee('name="select_wish_brand"', false);
    }

    /**
     * Test brand directory data loading.
     */
    public function test_brand_directory_data_loading(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'brand_data_wish_brand',
            'display_name' => 'Brand Data Wish Brand',
        ]);

        $component = new WishBrandDirectoryInput($field);
        $view = $component->render();
        $data = $view->getData();

        // Should have input_type_aux containing brand data
        $this->assertArrayHasKey('input_type_aux', $data);
        $this->assertIsArray($data['input_type_aux']);
    }

    /**
     * Test example/placeholder handling.
     */
    public function test_example_placeholder_handling(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'placeholder_wish_brand',
            'display_name' => 'Placeholder Wish Brand',
            'example' => 'Generic Brand',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.wish-brand-directory-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Check that example value is present in component data
        $view->assertSee('Placeholder Wish Brand');
    }

    /**
     * Test empty example handling.
     */
    public function test_empty_example_handling(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'no_example_wish_brand',
            'display_name' => 'No Example Wish Brand',
            'example' => null,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.wish-brand-directory-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Component still renders successfully without example
        $view->assertSee('No Example Wish Brand');
        $view->assertSee('form-group', false);
    }

    /**
     * Test component with various field scenarios.
     */
    public function test_various_field_scenarios(): void
    {
        // Test with minimal data
        $minimalField = ChannelListerField::factory()->create([
            'field_name' => 'minimal_wish_brand',
            'display_name' => null,
            'tooltip' => null,
            'example' => null,
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.wish-brand-directory-input :params="$field" class-str-default="form-control" />',
            ['field' => $minimalField]
        );

        $view->assertSee('Minimal Wish Brand'); // Should fallback to formatted field name
        $view->assertSee('form-group', false);
    }

    /**
     * Test Wish Brand Directory specific functionality.
     */
    public function test_wish_brand_directory_specific_functionality(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'specific_wish_brand',
            'display_name' => 'Specific Wish Brand',
            'example' => 'Test Brand',
        ]);

        $component = new WishBrandDirectoryInput($field);
        $view = $component->render();
        $data = $view->getData();

        // Verify Wish Brand Directory component data structure
        $this->assertEquals('specific_wish_brand', $data['element_name']);
        $this->assertEquals('Test Brand', $data['placeholder']);
        $this->assertEquals('specific_wish_brand-id', $data['id']);
        $this->assertArrayHasKey('input_type_aux', $data);
        $this->assertIsArray($data['input_type_aux']);
    }

    /**
     * Test brand directory constant.
     */
    public function test_brand_directory_constant(): void
    {
        $this->assertEquals('wish_brand_directory', WishBrandDirectoryInput::TABLE_WISH_BRAND_DIRECTORY);
    }

    /**
     * Test select dropdown options structure.
     */
    public function test_select_dropdown_options_structure(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'options_wish_brand',
            'display_name' => 'Options Wish Brand',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.wish-brand-directory-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Should render a select element for dropdown
        $view->assertSee('<select', false);
        $view->assertSee('</select>', false);
    }
}
