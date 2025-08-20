<?php

namespace IGE\ChannelLister\Tests\Feature\View\Components;

use IGE\ChannelLister\Tests\TestCase;
use IGE\ChannelLister\View\Components\ShippingCalculator;

class ShippingCalculatorTest extends TestCase
{
    /**
     * Test component instantiation with default parameters.
     */
    public function test_component_instantiation_with_defaults(): void
    {
        $component = new ShippingCalculator;

        $this->assertEquals('form-control', $component->classStrDefault);
        $this->assertTrue($component->showDimensionalCalculator);
        $this->assertEquals('', $component->fromZip);
        $this->assertEquals('', $component->toZip);
    }

    /**
     * Test component instantiation with custom parameters.
     */
    public function test_component_instantiation_with_custom_parameters(): void
    {
        $component = new ShippingCalculator(
            classStrDefault: 'custom-form-control',
            showDimensionalCalculator: false,
            fromZip: '98225',
            toZip: '98101'
        );

        $this->assertEquals('custom-form-control', $component->classStrDefault);
        $this->assertFalse($component->showDimensionalCalculator);
        $this->assertEquals('98225', $component->fromZip);
        $this->assertEquals('98101', $component->toZip);
    }

    /**
     * Test component renders correct view with data.
     */
    public function test_component_renders_correct_view(): void
    {
        $component = new ShippingCalculator(
            classStrDefault: 'test-class',
            showDimensionalCalculator: true,
            fromZip: '98225',
            toZip: '98101'
        );

        $view = $component->render();

        $this->assertEquals('channel-lister::components.shipping-calculator', $view->name());

        $viewData = $view->getData();
        $this->assertEquals('test-class', $viewData['classStrDefault']);
        $this->assertTrue($viewData['showDimensionalCalculator']);
        $this->assertEquals('98225', $viewData['fromZip']);
        $this->assertEquals('98101', $viewData['toZip']);
    }

    /**
     * Test component view rendering with Blade.
     */
    public function test_component_view_rendering(): void
    {
        $view = $this->blade(
            '<x-channel-lister::shipping-calculator class-str-default="custom-class" from-zip="98225" to-zip="98101" />',
            []
        );

        // Test basic structure is present
        $view->assertSee('custom-class', false);
        $view->assertSee('98225', false);
        $view->assertSee('98101', false);
    }

    /**
     * Test component with show dimensional calculator disabled.
     */
    public function test_component_with_dimensional_calculator_disabled(): void
    {
        $component = new ShippingCalculator(showDimensionalCalculator: false);

        $view = $component->render();
        $viewData = $view->getData();

        $this->assertFalse($viewData['showDimensionalCalculator']);
    }

    /**
     * Test component renders with empty ZIP codes.
     */
    public function test_component_with_empty_zip_codes(): void
    {
        $component = new ShippingCalculator(fromZip: '', toZip: '');

        $view = $component->render();
        $viewData = $view->getData();

        $this->assertEquals('', $viewData['fromZip']);
        $this->assertEquals('', $viewData['toZip']);
    }

    /**
     * Test component with special characters in ZIP codes.
     */
    public function test_component_with_special_zip_codes(): void
    {
        $component = new ShippingCalculator(
            fromZip: '12345-6789',
            toZip: '98101-1234'
        );

        $view = $component->render();
        $viewData = $view->getData();

        $this->assertEquals('12345-6789', $viewData['fromZip']);
        $this->assertEquals('98101-1234', $viewData['toZip']);
    }

    /**
     * Test component with boolean parameter variations.
     */
    public function test_component_boolean_parameter_variations(): void
    {
        // Test true
        $component1 = new ShippingCalculator(showDimensionalCalculator: true);
        $viewData1 = $component1->render()->getData();
        $this->assertTrue($viewData1['showDimensionalCalculator']);

        // Test false
        $component2 = new ShippingCalculator(showDimensionalCalculator: false);
        $viewData2 = $component2->render()->getData();
        $this->assertFalse($viewData2['showDimensionalCalculator']);
    }

