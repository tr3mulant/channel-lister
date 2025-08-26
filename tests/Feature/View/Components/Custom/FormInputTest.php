<?php

namespace IGE\ChannelLister\Tests\Feature\View\Components\Custom;

use IGE\ChannelLister\Models\ChannelListerField;
use IGE\ChannelLister\Tests\TestCase;
use IGE\ChannelLister\View\Components\Custom\FormInput;

class FormInputTest extends TestCase
{
    /**
     * Test that component renders basic form input correctly.
     */
    public function test_renders_basic_form_input(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'UPC',
            'display_name' => 'UPC Code',
            'tooltip' => 'UPC field tooltip',
            'example' => '123456789012',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Check that it renders the UPC component
        $view->assertSee('form-group', false);
        $view->assertSee('type="text"', false);

        // Check field-specific attributes for UPC component
        $view->assertSee('name="UPC"', false);
        $view->assertSee('UPC Code');

        // Check tooltip and maps to text
        $view->assertSee('UPC field tooltip');
        $view->assertSee('Maps To: <code>UPC</code>', false);
    }

    /**
     * Test the component class directly passes data to view.
     */
    public function test_component_class_data_preparation(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'UPC',  // Use a recognized field name
            'display_name' => 'Test UPC',
            'tooltip' => 'Test tooltip',
            'example' => 'Test example',
            'required' => true,
        ]);

        $component = new FormInput($field, 'form-control');
        $view = $component->render();
        $data = $view->getData();

        // FormInput is a dispatcher component, so it passes params and classStrDefault to the view
        $this->assertEquals($field, $data['params']);
        $this->assertEquals('form-control', $data['classStrDefault']);
    }

    /**
     * Test required field behavior.
     */
    public function test_required_field_behavior(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'UPC',  // Use a field name that routes to a specific component
            'display_name' => 'Required UPC Field',
            'required' => true,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertSee('required', false);
        $view->assertSee('form-group container-fluid required', false);
    }

    /**
     * Test optional field behavior with UPC component.
     */
    public function test_optional_field_behavior(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'amazon_upc',  // Routes to UPC component
            'display_name' => 'Optional Amazon UPC',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        $view->assertDontSee('required', false);
        $view->assertSee('form-group ', false);
        $view->assertDontSee('form-group required', false);
    }

    /**
     * Test routing to Labels component.
     */
    public function test_labels_component_routing(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'Labels',
            'display_name' => 'Product Labels',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Should render the Labels component
        $view->assertSee('form-group', false);
        $view->assertSee('Product Labels');
    }

    /**
     * Test routing to cost shipping component.
     */
    public function test_cost_shipping_component_routing(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'Cost Shipping',
            'display_name' => 'Cost Shipping',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.form-input :params="$field" class-str-default="custom-shipping-class" />',
            ['field' => $field]
        );

        // Should render the cost-shipping component
        $view->assertSee('form-group', false);
        $view->assertSee('Cost Shipping');
    }

    /**
     * Test routing to calculated shipping service component.
     */
    public function test_calculated_shipping_service_routing(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'calculated_shipping_service',
            'display_name' => 'Calculated Shipping Service',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Should render the calculated-shipping-service component
        $view->assertSee('form-group', false);
        $view->assertSee('Calculated Shipping Service');
    }

    /**
     * Test routing to listed by component.
     */
    public function test_listed_by_component_routing(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'listed_by',
            'display_name' => 'Listed By',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Should render the listed-by component
        $view->assertSee('form-group', false);
        $view->assertSee('Listed By');
    }

    /**
     * Test routing to amazon special refinements component.
     */
    public function test_amazon_special_refinements_routing(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'special_features_amazon',
            'display_name' => 'Amazon Special Features',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Should render the amazon-special-refinements component
        $view->assertSee('form-group', false);
        $view->assertSee('Amazon Special Features');
    }

    /**
     * Test default case for unrecognized field names.
     */
    public function test_unrecognized_field_name_error(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'unknown_field_name',
            'display_name' => 'Unknown Field',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Should show error message for unrecognized field
        $view->assertSee('alert alert-danger', false);
        $view->assertSee('Unable to build input field for unknown_field_name');
    }

    /**
     * Test routing to sku bundle component.
     */
    public function test_sku_bundle_routing(): void
    {
        $field = ChannelListerField::factory()->create([
            'field_name' => 'Bundle Components',
            'display_name' => 'Bundle Components',
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.form-input :params="$field" class-str-default="form-control" />',
            ['field' => $field]
        );

        // Should render the sku-bundle component
        $view->assertSee('form-group', false);
        $view->assertSee('Bundle Components');
    }
}
