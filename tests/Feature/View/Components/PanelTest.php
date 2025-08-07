<?php

namespace IGE\ChannelLister\Tests\Feature\View\Components;

use IGE\ChannelLister\Models\ChannelListerField;
use IGE\ChannelLister\Tests\TestCase;
use Illuminate\Support\Collection;

class PanelTest extends TestCase
{
    private function createTestFields(int $count = 2): Collection
    {
        return ChannelListerField::factory()->count($count)->make([
            'field_name' => 'test_field',
            'display_name' => 'Test Field',
            'required' => false,
        ]);
    }

    /**
     * Test basic panel rendering with default values.
     */
    public function test_renders_basic_panel_with_defaults(): void
    {
        $fields = $this->createTestFields();

        $view = $this->blade(
            '<x-channel-lister::panel :fields="$fields" grouping-name="Test Group" />',
            ['fields' => $fields]
        );

        // Basic structure
        $view->assertSee('border rounded', false);
        $view->assertSee('panel panel-default', false);
        $view->assertSee('panel_test_group', false); // grouping name class
        $view->assertSee('card-header', false);
        $view->assertSee('card-title', false);
        $view->assertSee('Test Group'); // title
        $view->assertSee('panel-collapse collapse', false);
        $view->assertSee('card-body bg-light', false);
    }

    /**
     * Test panel with custom title.
     */
    public function test_renders_with_custom_title(): void
    {
        $fields = $this->createTestFields();

        $view = $this->blade(
            '<x-channel-lister::panel :fields="$fields" grouping-name="Test Group" title="Custom Title" />',
            ['fields' => $fields]
        );

        $view->assertSee('Custom Title');
        $view->assertDontSee('Test Group'); // Should not see grouping name when custom title provided
    }

    /**
     * Test panel falls back to grouping name when no title provided.
     */
    public function test_falls_back_to_grouping_name_for_title(): void
    {
        $fields = $this->createTestFields();

        $view = $this->blade(
            '<x-channel-lister::panel :fields="$fields" grouping-name="Fallback Title" />',
            ['fields' => $fields]
        );

        $view->assertSee('Fallback Title');
    }

    /**
     * Test panel with custom content (inverted mode).
     */
    public function test_renders_with_custom_content_inverted(): void
    {
        $fields = $this->createTestFields();

        $view = $this->blade(
            '<x-channel-lister::panel :fields="$fields" grouping-name="Test Group" content="Custom content here" :inverted="true" />',
            ['fields' => $fields]
        );

        $view->assertSee('Custom content here');
        $view->assertSee('card-body', false);
        $view->assertDontSee('card-body bg-light', false); // No bg-light in inverted mode
    }

    /**
     * Test panel with additional custom CSS class.
     */
    public function test_renders_with_custom_css_class(): void
    {
        $fields = $this->createTestFields();

        $view = $this->blade(
            '<x-channel-lister::panel :fields="$fields" grouping-name="Test Group" class="custom-panel-class" />',
            ['fields' => $fields]
        );

        $view->assertSee('panel panel-default', false); // Default classes
        $view->assertSee('custom-panel-class', false); // Custom class
    }

    /**
     * Test panel with wide class applied.
     */
    public function test_renders_with_wide_class(): void
    {
        $fields = $this->createTestFields();

        $view = $this->blade(
            '<x-channel-lister::panel :fields="$fields" grouping-name="Test Group" :wide="true" />',
            ['fields' => $fields]
        );

        $view->assertSee('panel_wide', false);
    }

    /**
     * Test panel without wide class.
     */
    public function test_renders_without_wide_class_by_default(): void
    {
        $fields = $this->createTestFields();

        $view = $this->blade(
            '<x-channel-lister::panel :fields="$fields" grouping-name="Test Group" :wide="false" />',
            ['fields' => $fields]
        );

        $view->assertDontSee('panel_wide', false);
    }

    /**
     * Test panel with collapsed class applied by default.
     */
    public function test_renders_collapsed_by_default(): void
    {
        $fields = $this->createTestFields();

        $view = $this->blade(
            '<x-channel-lister::panel :fields="$fields" grouping-name="Test Group" />',
            ['fields' => $fields]
        );

        $view->assertSee('panel_collapsed', false);
        $view->assertDontSee('show', false); // Should not have 'show' class when collapsed
    }

    /**
     * Test panel not collapsed when startCollapsed is false.
     */
    public function test_renders_expanded_when_start_collapsed_false(): void
    {
        $fields = $this->createTestFields();

        $view = $this->blade(
            '<x-channel-lister::panel :fields="$fields" grouping-name="Test Group" :start-collapsed="false" />',
            ['fields' => $fields]
        );

        $view->assertDontSee('panel_collapsed', false);
        $view->assertSee('show', false); // Should have 'show' class when expanded
    }

