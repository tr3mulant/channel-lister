<?php

namespace IGE\ChannelLister\Tests\Feature\View\Components\Custom;

use IGE\ChannelLister\Models\ChannelListerField;
use IGE\ChannelLister\Tests\TestCase;
use IGE\ChannelLister\View\Components\Custom\Label;

class LabelTest extends TestCase
{
    /**
     * Test that component renders basic label correctly.
     */
    public function test_renders_basic_label(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'label_field',
            'display_name' => 'Custom Label',
            'tooltip' => 'Label tooltip',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.label :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Check basic structure
        $view->assertSee('form-group', false);

        // Check field-specific attributes
        $view->assertSee('Custom Label');
        $view->assertSee('label_field-id', false);

        // Check tooltip and maps to text
        $view->assertSee('Label tooltip');
        $view->assertSee('Maps To: <code>label_field</code>', false);
    }

    /**
     * Test the component class directly.
     */
    public function test_component_class_data_preparation(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'test_label',
            'display_name' => 'Test Label',
            'tooltip' => 'Test tooltip',
            'required' => true,
        ]);

        $component = new Label($field, 'form-control');
        $view = $component->render();
        $data = $view->getData();

        $this->assertEquals('test_label', $data['element_name']);
        $this->assertEquals('required', $data['required']);
        $this->assertEquals('Test Label', $data['label_text']);
        $this->assertEquals('test_label-id', $data['id']);
        $this->assertEquals('Test tooltip', $data['tooltip']);
        $this->assertEquals('Maps To: <code>test_label</code>', $data['maps_to_text']);
    }

    /**
     * Test required field behavior.
     */
    public function test_required_field_behavior(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'required_label',
            'display_name' => 'Required Label Field',
            'required' => true,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.label :params="$field" class-str-default="form-control" />',
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
            'field_name' => 'optional_label',
            'display_name' => 'Optional Label Field',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.label :params="$field" class-str-default="form-control" />',
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
            'field_name' => 'class_test_label',
            'display_name' => 'Class Test Label',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.label :params="$field" class-str-default="custom-label-class" />',
            ['field' => $field]
        );

        $view->assertSee('class="custom-label-class"', false);
    }

    /**
     * Test tooltip rendering.
     */
    public function test_tooltip_rendering(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'tooltip_label',
            'display_name' => 'Tooltip Label',
            'tooltip' => 'This is a <strong>label</strong> tooltip with HTML',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.label :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('This is a <strong>label</strong> tooltip with HTML', false);
    }

    /**
     * Test empty tooltip handling.
     */
    public function test_empty_tooltip_handling(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'no_tooltip_label',
            'display_name' => 'No Tooltip Label',
            'tooltip' => null,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.label :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Label component renders successfully even with null tooltip
        $view->assertSee('No Tooltip Label');
        $view->assertSee('form-group', false);
    }

    /**
     * Test field name fallback when display name is empty.
     */
    public function test_field_name_fallback(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'fallback_label_field',
            'display_name' => '',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.label :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Should use formatted field name as display name
        $view->assertSee('Fallback Label Field');
    }

    /**
     * Test maps to text generation.
     */
    public function test_maps_to_text_generation(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'mapping_label_test',
            'display_name' => 'Mapping Label Test',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.label :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('Maps To: <code>mapping_label_test</code>', false);
    }

    /**
     * Test XSS prevention in display name.
     */
    public function test_xss_prevention_in_display_name(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'xss_label_test',
            'display_name' => '<script>alert("xss")</script>Malicious Label',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.label :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Display name should be escaped
        $view->assertSee('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;Malicious Label', false);
        $view->assertDontSee('<script>alert("xss")</script>', false);
    }

    /**
     * Test ID generation.
     */
    public function test_id_generation(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'id_label_test',
            'display_name' => 'ID Label Test',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.label :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('id="id_label_test-id"', false);
        $view->assertSee('for="id_label_test-id"', false);
    }

    /**
     * Test Bootstrap classes are present.
     */
    public function test_bootstrap_classes_present(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'bootstrap_label_test',
            'display_name' => 'Bootstrap Label Test',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.label :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('form-group', false);
        $view->assertSee('col-form-label', false);
        $view->assertSee('form-control', false);
    }

    /**
     * Test label input type.
     */
    public function test_label_input_type(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'input_type_label',
            'display_name' => 'Input Type Label',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.label :params="$field" class-str-default="form-control" />',
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
            'field_name' => 'placeholder_label',
            'display_name' => 'Placeholder Label',
            'example' => 'Enter label text here',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.label :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('placeholder="Enter label text here"', false);
    }
}
