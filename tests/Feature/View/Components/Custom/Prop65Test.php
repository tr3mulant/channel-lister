<?php

namespace IGE\ChannelLister\Tests\Feature\View\Components\Custom;

use IGE\ChannelLister\Models\ChannelListerField;
use IGE\ChannelLister\Tests\TestCase;
use IGE\ChannelLister\View\Components\Custom\Prop65;

class Prop65Test extends TestCase
{
    /**
     * Test that component renders basic Prop65 correctly.
     */
    public function test_renders_basic_prop65(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'prop65_warning',
            'display_name' => 'Prop 65 Warning',
            'tooltip' => 'California Proposition 65 warning',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.prop-65 :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('Prop 65 Warning');
        $view->assertSee('Maps To: <code>prop65_warning</code>', false);
    }

    /**
     * Test the component class directly.
     */
    public function test_component_class_data_preparation(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'test_prop65',
            'display_name' => 'Test Prop65',
            'tooltip' => 'Test tooltip',
            'required' => true,
        ]);

        $component = new Prop65($field);
        $view = $component->render();
        $data = $view->getData();

        // Prop65 is a specialized component that provides different data structure
        $this->assertEquals('test_prop65-container-id', $data['container_id']);
        $this->assertInstanceOf(\IGE\ChannelLister\Models\ChannelListerField::class, $data['prop65_warning']);
        $this->assertInstanceOf(\IGE\ChannelLister\Models\ChannelListerField::class, $data['prop65_chem_base']);

        // Check the warning field details
        $this->assertEquals('prop65_warn_type', $data['prop65_warning']->field_name);
        $this->assertEquals('Prop 65 Warning Type', $data['prop65_warning']->display_name);
    }

    /**
     * Test required field behavior.
     */
    public function test_required_field_behavior(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'required_prop65',
            'display_name' => 'Required Prop65 Field',
            'required' => true,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.prop-65 :params="$field" class-str-default="form-control" />',
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
            'field_name' => 'optional_prop65',
            'display_name' => 'Optional Prop65 Field',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.prop-65 :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Prop65 warning type is always required, so this test checks the main component
        $view->assertSee('form-group', false);
    }

    /**
     * Test tooltip rendering with HTML.
     */
    public function test_tooltip_rendering(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'tooltip_prop65',
            'display_name' => 'Tooltip Prop65',
            'tooltip' => 'California <strong>Proposition 65</strong> warning information',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.prop-65 :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('California <strong>Proposition 65</strong> warning information', false);
    }

    /**
     * Test field name fallback when display name is empty.
     */
    public function test_field_name_fallback(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'fallback_prop65_field',
            'display_name' => '',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.prop-65 :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Should use formatted field name as display name
        $view->assertSee('Fallback Prop65 Field');
    }

    /**
     * Test maps to text generation.
     */
    public function test_maps_to_text_generation(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'mapping_prop65_test',
            'display_name' => 'Mapping Prop65 Test',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.prop-65 :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('Maps To: <code>mapping_prop65_test</code>', false);
    }

    /**
     * Test XSS prevention in display name.
     */
    public function test_xss_prevention_in_display_name(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'xss_prop65_test',
            'display_name' => '<script>alert("xss")</script>Malicious Prop65',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.prop-65 :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Display name should be escaped
        $view->assertSee('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;Malicious Prop65', false);
        $view->assertDontSee('<script>alert("xss")</script>', false);
    }

    /**
     * Test container ID generation.
     */
    public function test_container_id_generation(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'id_prop65_test',
            'display_name' => 'ID Prop65 Test',
        ]);

        $component = new Prop65($field);
        $view = $component->render();
        $data = $view->getData();

        $this->assertEquals('id_prop65_test-container-id', $data['container_id']);
    }

    /**
     * Test Bootstrap classes and structure.
     */
    public function test_bootstrap_classes_present(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'bootstrap_prop65_test',
            'display_name' => 'Bootstrap Prop65 Test',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.prop-65 :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('form-group', false);
        $view->assertSee('col-form-label', false);
    }

    /**
     * Test warning type field generation.
     */
    public function test_warning_type_field_generation(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'warning_prop65',
            'display_name' => 'Warning Prop65',
        ]);

        $component = new Prop65($field);
        $view = $component->render();
        $data = $view->getData();

        // Verify warning field is generated correctly
        $this->assertEquals('prop65_warn_type', $data['prop65_warning']->field_name);
        $this->assertEquals('Prop 65 Warning Type', $data['prop65_warning']->display_name);
        $this->assertTrue($data['prop65_warning']->required);
    }

    /**
     * Test chemical base field generation.
     */
    public function test_chemical_base_field_generation(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'chem_prop65',
            'display_name' => 'Chemical Prop65',
        ]);

        $component = new Prop65($field);
        $view = $component->render();
        $data = $view->getData();

        // Verify chemical base field is generated correctly
        $this->assertEquals('prop65_chem_name', $data['prop65_chem_base']->field_name);
        $this->assertEquals('Prop 65 Chemical Name', $data['prop65_chem_base']->display_name);
        $this->assertFalse($data['prop65_chem_base']->required);
    }

    /**
     * Test select elements are rendered.
     */
    public function test_select_elements_rendered(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'select_prop65',
            'display_name' => 'Select Prop65',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.prop-65 :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Should render select elements for warning type and chemical base
        $view->assertSee('select', false);
        $view->assertSee('name="prop65_warn_type"', false);
        $view->assertSee('name="prop65_chem_name"', false);
    }

    /**
     * Test Prop65 specific warning options.
     */
    public function test_prop65_warning_options(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'options_prop65',
            'display_name' => 'Options Prop65',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.prop-65 :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Prop65 uses dynamic options, so just verify select structure
        $view->assertSee('select', false);
        $view->assertSee('Prop 65 Warning Type');
        $view->assertSee('Prop 65 Chemical Name');
    }

    /**
     * Test empty tooltip handling.
     */
    public function test_empty_tooltip_handling(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'no_tooltip_prop65',
            'display_name' => 'No Tooltip Prop65',
            'tooltip' => null,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.prop-65 :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Component renders successfully even with null tooltip
        $view->assertSee('No Tooltip Prop65');
        $view->assertSee('form-group', false);
    }

    /**
     * Test component with various field scenarios.
     */
    public function test_various_field_scenarios(): void
    {
        // Test with minimal data
        $minimalField = ChannelListerField::factory()->create([
            'field_name' => 'minimal_prop65',
            'display_name' => null,
            'tooltip' => null,
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.prop-65 :params="$field" class-str-default="form-control" />',
            ['field' => $minimalField]
        );

        $view->assertSee('Minimal Prop65'); // Should fallback to formatted field name
        $view->assertSee('form-group', false);
    }

    /**
     * Test specialized Prop65 functionality.
     */
    public function test_specialized_prop65_functionality(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'specialized_prop65',
            'display_name' => 'Specialized Prop65',
        ]);

        $component = new Prop65($field);
        $view = $component->render();
        $data = $view->getData();

        // Verify Prop65 creates two specialized fields
        $this->assertCount(2, array_filter([
            $data['prop65_warning'] ?? null,
            $data['prop65_chem_base'] ?? null,
        ]));

        // Verify container ID is unique
        $this->assertStringContainsString('specialized_prop65-container-id', $data['container_id']);
    }
}