    /**
     * Test panel ID generation with default values.
     */
    public function test_generates_panel_id_with_defaults(): void
    {
        $fields = $this->createTestFields();

        $view = $this->blade(
            '<x-channel-lister::panel :fields="$fields" grouping-name="Test Group" />',
            ['fields' => $fields]
        );

        // Should generate panel-panel_0-0 or similar
        $view->assertSee('id="panel-panel_0-', false);
        $view->assertSee('href="#panel-content-panel_0-', false);
        $view->assertSee('id="panel-content-panel_0-', false);
    }

    /**
     * Test panel with custom panel ID.
     */
    public function test_renders_with_custom_panel_id(): void
    {
        $fields = $this->createTestFields();

        $view = $this->blade(
            '<x-channel-lister::panel :fields="$fields" grouping-name="Test Group" panel-id="custom-id" />',
            ['fields' => $fields]
        );

        $view->assertSee('id="panel-custom-id-', false);
        $view->assertSee('href="#panel-content-custom-id-', false);
        $view->assertSee('id="panel-content-custom-id-', false);
    }

    /**
     * Test panel with custom panel number.
     */
    public function test_renders_with_custom_panel_number(): void
    {
        $fields = $this->createTestFields();

        $view = $this->blade(
            '<x-channel-lister::panel :fields="$fields" grouping-name="Test Group" :panel-num="5" />',
            ['fields' => $fields]
        );

        // Panel number should be used somewhere in rendering
        $view->assertSee('panel panel-default', false);
    }

    /**
     * Test inverted panel structure.
     */
    public function test_renders_inverted_panel_structure(): void
    {
        $fields = $this->createTestFields();

        $view = $this->blade(
            '<x-channel-lister::panel :fields="$fields" grouping-name="Test Group" :inverted="true" />',
            ['fields' => $fields]
        );

        // In inverted mode, content comes before header
        $view->assertSee('panel-collapse collapse', false);
        $view->assertSee('card-body', false);
        $view->assertSee('card-header', false);
        $view->assertDontSee('border rounded', false); // No border/rounded classes in inverted
        $view->assertDontSee('bg-light', false); // No bg-light in inverted
    }

    /**
     * Test normal (non-inverted) panel structure.
     */
    public function test_renders_normal_panel_structure(): void
    {
        $fields = $this->createTestFields();

        $view = $this->blade(
            '<x-channel-lister::panel :fields="$fields" grouping-name="Test Group" :inverted="false" />',
            ['fields' => $fields]
        );

        // In normal mode, header comes before content
        $view->assertSee('border rounded', false);
        $view->assertSee('card-header', false);
        $view->assertSee('panel-collapse collapse', false);
        $view->assertSee('card-body bg-light', false);
    }

    /**
     * Test grouping name class generation with spaces.
     */
    public function test_grouping_name_class_handles_spaces(): void
    {
        $fields = $this->createTestFields();

        $view = $this->blade(
            '<x-channel-lister::panel :fields="$fields" grouping-name="Multi Word Group" />',
            ['fields' => $fields]
        );

        $view->assertSee('panel_multi_word_group', false);
    }

    /**
     * Test grouping name class generation with mixed case.
     */
    public function test_grouping_name_class_handles_mixed_case(): void
    {
        $fields = $this->createTestFields();

        $view = $this->blade(
            '<x-channel-lister::panel :fields="$fields" grouping-name="CamelCase Group" />',
            ['fields' => $fields]
        );

        $view->assertSee('panel_camelcase_group', false);
    }

    /**
     * Test panel with empty fields collection.
     */
    public function test_renders_with_empty_fields_collection(): void
    {
        $fields = collect([]);

        $view = $this->blade(
            '<x-channel-lister::panel :fields="$fields" grouping-name="Empty Group" />',
            ['fields' => $fields]
        );

        $view->assertSee('Empty Group');
        $view->assertSee('card-body bg-light', false);
        // Should still render structure even with no fields
    }

    /**
     * Test panel with single field.
     */
    public function test_renders_with_single_field(): void
    {
        $fields = $this->createTestFields(1);

        $view = $this->blade(
            '<x-channel-lister::panel :fields="$fields" grouping-name="Single Field Group" />',
            ['fields' => $fields]
        );

        $view->assertSee('Single Field Group');
        $view->assertSee('card-body bg-light', false);
    }

    /**
     * Test panel with multiple fields.
     */
    public function test_renders_with_multiple_fields(): void
    {
        $fields = $this->createTestFields(5);

        $view = $this->blade(
            '<x-channel-lister::panel :fields="$fields" grouping-name="Multiple Fields Group" />',
            ['fields' => $fields]
        );

        $view->assertSee('Multiple Fields Group');
        $view->assertSee('card-body bg-light', false);
    }

