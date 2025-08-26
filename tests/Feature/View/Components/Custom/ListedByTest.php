<?php

namespace IGE\ChannelLister\Tests\Feature\View\Components\Custom;

use IGE\ChannelLister\Models\ChannelListerField;
use IGE\ChannelLister\Tests\TestCase;
use IGE\ChannelLister\View\Components\Custom\ListedBy;

class ListedByTest extends TestCase
{
    /**
     * Test that component renders basic listed by correctly.
     */
    public function test_renders_basic_listed_by(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'listed_by',
            'display_name' => 'Listed By',
            'tooltip' => 'User who listed the item',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.listed-by :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('Listed By');
        $view->assertSee('Maps To: <code>listed_by</code>', false);
    }

    /**
     * Test the component class directly.
     */
    public function test_component_class_data_preparation(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'test_listed_by',
            'display_name' => 'Test Listed By',
            'tooltip' => 'Test tooltip',
            'required' => true,
        ]);

        $component = new ListedBy($field, 'form-control');
        $view = $component->render();
        $data = $view->getData();

        $this->assertEquals('test_listed_by', $data['element_name']);
        $this->assertEquals('required', $data['required']);
        $this->assertEquals('Test Listed By', $data['label_text']);
        $this->assertEquals('test_listed_by-id', $data['id']);
        $this->assertEquals('Test tooltip', $data['tooltip']);
        $this->assertEquals('Maps To: <code>test_listed_by</code>', $data['maps_to_text']);
    }

    /**
     * Test required field behavior.
     */
    public function test_required_field_behavior(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'required_listed_by',
            'display_name' => 'Required Listed By Field',
            'required' => true,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.listed-by :params="$field" class-str-default="form-control" />',
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
            'field_name' => 'optional_listed_by',
            'display_name' => 'Optional Listed By Field',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.listed-by :params="$field" class-str-default="form-control" />',
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
            'field_name' => 'custom_class_listed_by',
            'display_name' => 'Custom Class Listed By',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.listed-by :params="$field" class-str-default="custom-listed-by-class" />',
            ['field' => $field]
        );

        $view->assertSee('class="custom-listed-by-class"', false);
    }

    /**
     * Test tooltip rendering with HTML.
     */
    public function test_tooltip_rendering(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'tooltip_listed_by',
            'display_name' => 'Tooltip Listed By',
            'tooltip' => 'This shows who <strong>originally listed</strong> the item',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.listed-by :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('This shows who <strong>originally listed</strong> the item', false);
    }

    /**
     * Test empty tooltip handling.
     */
    public function test_empty_tooltip_handling(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'no_tooltip_listed_by',
            'display_name' => 'No Tooltip Listed By',
            'tooltip' => null,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.listed-by :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Component renders successfully even with null tooltip
        $view->assertSee('No Tooltip Listed By');
        $view->assertSee('form-group', false);
    }

    /**
     * Test field name fallback when display name is empty.
     */
    public function test_field_name_fallback(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'fallback_listed_by_field',
            'display_name' => '',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.listed-by :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Should use formatted field name as display name
        $view->assertSee('Fallback Listed By Field');
    }

    /**
     * Test maps to text generation.
     */
    public function test_maps_to_text_generation(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'mapping_listed_by_test',
            'display_name' => 'Mapping Listed By Test',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.listed-by :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('Maps To: <code>mapping_listed_by_test</code>', false);
    }

    /**
     * Test XSS prevention in display name.
     */
    public function test_xss_prevention_in_display_name(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'xss_listed_by_test',
            'display_name' => '<script>alert("xss")</script>Malicious Listed By',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.listed-by :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Display name should be escaped
        $view->assertSee('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;Malicious Listed By', false);
        $view->assertDontSee('<script>alert("xss")</script>', false);
    }

    /**
     * Test ID generation.
     */
    public function test_id_generation(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'id_listed_by_test',
            'display_name' => 'ID Listed By Test',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.listed-by :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('id="id_listed_by_test-id"', false);
        $view->assertSee('for="id_listed_by_test-id"', false);
    }

    /**
     * Test Bootstrap classes are present.
     */
    public function test_bootstrap_classes_present(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'bootstrap_listed_by_test',
            'display_name' => 'Bootstrap Listed By Test',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.listed-by :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('form-group', false);
        $view->assertSee('col-form-label', false);
        $view->assertSee('form-control', false);
    }

    /**
     * Test input type.
     */
    public function test_input_type(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'input_type_listed_by',
            'display_name' => 'Input Type Listed By',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.listed-by :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('type="text"', false);
    }

    /**
     * Test example/placeholder rendering.
     */
    public function test_example_placeholder_rendering(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'placeholder_listed_by',
            'display_name' => 'Placeholder Listed By',
            'example' => 'john.doe@example.com',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.listed-by :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('placeholder="john.doe@example.com"', false);
    }

    /**
     * Test readonly attribute is present.
     */
    public function test_readonly_attribute(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'readonly_listed_by',
            'display_name' => 'Readonly Listed By',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.listed-by :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // ListedBy component should be readonly since it shows who listed the item
        $view->assertSee('readonly', false);
    }

    /**
     * Test component with various field scenarios.
     */
    public function test_various_field_scenarios(): void
    {
        // Test with minimal data
        $minimalField = ChannelListerField::factory()->create([
            'field_name' => 'minimal_listed_by',
            'display_name' => null,
            'tooltip' => null,
            'example' => null,
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.listed-by :params="$field" class-str-default="form-control" />',
            ['field' => $minimalField]
        );

        $view->assertSee('Minimal Listed By'); // Should fallback to formatted field name
        $view->assertSee('form-group', false);
    }
}
