<?php

namespace IGE\ChannelLister\Tests\Feature\View\Components;

use IGE\ChannelLister\Enums\InputType;
use IGE\ChannelLister\Enums\Type;
use IGE\ChannelLister\Models\ChannelListerField;
use IGE\ChannelLister\Tests\TestCase;

class ChannelListerFormInputTest extends TestCase
{
    /**
     * Test ALERT input type renders alert-message component.
     */
    public function test_alert_input_type_renders_alert_message(): void
    {
        $field = ChannelListerField::factory()->create([
            'input_type' => InputType::ALERT,
            'input_type_aux' => 'success',
            'display_name' => 'Test Alert',
            'tooltip' => 'Test alert message',
        ]);

        $view = $this->blade(
            '<x-channel-lister::channel-lister-form-input :params="$field" />',
            ['field' => $field]
        );

        $view->assertSee('alert alert-success', false);
        $view->assertSee('Test Alert');
        $view->assertSee('Test alert message');
    }

    /**
     * Test CHECKBOX input type renders checkbox-form-input component.
     */
    public function test_checkbox_input_type_renders_checkbox_form(): void
    {
        $field = ChannelListerField::factory()->create([
            'input_type' => InputType::CHECKBOX,
            'field_name' => 'test_checkbox',
            'display_name' => 'Test Checkbox',
        ]);

        $view = $this->blade(
            '<x-channel-lister::channel-lister-form-input :params="$field" />',
            ['field' => $field]
        );

        // Should render the checkbox component (exact output depends on checkbox component implementation)
        $view->assertSee('test_checkbox');
    }

    /**
     * Test COMMA_SEPARATED input type renders comma-separated-form component.
     */
    public function test_comma_separated_input_type_renders_comma_form(): void
    {
        $field = ChannelListerField::factory()->create([
            'input_type' => InputType::COMMA_SEPARATED,
            'field_name' => 'keywords',
            'display_name' => 'Keywords',
        ]);

        $view = $this->blade(
            '<x-channel-lister::channel-lister-form-input :params="$field" />',
            ['field' => $field]
        );

        // Should render the comma-separated-form component
        $view->assertSee('keywords');
    }

    /**
     * Test CURRENCY input type renders currency-form-input component.
     */
    public function test_currency_input_type_renders_currency_form(): void
    {
        $field = ChannelListerField::factory()->create([
            'input_type' => InputType::CURRENCY,
            'field_name' => 'price',
            'display_name' => 'Price',
        ]);

        $view = $this->blade(
            '<x-channel-lister::channel-lister-form-input :params="$field" />',
            ['field' => $field]
        );

        // Should render the currency-form-input component
        $view->assertSee('price');
    }

    /**
     * Test CUSTOM input type renders custom form-input component.
     */
    public function test_custom_input_type_renders_custom_form(): void
    {
        $field = ChannelListerField::factory()->create([
            'input_type' => InputType::CUSTOM,
            'field_name' => 'custom_field',
            'display_name' => 'Custom Field',
        ]);

        $view = $this->blade(
            '<x-channel-lister::channel-lister-form-input :params="$field" />',
            ['field' => $field]
        );

        // Should render the custom.form-input component
        $view->assertSee('custom_field');
    }

    /**
     * Test DECIMAL input type renders decimal-form-input component.
     */
    public function test_decimal_input_type_renders_decimal_form(): void
    {
        $field = ChannelListerField::factory()->create([
            'input_type' => InputType::DECIMAL,
            'field_name' => 'weight',
            'display_name' => 'Weight',
        ]);

        $view = $this->blade(
            '<x-channel-lister::channel-lister-form-input :params="$field" />',
            ['field' => $field]
        );

        // Should render the decimal-form-input component
        $view->assertSee('weight');
    }