    /**
     * Test all CSS classes are applied correctly.
     */
    public function test_all_css_classes_applied_correctly(): void
    {
        $fields = $this->createTestFields();

        $view = $this->blade(
            '<x-channel-lister::panel :fields="$fields" grouping-name="Test Group" class="extra-class" :wide="true" :start-collapsed="true" />',
            ['fields' => $fields]
        );

        $view->assertSee('panel panel-default', false); // Base classes
        $view->assertSee('panel_test_group', false); // Grouping name class
        $view->assertSee('panel_wide', false); // Wide class
        $view->assertSee('panel_collapsed', false); // Collapsed class
        $view->assertSee('extra-class', false); // Custom class
    }

    /**
     * Test data toggle and href attributes for collapse functionality.
     */
    public function test_collapse_functionality_attributes(): void
    {
        $fields = $this->createTestFields();

        $view = $this->blade(
            '<x-channel-lister::panel :fields="$fields" grouping-name="Test Group" panel-id="test-panel" />',
            ['fields' => $fields]
        );

        $view->assertSee('data-toggle="collapse"', false);
        $view->assertSee('href="#panel-content-test-panel-', false);
    }

    /**
     * Test panel with all boolean options set to true.
     */
    public function test_renders_with_all_boolean_options_true(): void
    {
        $fields = $this->createTestFields();

        $view = $this->blade(
            '<x-channel-lister::panel :fields="$fields" grouping-name="Test Group" :wide="true" :start-collapsed="false" :inverted="true" />',
            ['fields' => $fields]
        );

        $view->assertSee('panel_wide', false);
        $view->assertSee('show', false); // Expanded
        $view->assertSee('card-body', false); // Inverted structure
        $view->assertDontSee('border rounded', false); // No border in inverted
    }

    /**
     * Test panel with all boolean options set to false.
     */
    public function test_renders_with_all_boolean_options_false(): void
    {
        $fields = $this->createTestFields();

        $view = $this->blade(
            '<x-channel-lister::panel :fields="$fields" grouping-name="Test Group" :wide="false" :start-collapsed="false" :inverted="false" />',
            ['fields' => $fields]
        );

        $view->assertDontSee('panel_wide', false);
        $view->assertSee('show', false); // Expanded
        $view->assertSee('border rounded', false); // Normal structure
        $view->assertSee('card-body bg-light', false); // Normal structure
    }

    /**
     * Test that duplicate wide class is not added when wide is true.
     */
    public function test_does_not_duplicate_wide_class(): void
    {
        $fields = $this->createTestFields();

        $view = $this->blade(
            '<x-channel-lister::panel :fields="$fields" grouping-name="Test Group" :wide="true" />',
            ['fields' => $fields]
        );

        $rendered = (string) $view;
        $wideClassCount = substr_count($rendered, 'panel_wide');

        // Should only appear once even though the constructor logic has it in two places
        $this->assertEquals(1, $wideClassCount, 'panel_wide class should only appear once');
    }

    /**
     * Test panel ID consistency between main panel and content panel.
     */
    public function test_panel_id_consistency(): void
    {
        $fields = $this->createTestFields();

        $view = $this->blade(
            '<x-channel-lister::panel :fields="$fields" grouping-name="Test Group" panel-id="consistency-test" />',
            ['fields' => $fields]
        );

        $rendered = (string) $view;

        // Extract ID from main panel
        preg_match('/id="panel-consistency-test-(\d+)"/', $rendered, $panelMatches);

        // Extract ID from content panel
        preg_match('/id="panel-content-consistency-test-(\d+)"/', $rendered, $contentMatches);

        // Extract href target
        preg_match('/href="#panel-content-consistency-test-(\d+)"/', $rendered, $hrefMatches);

        $this->assertNotEmpty($panelMatches, 'Main panel ID should be found');
        $this->assertNotEmpty($contentMatches, 'Content panel ID should be found');
        $this->assertNotEmpty($hrefMatches, 'Href target should be found');

        // All should have the same numeric suffix
        $this->assertEquals($panelMatches[1], $contentMatches[1], 'Panel and content should have matching ID suffixes');
        $this->assertEquals($contentMatches[1], $hrefMatches[1], 'Content ID and href target should match');
    }

    /**
     * Test minimal panel configuration.
     */
    public function test_renders_with_minimal_configuration(): void
    {
        $fields = collect([]);

        $view = $this->blade(
            '<x-channel-lister::panel :fields="$fields" grouping-name="Minimal" />',
            ['fields' => $fields]
        );

        $view->assertSee('Minimal');
        $view->assertSee('panel panel-default', false);
        $view->assertSee('panel_minimal', false);
        $view->assertSee('card-header', false);
        $view->assertSee('card-body bg-light', false);
    }
}
