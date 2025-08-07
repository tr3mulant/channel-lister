<?php

namespace IGE\ChannelLister\Tests\Feature\View\Components;

use IGE\ChannelLister\Models\ChannelListerField;
use IGE\ChannelLister\Tests\TestCase;
use Mockery;

class SelectFormInputTest extends TestCase
{
    /**
     * Test that component renders basic select input correctly.
     */
    public function test_renders_basic_select_input(): void
    {
        $field = Mockery::mock(ChannelListerField::class)->makePartial();
        $field->field_name = 'status';
        $field->display_name = 'Order Status';
        $field->tooltip = 'Select the current order status';
        $field->example = 'pending';
        $field->required = false;

        $field->shouldReceive('getInputTypeAuxOptions')
            ->once()
            ->andReturn(['pending', 'processing', 'completed']);

        $view = $this->blade(
            '<x-channel-lister::select-form-input :params="$field" class-str-default="form-group" />',
            ['field' => $field]
        );

        // Basic element structure
        $view->assertSee('form-group', false);
        $view->assertSee('<select', false);
        $view->assertSee('data-style="bg-white border"', false);
        $view->assertSee('data-size="10"', false);

        // Field-specific checks
        $view->assertSee('name="status"', false);
        $view->assertSee('id="status-id"', false);
        $view->assertSee('Order Status');
        $view->assertSee('title="Select..."', false);

        // Options
        $view->assertSee('<option', false);
        $view->assertSee('value="pending"', false);
        $view->assertSee('>Pending</option>', false);
        $view->assertSee('value="processing"', false);
        $view->assertSee('>Processing</option>', false);
        $view->assertSee('value="completed"', false);
        $view->assertSee('>Completed</option>', false);

        // Tooltip and mapping info
        $view->assertSee('Select the current order status');
        $view->assertSee('Maps To: <code>status</code>', false);

        // Should not have required attribute
        $view->assertDontSee('required', false);
    }

    /**
     * Test that component renders required select input correctly.
     */
    public function test_renders_required_select_input(): void
    {
        $field = Mockery::mock(ChannelListerField::class)->makePartial();
        $field->field_name = 'priority';
        $field->display_name = 'Priority Level';
        $field->tooltip = 'Select priority';
        $field->example = '';
        $field->required = true;

        $field->shouldReceive('getInputTypeAuxOptions')
            ->once()
            ->andReturn(['low', 'medium', 'high']);

        $view = $this->blade(
            '<x-channel-lister::select-form-input :params="$field" />',
            ['field' => $field]
        );

        // Check required class and attribute
        $view->assertSee('form-group required', false);
        $view->assertSee('required', false);
        $view->assertSee('Priority Level');
    }

    /**
     * Test that component handles options with display names correctly.
     */
    public function test_renders_options_with_display_names(): void
    {
        $field = Mockery::mock(ChannelListerField::class)->makePartial();
        $field->field_name = 'country';
        $field->display_name = 'Country';
        $field->tooltip = 'Select your country';
        $field->example = '';
        $field->required = false;

        $field->shouldReceive('getInputTypeAuxOptions')
            ->once()
            ->andReturn([
                'us==United States',
                'ca==Canada',
                'uk==United Kingdom',
                'au==Australia',
            ]);

        $view = $this->blade(
            '<x-channel-lister::select-form-input :params="$field" />',
            ['field' => $field]
        );

        // Check options with custom display names
        $view->assertSee('value="us"', false);
        $view->assertSee('>United States</option>', false);
        $view->assertSee('value="ca"', false);
        $view->assertSee('>Canada</option>', false);
        $view->assertSee('value="uk"', false);
        $view->assertSee('>United Kingdom</option>', false);
        $view->assertSee('value="au"', false);
        $view->assertSee('>Australia</option>', false);
    }

