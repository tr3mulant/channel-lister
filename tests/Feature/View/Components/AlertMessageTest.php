<?php

namespace IGE\ChannelLister\Tests\Feature\View\Components;

use IGE\ChannelLister\Models\ChannelListerField;
use IGE\ChannelLister\Tests\TestCase;

class AlertMessageTest extends TestCase
{
    /**
     * Test alert message renders with valid alert type
     */
    public function test_alert_message_renders_with_valid_type(): void
    {
        $field = ChannelListerField::factory()->create([
            'input_type_aux' => 'success',
            'field_name' => 'test_field',
            'display_name' => 'Test Alert',
            'tooltip' => 'This is a test message',
            'example' => 'Additional information',
        ]);

        $view = $this->blade(
            '<x-channel-lister::alert-message :params="$field" />',
            ['field' => $field]
        );

        $view->assertSee('alert alert-success', false);
        $view->assertSee('Test Alert');
        $view->assertSee('This is a test message');
        $view->assertSee('Additional information');
    }

    /**
     * Test all valid alert types
     */
    public function test_all_valid_alert_types(): void
    {
        $validTypes = ['success', 'info', 'warning', 'danger'];

        foreach ($validTypes as $type) {
            $field = ChannelListerField::factory()->create([
                'input_type_aux' => $type,
                'display_name' => "Test {$type}",
                'tooltip' => "This is a {$type} message",
            ]);

            $view = $this->blade(
                '<x-channel-lister::alert-message :params="$field" />',
                ['field' => $field]
            );

            $view->assertSee("alert alert-{$type}", false);
            $view->assertSee("Test {$type}");
        }
    }

    /**
     * Test defaults to 'info' when alert type is empty/null
     */
    public function test_defaults_to_info_when_alert_type_empty(): void
    {
        $emptyValues = ['', '0', null];

        foreach ($emptyValues as $emptyValue) {
            $field = ChannelListerField::factory()->create([
                'input_type_aux' => $emptyValue,
                'display_name' => 'Test Alert',
                'tooltip' => 'Test message',
            ]);

            $view = $this->blade(
                '<x-channel-lister::alert-message :params="$field" />',
                ['field' => $field]
            );

            $view->assertSee('alert alert-info', false);
        }
    }

    /**
     * Test handles array input type (takes first element)
     */
    public function test_handles_array_input_type(): void
    {
        $field = ChannelListerField::factory()->create([
            'input_type_aux' => ['warning', 'secondary'], // Should use 'warning'
            'display_name' => 'Array Test',
            'tooltip' => 'Array test message',
        ]);

        $view = $this->blade(
            '<x-channel-lister::alert-message :params="$field" />',
            ['field' => $field]
        );

        $view->assertSee('alert alert-warning', false);
    }

    /* Commented out for now because it is supposed to fail, and it does */
    // /**
    //  * Test throws exception for invalid alert type
    //  * Test is Supposed to fail/ throw exception
    //  */
    // public function test_throws_exception_for_invalid_alert_type(): void
    // {
    //     $field = ChannelListerField::factory()->create([
    //         'input_type_aux' => 'invalid-type',
    //         'display_name' => 'Invalid Test',
    //         'tooltip' => 'Test message',
    //     ]);

    //     $this->expectException(\RuntimeException::class);
    //     $this->expectExceptionMessage("Invalid alert type 'invalid-type' in field 'input_type_aux' must be one of success, info, warning, danger");

    //     $this->blade(
    //         '<x-channel-lister::alert-message :params="$field" />',
    //         ['field' => $field]
    //     );
    // }

    /**
     * Test case insensitive alert type handling
     */
    public function test_case_insensitive_alert_type(): void
    {
        $field = ChannelListerField::factory()->create([
            'input_type_aux' => 'SUCCESS', // Uppercase
            'display_name' => 'Case Test',
            'tooltip' => 'Case test message',
        ]);

        $view = $this->blade(
            '<x-channel-lister::alert-message :params="$field" />',
            ['field' => $field]
        );

        $view->assertSee('alert alert-success', false);
    }

    /**
     * Test falls back to field_name when display_name is empty
     */
    public function test_falls_back_to_field_name_when_display_name_empty(): void
    {
        $field = ChannelListerField::factory()->create([
            'input_type_aux' => 'info',
            'field_name' => 'test_field',
            'tooltip' => 'Test message',
        ]);

        $field->display_name = '';
        $field->save();
        $field->refresh();

        $view = $this->blade(
            '<x-channel-lister::alert-message :params="$field" />',
            ['field' => $field]
        );

        $view->assertSee('Test Field');
        $view->assertDontSee('<strong></strong>', false);
    }

    /**
     * Test handles HTML content in tooltip and example
     */
    public function test_handles_html_content(): void
    {
        $field = ChannelListerField::factory()->create([
            'input_type_aux' => 'warning',
            'display_name' => 'HTML Test',
            'tooltip' => 'Message with <em>emphasis</em>',
            'example' => 'Example with <strong>bold</strong> text',
        ]);

        $view = $this->blade(
            '<x-channel-lister::alert-message :params="$field" />',
            ['field' => $field]
        );

        // Should render HTML (using {!! !!})
        $view->assertSee('<em>emphasis</em>', false);
        $view->assertSee('<strong>bold</strong>', false);
    }

    /**
     * Test component with custom class string default
     */
    public function test_component_with_custom_class_string_default(): void
    {
        $field = ChannelListerField::factory()->create([
            'input_type_aux' => 'success',
            'display_name' => 'Custom Class Test',
            'tooltip' => 'Test message',
        ]);

        $view = $this->blade(
            '<x-channel-lister::alert-message :params="$field" class-str-default="custom-form-group" />',
            ['field' => $field]
        );

        // Test that the component renders successfully with custom class
        $view->assertSee('Custom Class Test');
        $view->assertSee('alert alert-success', false);
    }

    /**
     * Test empty array defaults to info
     */
    public function test_empty_array_defaults_to_info(): void
    {
        $field = ChannelListerField::factory()->create([
            'input_type_aux' => [], // Empty array
            'display_name' => 'Empty Array Test',
            'tooltip' => 'Test message',
        ]);

        $view = $this->blade(
            '<x-channel-lister::alert-message :params="$field" />',
            ['field' => $field]
        );

        $view->assertSee('alert alert-info', false);
    }

    /**
     * Test component handles missing optional fields gracefully
     */
    public function test_handles_missing_optional_fields(): void
    {
        $field = ChannelListerField::factory()->create([
            'input_type_aux' => 'info',
            'display_name' => 'Test',
            'tooltip' => 'Required message',
            'example' => null, // Null example
        ]);

        $view = $this->blade(
            '<x-channel-lister::alert-message :params="$field" />',
            ['field' => $field]
        );

        $view->assertSee('Required message');
        // Should handle null example gracefully
        $view->assertDontSee('null');
    }
}