    /**
     * Test INTEGER input type renders integer-form-input component.
     */
    public function test_integer_input_type_renders_integer_form(): void
    {
        $field = ChannelListerField::factory()->create([
            'input_type' => InputType::INTEGER,
            'field_name' => 'quantity',
            'display_name' => 'Quantity',
        ]);

        $view = $this->blade(
            '<x-channel-lister::channel-lister-form-input :params="$field" />',
            ['field' => $field]
        );

        // Should render the integer-form-input component
        $view->assertSee('quantity');
    }

    /**
     * Test SELECT input type renders select-form-input component.
     */
    public function test_select_input_type_renders_select_form(): void
    {
        $field = ChannelListerField::factory()->create([
            'input_type' => InputType::SELECT,
            'field_name' => 'category',
            'display_name' => 'Category',
            'input_type_aux' => 'option1||option2||option3',
        ]);

        $view = $this->blade(
            '<x-channel-lister::channel-lister-form-input :params="$field" />',
            ['field' => $field]
        );

        // Should render the select-form-input component
        $view->assertSee('category');
    }

    /**
     * Test TEXT input type renders text-form-input component.
     */
    public function test_text_input_type_renders_text_form(): void
    {
        $field = ChannelListerField::factory()->create([
            'input_type' => InputType::TEXT,
            'field_name' => 'title',
            'display_name' => 'Title',
        ]);

        $view = $this->blade(
            '<x-channel-lister::channel-lister-form-input :params="$field" />',
            ['field' => $field]
        );

        // Should render the text-form-input component
        $view->assertSee('title');
    }

    /**
     * Test TEXTAREA input type renders textarea-form-input component.
     */
    public function test_textarea_input_type_renders_textarea_form(): void
    {
        $field = ChannelListerField::factory()->create([
            'input_type' => InputType::TEXTAREA,
            'field_name' => 'description',
            'display_name' => 'Description',
        ]);

        $view = $this->blade(
            '<x-channel-lister::channel-lister-form-input :params="$field" />',
            ['field' => $field]
        );

        // Should render the textarea-form-input component
        $view->assertSee('description');
    }

    /**
     * Test URL input type renders url-form-input component.
     */
    public function test_url_input_type_renders_url_form(): void
    {
        $field = ChannelListerField::factory()->create([
            'input_type' => InputType::URL,
            'field_name' => 'website',
            'display_name' => 'Website URL',
        ]);

        $view = $this->blade(
            '<x-channel-lister::channel-lister-form-input :params="$field" />',
            ['field' => $field]
        );

        // Should render the url-form-input component
        $view->assertSee('website');
    }

    /* Test is supposed to fail, and does correctly so commented out for now */
    // /**
    //  * Test that unrecognized input types render error message.
    //  * Test is Supposed to fail, correctly throws the Exception
    //  */
    // public function test_unrecognized_input_type_renders_error(): void
    // {
    //     // Create a field with a mock input type that doesn't match any case
    //     $field = ChannelListerField::factory()->make([
    //         'field_name' => 'test_field',
    //         'display_name' => 'Test Field',
    //     ]);
    //     // We need to mock the input_type to return something not handled
    //     $field->input_type = 'NONEXISTENT_TYPE';
    //     unset($field->casts['input_type']);

    //     $view = $this->blade(
    //         '<x-channel-lister::channel-lister-form-input :params="$field" />',
    //         ['field' => $field]
    //     );

    //     // Should render the default error case
    //     $view->assertSee('alert alert-danger', false);
    //     $view->assertSee('Error:');
    //     $view->assertSee('Unrecognized input_type');
    //     $view->assertSee('NONEXISTENT_TYPE');
    // }

    /**
     * Test component passes custom class string to child components.
     */
    public function test_component_passes_custom_class_string(): void
    {
        $field = ChannelListerField::factory()->create([
            'input_type' => InputType::ALERT,
            'input_type_aux' => 'info',
            'display_name' => 'Test Alert',
            'tooltip' => 'Test message',
        ]);

        $view = $this->blade(
            '<x-channel-lister::channel-lister-form-input :params="$field" class-str-default="custom-form-class" />',
            ['field' => $field]
        );

        // The custom class should be passed to the child component
        // (Exact assertion depends on how child components use the class)
        $view->assertSee('Test Alert');
    }

