<?php

namespace IGE\ChannelLister\Tests\Feature\View\Components\Custom;

use IGE\ChannelLister\Models\ChannelListerField;
use IGE\ChannelLister\Tests\TestCase;
use IGE\ChannelLister\View\Components\Custom\SkuBundleComponentInputRow;

class SkuBundleComponentInputRowTest extends TestCase
{
    /**
     * Test that component renders basic SKU Bundle Component Input Row correctly.
     */
    public function test_renders_basic_sku_bundle_component_input_row(): void
    {
        ChannelListerField::factory()->create([
            'field_name' => 'sku_bundle_row',
            'display_name' => 'SKU Bundle Row',
            'tooltip' => 'Individual row in SKU bundle',
            'required' => false,
        ]);

        $view = $this->blade(
            '<x-channel-lister::custom.sku-bundle-component-input-row :is-first="false" />',
            []
        );

        // This component renders a button row, not a standard field
        $view->assertSee('Remove Row');
        $view->assertSee('btn btn-primary', false);
    }

    /**
     * Test the component class directly.
     */
    public function test_component_class_data_preparation(): void
    {
        // Test with isFirst = false (Remove button)
        $component = new SkuBundleComponentInputRow(false);
        // Test component properties for Remove button (isFirst = false)
        $this->assertEquals('', $component->id);
        $this->assertEquals('Remove Bundle Component Row', $component->title);
        $this->assertEquals('remove-row btn btn-primary p-1', $component->class);
        $this->assertEquals('Remove Row', $component->value);
        $this->assertEquals('icon-minus', $component->icon_class);

        // Test with isFirst = true (Add button)
        $addComponent = new SkuBundleComponentInputRow(true);
        $addComponent->render(); // This sets the properties

        $this->assertEquals('add-component-button', $addComponent->id);
        $this->assertEquals('Add Bundle Component Row', $addComponent->title);
        $this->assertEquals('add-row btn btn-primary p-1', $addComponent->class);
        $this->assertEquals('Add Row', $addComponent->value);
        $this->assertEquals('icon-plus', $addComponent->icon_class);
    }

    /**
     * Test first row (Add button) rendering.
     */
    public function test_first_row_add_button_rendering(): void
    {
        $view = $this->blade(
            '<x-channel-lister::custom.sku-bundle-component-input-row :is-first="true" />',
            []
        );

        $view->assertSee('Add Row');
        $view->assertSee('id="add-component-button"', false);
        $view->assertSee('add-row btn btn-primary p-1', false);
        $view->assertSee('icon-plus', false);
    }

    /**
     * Test non-first row (Remove button) rendering.
     */
    public function test_non_first_row_remove_button_rendering(): void
    {
        $view = $this->blade(
            '<x-channel-lister::custom.sku-bundle-component-input-row :is-first="false" />',
            []
        );

        $view->assertSee('Remove Row');
        $view->assertSee('remove-row btn btn-primary p-1', false);
        $view->assertSee('icon-minus', false);
    }

    /**
     * Test default behavior without isFirst parameter.
     */
    public function test_default_behavior_without_is_first(): void
    {
        // Component defaults to isFirst = false
        $component = new SkuBundleComponentInputRow;

        $this->assertEquals('', $component->id);
        $this->assertEquals('Remove Bundle Component Row', $component->title);
        $this->assertEquals('remove-row btn btn-primary p-1', $component->class);
        $this->assertEquals('Remove Row', $component->value);
        $this->assertEquals('icon-minus', $component->icon_class);
    }

    /**
     * Test button classes are present.
     */
    public function test_bootstrap_button_classes_present(): void
    {
        $view = $this->blade(
            '<x-channel-lister::custom.sku-bundle-component-input-row :is-first="false" />',
            []
        );

        $view->assertSee('btn', false);
        $view->assertSee('btn-primary', false);
    }

    /**
     * Test title attribute is present.
     */
    public function test_title_attribute_present(): void
    {
        $view = $this->blade(
            '<x-channel-lister::custom.sku-bundle-component-input-row :is-first="true" />',
            []
        );

        $view->assertSee('title="Add Bundle Component Row"', false);
    }

    /**
     * Test remove button title attribute.
     */
    public function test_remove_button_title_attribute(): void
    {
        $view = $this->blade(
            '<x-channel-lister::custom.sku-bundle-component-input-row :is-first="false" />',
            []
        );

        $view->assertSee('title="Remove Bundle Component Row"', false);
    }

    /**
     * Test component renders input elements.
     */
    public function test_component_renders_input_elements(): void
    {
        $view = $this->blade(
            '<x-channel-lister::custom.sku-bundle-component-input-row :is-first="false" />',
            []
        );

        $view->assertSee('type="button"', false);
        $view->assertSee('input', false);
    }

