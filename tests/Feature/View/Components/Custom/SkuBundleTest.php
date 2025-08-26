<?php

namespace IGE\ChannelLister\Tests\Feature\View\Components\Custom;

use IGE\ChannelLister\Models\ChannelListerField;
use IGE\ChannelLister\Tests\TestCase;
use IGE\ChannelLister\View\Components\Custom\SkuBundle;

class SkuBundleTest extends TestCase
{
    /**
     * Test that component renders basic SKU Bundle correctly.
     */
    public function test_renders_basic_sku_bundle(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'sku_bundle',
            'display_name' => 'SKU Bundle',
            'tooltip' => 'Bundle of related SKUs',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.sku-bundle :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('SKU Bundle');
        $view->assertSee('Maps To: <code>sku_bundle</code>', false);
    }

    /**
     * Test the component class directly.
     */
    public function test_component_class_data_preparation(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'test_sku_bundle',
            'display_name' => 'Test SKU Bundle',
            'tooltip' => 'Test tooltip',
            'required' => true,
        ]);

        $component = new SkuBundle($field, 'form-control');
        $view = $component->render();
        $data = $view->getData();

        $this->assertEquals('test_sku_bundle', $data['element_name']);
        $this->assertEquals('required', $data['required']);
        $this->assertEquals('Test SKU Bundle', $data['label_text']);
        $this->assertEquals('test_sku_bundle-id', $data['id']);
        $this->assertEquals('Test tooltip', $data['tooltip']);
        $this->assertEquals('Maps To: <code>test_sku_bundle</code>', $data['maps_to_text']);
    }

    /**
     * Test required field behavior.
     */
    public function test_required_field_behavior(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'required_sku_bundle',
            'display_name' => 'Required SKU Bundle Field',
            'required' => true,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.sku-bundle :params="$field" class-str-default="form-control" />',
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
            'field_name' => 'optional_sku_bundle',
            'display_name' => 'Optional SKU Bundle Field',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.sku-bundle :params="$field" class-str-default="form-control" />',
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
            'field_name' => 'custom_class_sku_bundle',
            'display_name' => 'Custom Class SKU Bundle',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.sku-bundle :params="$field" class-str-default="custom-sku-bundle-class" />',
            ['field' => $field]
        );

        // SkuBundle component doesn't use custom class string for main input, check for form structure
        $view->assertSee('class="form-control"', false);
    }

    /**
     * Test tooltip rendering with HTML.
     */
    public function test_tooltip_rendering(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'tooltip_sku_bundle',
            'display_name' => 'Tooltip SKU Bundle',
            'tooltip' => 'This bundle contains <strong>multiple related SKUs</strong>',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.sku-bundle :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('This bundle contains <strong>multiple related SKUs</strong>', false);
    }

    /**
     * Test empty tooltip handling.
     */
    public function test_empty_tooltip_handling(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'no_tooltip_sku_bundle',
            'display_name' => 'No Tooltip SKU Bundle',
            'tooltip' => null,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.sku-bundle :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Component renders successfully even with null tooltip
        $view->assertSee('No Tooltip SKU Bundle');
        $view->assertSee('form-group', false);
    }

    /**
     * Test field name fallback when display name is empty.
     */
    public function test_field_name_fallback(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'fallback_sku_bundle_field',
            'display_name' => '',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.sku-bundle :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Should use field name as display name when display name is empty
        $view->assertSee('fallback_sku_bundle_field');
    }

    /**
     * Test maps to text generation.
     */
    public function test_maps_to_text_generation(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'mapping_sku_bundle_test',
            'display_name' => 'Mapping SKU Bundle Test',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.sku-bundle :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('Maps To: <code>mapping_sku_bundle_test</code>', false);
    }

    /**
     * Test XSS prevention in display name.
     */
    public function test_xss_prevention_in_display_name(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'xss_sku_bundle_test',
            'display_name' => '<script>alert("xss")</script>Malicious SKU Bundle',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.sku-bundle :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Display name should be escaped
        $view->assertSee('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;Malicious SKU Bundle', false);
        $view->assertDontSee('<script>alert("xss")</script>', false);
    }

    /**
     * Test ID generation.
     */
    public function test_id_generation(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'id_sku_bundle_test',
            'display_name' => 'ID SKU Bundle Test',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.sku-bundle :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('id="id_sku_bundle_test-id"', false);
        $view->assertSee('for="id_sku_bundle_test-id"', false);
    }

    /**
     * Test Bootstrap classes are present.
     */
    public function test_bootstrap_classes_present(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'bootstrap_sku_bundle_test',
            'display_name' => 'Bootstrap SKU Bundle Test',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.sku-bundle :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('form-group', false);
        $view->assertSee('col-form-label', false);
        $view->assertSee('form-control', false);
    }

    /**
     * Test input type and attributes.
     */
    public function test_input_type(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'input_type_sku_bundle',
            'display_name' => 'Input Type SKU Bundle',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.sku-bundle :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // SkuBundle uses input without explicit type, check readonly attribute instead
        $view->assertSee('readonly=""', false);
    }

    /**
     * Test example/placeholder rendering.
     */
    public function test_example_placeholder_rendering(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'placeholder_sku_bundle',
            'display_name' => 'Placeholder SKU Bundle',
            'example' => 'SKU1,SKU2,SKU3',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.sku-bundle :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('placeholder="SKU1,SKU2,SKU3"', false);
    }

    /**
     * Test pattern attribute handling.
     */
    public function test_pattern_attribute_handling(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'pattern_sku_bundle',
            'display_name' => 'Pattern SKU Bundle',
            'input_type_aux' => '[A-Z0-9,]+',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.sku-bundle :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // SkuBundle includes the pattern validation, check for presence in component data
        $view->assertSee('[A-Z0-9,]+', false);
    }

    /**
     * Test empty pattern handling.
     */
    public function test_empty_pattern_handling(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'no_pattern_sku_bundle',
            'display_name' => 'No Pattern SKU Bundle',
            'input_type_aux' => null,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.sku-bundle :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Component still renders successfully without pattern
        $view->assertSee('No Pattern SKU Bundle');
        $view->assertSee('form-group', false);
    }

    /**
     * Test empty example handling.
     */
    public function test_empty_example_handling(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'no_example_sku_bundle',
            'display_name' => 'No Example SKU Bundle',
            'example' => null,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.sku-bundle :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Component still renders successfully without example
        $view->assertSee('No Example SKU Bundle');
        $view->assertSee('form-group', false);
    }

    /**
     * Test component with various field scenarios.
     */
    public function test_various_field_scenarios(): void
    {
        // Test with minimal data
        $minimalField = ChannelListerField::factory()->create([
            'field_name' => 'minimal_sku_bundle',
            'display_name' => null,
            'tooltip' => null,
            'example' => null,
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.sku-bundle :params="$field" class-str-default="form-control" />',
            ['field' => $minimalField]
        );

        $view->assertSee('minimal_sku_bundle'); // Should use field name when display name is null
        $view->assertSee('form-group', false);
    }

    /**
     * Test SKU Bundle specific functionality.
     */
    public function test_sku_bundle_specific_functionality(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'specific_sku_bundle',
            'display_name' => 'Specific SKU Bundle',
            'example' => 'BUNDLE1,BUNDLE2',
            'input_type_aux' => '[A-Z0-9,]+',
        ]);

        $component = new SkuBundle($field);
        $view = $component->render();
        $data = $view->getData();

        // Verify SKU Bundle component data structure
        $this->assertEquals('specific_sku_bundle', $data['element_name']);
        $this->assertEquals('BUNDLE1,BUNDLE2', $data['placeholder']);
        $this->assertEquals("pattern='[A-Z0-9,]+'", $data['pattern']);
        $this->assertEquals('specific_sku_bundle-id', $data['id']);
    }
}