    /**
     * Test that component renders editable select when __OTHER__ option is present.
     */
    public function test_renders_editable_select_with_other_option(): void
    {
        $field = Mockery::mock(ChannelListerField::class)->makePartial();
        $field->field_name = 'category';
        $field->display_name = 'Product Category';
        $field->tooltip = 'Select or enter a category';
        $field->example = '';
        $field->required = false;

        $field->shouldReceive('getInputTypeAuxOptions')
            ->once()
            ->andReturn([
                'electronics==Electronics',
                'clothing==Clothing',
                'food==Food & Beverages',
                '__OTHER__==Other',
            ]);

        $view = $this->blade(
            '<x-channel-lister::select-form-input :params="$field" />',
            ['field' => $field]
        );

        // Should have editable-select class
        $view->assertSee('editable-select', false);
        $view->assertSee('bg-white', false);

        // Should NOT have selectpicker data attributes
        $view->assertDontSee('data-style="bg-white border"', false);

        // __OTHER__ option should be removed from display
        $view->assertDontSee('value="__OTHER__"', false);
        $view->assertDontSee('>Other</option>', false);

        // Other options should still be present
        $view->assertSee('value="electronics"', false);
        $view->assertSee('>Electronics</option>', false);
        $view->assertSee('value="clothing"', false);
        $view->assertSee('>Clothing</option>', false);
        $view->assertSee('value="food"', false);
        $view->assertSee('>Food &amp; Beverages</option>', false);
    }

    /**
     * Test that component enables search for more than 10 options.
     */
    public function test_enables_search_for_many_options(): void
    {
        $options = [];
        for ($i = 1; $i <= 15; $i++) {
            $options[] = "option{$i}==Option {$i}";
        }

        $field = Mockery::mock(ChannelListerField::class)->makePartial();
        $field->field_name = 'large_list';
        $field->display_name = 'Large List';
        $field->tooltip = 'Select from many options';
        $field->example = '';
        $field->required = false;

        $field->shouldReceive('getInputTypeAuxOptions')
            ->once()
            ->andReturn($options);

        $view = $this->blade(
            '<x-channel-lister::select-form-input :params="$field" />',
            ['field' => $field]
        );

        // Should enable live search for > 10 options
        $view->assertSee('data-live-search="true"', false);

        // Verify some options are rendered
        $view->assertSee('value="option1"', false);
        $view->assertSee('>Option 1</option>', false);
        $view->assertSee('value="option15"', false);
        $view->assertSee('>Option 15</option>', false);
    }

    /**
     * Test that component disables search for 10 or fewer options.
     */
    public function test_disables_search_for_few_options(): void
    {
        $field = Mockery::mock(ChannelListerField::class)->makePartial();
        $field->field_name = 'small_list';
        $field->display_name = 'Small List';
        $field->tooltip = 'Select from few options';
        $field->example = '';
        $field->required = false;

        $field->shouldReceive('getInputTypeAuxOptions')
            ->once()
            ->andReturn(['opt1', 'opt2', 'opt3']);

        $view = $this->blade(
            '<x-channel-lister::select-form-input :params="$field" />',
            ['field' => $field]
        );

        // Should disable live search for <= 10 options
        $view->assertSee('data-live-search="false"', false);
    }

    /**
     * Test that component handles empty display_name correctly.
     */
    public function test_uses_field_name_when_display_name_empty(): void
    {
        $field = Mockery::mock(ChannelListerField::class)->makePartial();
        $field->field_name = 'product_code';
        $field->display_name = '';
        $field->tooltip = 'Enter product code';
        $field->example = '';
        $field->required = false;

        $field->shouldReceive('getInputTypeAuxOptions')
            ->once()
            ->andReturn(['code1', 'code2']);

        $view = $this->blade(
            '<x-channel-lister::select-form-input :params="$field" />',
            ['field' => $field]
        );

        // Should use field_name as label when display_name is empty
        $view->assertSee('product_code');
        $view->assertSee('<label', false);
    }