    /**
     * Test add button specific attributes.
     */
    public function test_add_button_specific_attributes(): void
    {
        $component = new SkuBundleComponentInputRow(true);
        $view = $component->render();
        $view->getData();

        $this->assertEquals('add-component-button', $component->id);
        $this->assertEquals('Add Bundle Component Row', $component->title);
        $this->assertEquals('add-row btn btn-primary p-1', $component->class);
        $this->assertEquals('Add Row', $component->value);
        $this->assertEquals('icon-plus', $component->icon_class);
    }

    /**
     * Test remove button specific attributes.
     */
    public function test_remove_button_specific_attributes(): void
    {
        $component = new SkuBundleComponentInputRow(false);

        $this->assertEquals('', $component->id);
        $this->assertEquals('Remove Bundle Component Row', $component->title);
        $this->assertEquals('remove-row btn btn-primary p-1', $component->class);
        $this->assertEquals('Remove Row', $component->value);
        $this->assertEquals('icon-minus', $component->icon_class);
    }

    /**
     * Test component icon classes.
     */
    public function test_component_icon_classes(): void
    {
        // Test plus icon for add button
        $addView = $this->blade(
            '<x-channel-lister::custom.sku-bundle-component-input-row :is-first="true" />',
            []
        );
        $addView->assertSee('icon-plus', false);

        // Test minus icon for remove button
        $removeView = $this->blade(
            '<x-channel-lister::custom.sku-bundle-component-input-row :is-first="false" />',
            []
        );
        $removeView->assertSee('icon-minus', false);
    }

    /**
     * Test button values.
     */
    public function test_button_values(): void
    {
        // Test Add Row value
        $addView = $this->blade(
            '<x-channel-lister::custom.sku-bundle-component-input-row :is-first="true" />',
            []
        );
        $addView->assertSee('value="Add Row"', false);

        // Test Remove Row value
        $removeView = $this->blade(
            '<x-channel-lister::custom.sku-bundle-component-input-row :is-first="false" />',
            []
        );
        $removeView->assertSee('value="Remove Row"', false);
    }

    /**
     * Test component renders without errors.
     */
    public function test_component_renders_without_errors(): void
    {
        $component = new SkuBundleComponentInputRow(true);
        $view = $component->render();

        $this->assertNotNull($view);
        $this->assertEquals('channel-lister::components.custom.sku-bundle-component-input-row', $view->name());
    }

    /**
     * Test state changes after render call.
     */
    public function test_state_changes_after_render_call(): void
    {
        $component = new SkuBundleComponentInputRow(true);

        // Properties should be set during render
        $component->render();

        $this->assertEquals('add-component-button', $component->id);
        $this->assertEquals('Add Bundle Component Row', $component->title);
        $this->assertEquals('add-row btn btn-primary p-1', $component->class);
        $this->assertEquals('Add Row', $component->value);
        $this->assertEquals('icon-plus', $component->icon_class);
    }

    /**
     * Test component with boolean parameter variations.
     */
    public function test_component_boolean_parameter_variations(): void
    {
        // Test with explicit true
        $trueCaseCOmponent = new SkuBundleComponentInputRow(true);
        $trueCaseCOmponent->render();
        $this->assertEquals('Add Row', $trueCaseCOmponent->value);

        // Test with explicit false
        $falseCaseComponent = new SkuBundleComponentInputRow(false);
        $this->assertEquals('Remove Row', $falseCaseComponent->value);

        // Test with default (should be false)
        $defaultComponent = new SkuBundleComponentInputRow;
        $this->assertEquals('Remove Row', $defaultComponent->value);
    }

    /**
     * Test CSS classes are properly structured.
     */
    public function test_css_classes_properly_structured(): void
    {
        $addComponent = new SkuBundleComponentInputRow(true);
        $addComponent->render();

        // Verify add button classes
        $this->assertStringContainsString('add-row', $addComponent->class);
        $this->assertStringContainsString('btn', $addComponent->class);
        $this->assertStringContainsString('btn-primary', $addComponent->class);
        $this->assertStringContainsString('p-1', $addComponent->class);

        $removeComponent = new SkuBundleComponentInputRow(false);

        // Verify remove button classes
        $this->assertStringContainsString('remove-row', $removeComponent->class);
        $this->assertStringContainsString('btn', $removeComponent->class);
        $this->assertStringContainsString('btn-primary', $removeComponent->class);
        $this->assertStringContainsString('p-1', $removeComponent->class);
    }
}