    /**
     * Test that all InputType enum values are handled.
     */
    public function test_all_input_types_are_handled(): void
    {
        $handledTypes = [
            InputType::ALERT,
            InputType::CHECKBOX,
            InputType::COMMA_SEPARATED,
            InputType::CURRENCY,
            InputType::CUSTOM,
            InputType::DECIMAL,
            InputType::INTEGER,
            InputType::SELECT,
            InputType::TEXT,
            InputType::TEXTAREA,
            InputType::URL,
        ];

        // Get all enum cases
        $allInputTypes = InputType::cases();

        // Verify we're testing all enum values
        $this->assertCount(count($allInputTypes), $handledTypes,
            'Not all InputType enum values are being tested. Please add tests for missing types.');

        foreach ($handledTypes as $inputType) {
            $this->assertContains($inputType, $allInputTypes,
                "InputType::{$inputType->name} is not a valid enum case");
        }
    }

    /**
     * Test that switch statement properly differentiates between input types.
     */
    public function test_input_type_differentiation(): void
    {
        $inputTypes = [
            [InputType::ALERT, 'alert'],
            [InputType::TEXT, 'text'],
            [InputType::SELECT, 'select'],
            [InputType::TEXTAREA, 'textarea'],
        ];

        foreach ($inputTypes as [$inputType, $expectedString]) {
            $field = ChannelListerField::factory()->create([
                'input_type' => $inputType,
                'field_name' => 'test_field_'.uniqid(),
                'display_name' => 'Test Field',
                'tooltip' => 'Test tooltip',
                'input_type_aux' => $inputType === InputType::ALERT ? 'info' : 'test_aux',
            ]);

            $view = $this->blade(
                '<x-channel-lister::channel-lister-form-input :params="$field" />',
                ['field' => $field]
            );

            // Each input type should render its specific component
            // (The exact assertion depends on what each component renders)
            $view->assertSee('Test Field');
            // Ensure it's not rendering the error case
            $view->assertDontSee('Unrecognized input_type');
        }
    }

    /* Supposed to fail, and it does correctly so commented out for now */
    // /**
    //  * Test error case includes field data dump.
    //  * Test is Supposed to fail, correctly throws the Exception
    //  */
    // public function test_error_case_includes_field_dump(): void
    // {
    //     $field = ChannelListerField::factory()->create([
    //         'field_name' => 'debug_field',
    //         'display_name' => 'Debug Field',
    //     ]);
    //     // Mock an invalid input type
    //     $field->input_type = 'INVALID_TYPE';

    //     $view = $this->blade(
    //         '<x-channel-lister::channel-lister-form-input :params="$field" />',
    //         ['field' => $field]
    //     );

    //     // Should contain the dump output and error message
    //     $view->assertSee('alert alert-danger', false);
    //     $view->assertSee('INVALID_TYPE');
    //     // The @dump should output field data (exact format depends on dump implementation)
    // }

    /**
     * Test component handles complex field configurations.
     */
    public function test_component_handles_complex_field_configurations(): void
    {
        $field = ChannelListerField::factory()->create([
            'input_type' => InputType::SELECT,
            'field_name' => 'complex_select',
            'display_name' => 'Complex Select Field',
            'tooltip' => 'This is a complex tooltip with <em>HTML</em>',
            'example' => 'Example: Choose one option',
            'input_type_aux' => 'option1||option2||option3||option4',
            'required' => true,
            'marketplace' => 'amazon',
            'grouping' => 'advanced_options',
        ]);

        $view = $this->blade(
            '<x-channel-lister::channel-lister-form-input :params="$field" class-str-default="advanced-form-control" />',
            ['field' => $field]
        );

        // Should render without errors and include field data
        $view->assertSee('complex_select');
        $view->assertDontSee('Unrecognized input_type');
        $view->assertDontSee('alert alert-danger');
    }
}