    /**
     * Test component handles long class strings.
     */
    public function test_component_with_long_class_string(): void
    {
        $longClass = 'form-control custom-shipping-calculator-input very-long-class-name-for-testing';
        $component = new ShippingCalculator(classStrDefault: $longClass);

        $view = $component->render();
        $viewData = $view->getData();

        $this->assertEquals($longClass, $viewData['classStrDefault']);
    }

    /**
     * Test component data structure integrity.
     */
    public function test_component_data_structure(): void
    {
        $component = new ShippingCalculator;
        $view = $component->render();
        $data = $view->getData();

        // Verify all expected keys are present
        $expectedKeys = ['classStrDefault', 'showDimensionalCalculator', 'fromZip', 'toZip'];
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $data);
        }

        // Verify data types
        $this->assertIsString($data['classStrDefault']);
        $this->assertIsBool($data['showDimensionalCalculator']);
        $this->assertIsString($data['fromZip']);
        $this->assertIsString($data['toZip']);
    }

    /**
     * Test component with edge case ZIP codes.
     */
    public function test_component_with_edge_case_zip_codes(): void
    {
        $testCases = [
            ['fromZip' => '00000', 'toZip' => '99999'],
            ['fromZip' => '12345', 'toZip' => ''],
            ['fromZip' => '', 'toZip' => '54321'],
            ['fromZip' => '98225-0000', 'toZip' => '98101-9999'],
        ];

        foreach ($testCases as $case) {
            $component = new ShippingCalculator(
                fromZip: $case['fromZip'],
                toZip: $case['toZip']
            );

            $view = $component->render();
            $viewData = $view->getData();

            $this->assertEquals($case['fromZip'], $viewData['fromZip']);
            $this->assertEquals($case['toZip'], $viewData['toZip']);
        }
    }

    /**
     * Test component can be rendered multiple times.
     */
    public function test_component_multiple_renders(): void
    {
        $component = new ShippingCalculator(
            classStrDefault: 'test-class',
            fromZip: '98225'
        );

        $view1 = $component->render();
        $view2 = $component->render();

        $this->assertEquals($view1->name(), $view2->name());
        $this->assertEquals($view1->getData(), $view2->getData());
    }

    /**
     * Test component properties are publicly accessible.
     */
    public function test_component_properties_accessibility(): void
    {
        $component = new ShippingCalculator(
            classStrDefault: 'test-access',
            showDimensionalCalculator: false,
            fromZip: '12345',
            toZip: '67890'
        );

        // Test all properties are accessible
        $this->assertEquals('test-access', $component->classStrDefault);
        $this->assertFalse($component->showDimensionalCalculator);
        $this->assertEquals('12345', $component->fromZip);
        $this->assertEquals('67890', $component->toZip);
    }

    /**
     * Test component with international postal codes (edge case).
     */
    public function test_component_with_international_postal_codes(): void
    {
        // Test with non-US postal codes (should still work)
        $component = new ShippingCalculator(
            fromZip: 'M5V 3A8', // Canadian postal code
            toZip: 'SW1A 1AA'  // UK postal code
        );

        $view = $component->render();
        $viewData = $view->getData();

        $this->assertEquals('M5V 3A8', $viewData['fromZip']);
        $this->assertEquals('SW1A 1AA', $viewData['toZip']);
    }

    /**
     * Test component renders consistently with same parameters.
     */
    public function test_component_consistency(): void
    {
        $params = [
            'classStrDefault' => 'consistent-class',
            'showDimensionalCalculator' => true,
            'fromZip' => '98225',
            'toZip' => '98101',
        ];

        $component1 = new ShippingCalculator(...$params);
        $component2 = new ShippingCalculator(...$params);

        $data1 = $component1->render()->getData();
        $data2 = $component2->render()->getData();

        $this->assertEquals($data1, $data2);
    }
}