    /**
     * Test that component handles null input_type_aux gracefully.
     */
    public function test_handles_null_input_type_aux(): void
    {
        $field = Mockery::mock(ChannelListerField::class)->makePartial();
        $field->field_name = 'empty_select';
        $field->display_name = 'Empty Select';
        $field->tooltip = 'No options available';
        $field->example = '';
        $field->required = false;

        $field->shouldReceive('getInputTypeAuxOptions')
            ->once()
            ->andReturn(null);

        $view = $this->blade(
            '<x-channel-lister::select-form-input :params="$field" />',
            ['field' => $field]
        );

        // Should render select without options
        $view->assertSee('<select', false);
        $view->assertSee('name="empty_select"', false);
        $view->assertSee('Empty Select');
        // Should have no option elements (or check that it doesn't error)
        $view->assertSee('</select>', false);
    }

    /**
     * Test that component handles very long display names correctly.
     */
    public function test_handles_long_display_names(): void
    {
        $longText = str_repeat('This is a very long display name ', 20); // > 256 chars

        $field = Mockery::mock(ChannelListerField::class)->makePartial();
        $field->field_name = 'long_option';
        $field->display_name = 'Long Option';
        $field->tooltip = 'Select option';
        $field->example = '';
        $field->required = false;

        $field->shouldReceive('getInputTypeAuxOptions')
            ->once()
            ->andReturn([
                'long=='.$longText,
                'short==Short Name',
            ]);

        $view = $this->blade(
            '<x-channel-lister::select-form-input :params="$field" />',
            ['field' => $field]
        );

        // Long option should have white-space:normal style
        // The @style directive may render inline styles differently
        $view->assertSee('value="long"', false);
        $view->assertSee($longText, false); // Verify the long text is present
        $view->assertSee('white-space:normal', false); // Verify the style is applied

        // Short option should not have the style
        $view->assertSee('value="short"', false);
        $view->assertSee('>Short Name</option>', false);

        // Verify that the short option does NOT have the white-space style
        // by checking that the option tag for "short" appears without the style
        $view->assertDontSee('value="short" style', false);
    }

    /**
     * Test that component handles options without display names correctly.
     */
    public function test_handles_options_without_display_names(): void
    {
        $field = Mockery::mock(ChannelListerField::class)->makePartial();
        $field->field_name = 'simple_select';
        $field->display_name = 'Simple Select';
        $field->tooltip = 'Select an option';
        $field->example = '';
        $field->required = false;

        $field->shouldReceive('getInputTypeAuxOptions')
            ->once()
            ->andReturn([
                'draft',
                'published',
                'archived',
            ]);

        $view = $this->blade(
            '<x-channel-lister::select-form-input :params="$field" />',
            ['field' => $field]
        );

        // Options without == should use ucwords on the value
        $view->assertSee('value="draft"', false);
        $view->assertSee('>Draft</option>', false);
        $view->assertSee('value="published"', false);
        $view->assertSee('>Published</option>', false);
        $view->assertSee('value="archived"', false);
        $view->assertSee('>Archived</option>', false);
    }

    /**
     * Test that component applies custom class string correctly.
     */
    public function test_applies_custom_class_string(): void
    {
        $field = Mockery::mock(ChannelListerField::class)->makePartial();
        $field->field_name = 'custom_class';
        $field->display_name = 'Custom Class';
        $field->tooltip = 'Test custom class';
        $field->example = '';
        $field->required = false;

        $field->shouldReceive('getInputTypeAuxOptions')
            ->once()
            ->andReturn(['option1']);

        $view = $this->blade(
            '<x-channel-lister::select-form-input :params="$field" class-str-default="custom-form-group" />',
            ['field' => $field]
        );

        // Should use custom class instead of default
        $view->assertSee('custom-form-group', false);
        $view->assertSee('selectpicker', false);
    }

    /**
     * Clean up after test.
     */
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
