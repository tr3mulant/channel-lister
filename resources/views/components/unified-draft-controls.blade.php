@php
    $isAmazonTab = isset($amazonTab) && $amazonTab === true;
@endphp

<style>
    .unified-draft-controls {
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        padding: 1.5rem;
        margin-bottom: 1rem;
    }

    .draft-status-indicator {
        display: inline-block;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        margin-right: 0.5rem;
    }

    .status-draft {
        background-color: #6c757d;
    }

    .status-validated {
        background-color: #28a745;
    }

    .status-exported {
        background-color: #007bff;
    }

    .status-error {
        background-color: #dc3545;
    }

    .draft-preview-card {
        background-color: #fff;
        border: 1px solid #dee2e6;
        border-radius: 0.25rem;
        padding: 1rem;
        margin-top: 0.5rem;
    }

    .validation-summary {
        border-radius: 0.25rem;
        padding: 0.75rem;
        margin: 0.5rem 0;
    }

    .progress-container {
        margin-top: 0.5rem;
    }

    .btn-group-unified {
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .cross-tab-indicator {
        font-size: 0.8em;
        color: #6c757d;
        margin-left: 0.5rem;
        animation: fadeIn 0.5s ease-in;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    .btn-with-badge {
        position: relative;
    }

    .btn-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background-color: #dc3545;
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        font-size: 0.7em;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .tab-data-indicator {
        font-size: 0.7em;
        background-color: #007bff;
        color: white;
        border-radius: 10px;
        padding: 1px 6px;
        margin-left: 5px;
    }
</style>

<div class="unified-draft-controls">
    <div class="row">
        <div class="col-md-12">
            <h5 class="mb-3">
                <span style="margin-right: 0.5rem;">💾</span>
                {{ $isAmazonTab ? 'Amazon Draft Management' : 'Unified Product Draft' }}
                <span class="cross-tab-indicator" id="cross-tab-indicator" style="display: none;">
                    (includes data from multiple tabs)
                </span>
            </h5>
        </div>
    </div>

    <!-- Current Draft Status -->
    <div class="row mb-3" id="current-draft-status" style="display: none;">
        <div class="col-md-12">
            <div class="alert alert-info mb-2">
                <strong>Current Draft:</strong>
                <span class="draft-status-indicator" id="current-status-indicator"></span>
                <span id="current-draft-title">Untitled Draft</span>
                <small class="ml-2" id="current-draft-meta"></small>
            </div>
        </div>
    </div>

    <!-- Draft Actions Row -->
    <div class="row mb-3">
        <!-- Load Draft Section -->
        <div class="col-md-6">
            <label class="form-label"><strong>Load Existing Draft</strong></label>
            <div class="row">
                <div class="col-8">
                    <select id="unified-draft-listing" class="form-control">
                        <option value="">Select a saved draft...</option>
                    </select>
                </div>
                <div class="col-4">
                    <div class="d-flex gap-1">
                        <button type="button" id="unified-load-draft-btn" class="btn btn-success btn-sm" disabled>
                            Load
                        </button>
                        <button type="button" id="unified-refresh-drafts-btn" class="btn btn-outline-secondary btn-sm">
                            ↻
                        </button>
                    </div>
                </div>
            </div>
            <small class="form-text text-muted">Load a previously saved draft with all marketplace data</small>
        </div>

        <!-- Quick Actions Section -->
        <div class="col-md-6">
            <label class="form-label"><strong>Quick Actions</strong></label>
            <div class="btn-group btn-group-unified d-flex" role="group">
                <button type="button" id="unified-save-draft-btn" class="btn btn-secondary btn-sm">
                    💾 Save Draft
                </button>
                <button type="button" id="unified-validate-btn" class="btn btn-warning btn-sm">
                    ✓ Validate
                </button>
                <button type="button" id="unified-export-btn" class="btn btn-primary btn-sm" disabled>
                    ⬇ Export
                </button>
            </div>
            <small class="form-text text-muted">Save, validate, and export your product data</small>
        </div>
    </div>

    <!-- Draft Preview Section -->
    <div id="unified-draft-preview" class="draft-preview-card" style="display: none;">
        <h6 class="mb-2">Draft Preview</h6>
        <div id="unified-draft-details"></div>
    </div>

    <!-- Validation Summary Section -->
    <div id="unified-validation-summary" class="validation-summary" style="display: none;">
        <div id="unified-validation-details"></div>

        <!-- Progress Bar -->
        <div class="progress-container" id="unified-progress-container" style="display: none;">
            <div class="progress" style="height: 20px;">
                <div id="unified-progress-bar" class="progress-bar" role="progressbar" style="width: 0%"></div>
            </div>
            <small id="unified-progress-text" class="text-muted"></small>
        </div>
    </div>

    <!-- Export Options (when validated) -->
    <div id="unified-export-options" class="mt-3" style="display: none;">
        <label class="form-label"><strong>Export Options</strong></label>
        <div class="btn-group" role="group">
            <button type="button" id="export-rithum-btn" class="btn btn-success btn-sm">
                📊 Rithum CSV
            </button>
            <button type="button" id="export-amazon-btn" class="btn btn-info btn-sm" style="display: none;">
                📄 Amazon JSON
            </button>
            <button type="button" id="export-all-btn" class="btn btn-primary btn-sm">
                📦 Export All
            </button>
        </div>
        <small class="form-text text-muted">Choose export format for your validated product data</small>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Global variables for unified draft management
        window.unifiedDraftSystem = {
            currentDraftId: null,
            currentStatus: 'draft',
            hasAmazonData: false,
            hasCommonData: false,

            // Initialize the unified draft system
            init: function() {
                this.bindEvents();
                this.loadDrafts();
                this.detectCrossTabData();
                this.setupFormChangeDetection();
                this.setupAutoDetection();
            },

            // Bind event handlers
            bindEvents: function() {
                $('#unified-save-draft-btn').on('click', () => this.saveDraft());
                $('#unified-validate-btn').on('click', () => this.validateDraft());
                $('#unified-export-btn').on('click', () => this.showExportOptions());
                $('#unified-load-draft-btn').on('click', () => this.loadSelectedDraft());
                $('#unified-refresh-drafts-btn').on('click', () => this.loadDrafts());
                $('#unified-draft-listing').on('change', () => this.onDraftSelectionChange());

                // Export button handlers
                $('#export-rithum-btn').on('click', () => this.exportDraft('rithum'));
                $('#export-amazon-btn').on('click', () => this.exportDraft('amazon'));
                $('#export-all-btn').on('click', () => this.exportDraft('all'));
            },

            // Detect if we have data from multiple tabs
            detectCrossTabData: function() {
                if (typeof window.getDataSummary === 'function') {
                    // Use enhanced global function
                    const summary = window.getDataSummary();
                    this.hasCommonData = summary.commonFields > 0;
                    this.hasAmazonData = summary.marketplaces.some(m => m.name === 'amazon' && m
                        .fields > 0);

                    // Update indicator with all marketplace data
                    if (summary.commonFields > 0 || summary.marketplaceFields > 0) {
                        $('#cross-tab-indicator').show();
                        let tabs = [];
                        if (summary.commonFields > 0) tabs.push(`Common (${summary.commonFields})`);
                        summary.marketplaces.forEach(m => {
                            if (m.fields > 0) {
                                tabs.push(
                                    `${m.name.charAt(0).toUpperCase() + m.name.slice(1)} (${m.fields})`
                                );
                            }
                        });
                        $('#cross-tab-indicator').text(`(includes data from: ${tabs.join(', ')})`);
                    } else {
                        $('#cross-tab-indicator').hide();
                    }
                } else {
                    // Fallback to basic detection
                    // Check for Amazon tab data
                    this.hasAmazonData = $('.amazon-generated-panel').length > 0 &&
                        $(
                            '.amazon-generated-panel input, .amazon-generated-panel select, .amazon-generated-panel textarea'
                        )
                        .filter(function() {
                            return $(this).val() !== '';
                        }).length > 0;

                    // Check for common tab data
                    this.hasCommonData = $('#common input, #common select, #common textarea')
                        .filter(function() {
                            return $(this).val() !== '';
                        }).length > 0;

                    // Update indicator
                    if (this.hasAmazonData || this.hasCommonData) {
                        $('#cross-tab-indicator').show();
                        let tabs = [];
                        if (this.hasCommonData) tabs.push('Common');
                        if (this.hasAmazonData) tabs.push('Amazon');
                        $('#cross-tab-indicator').text(`(includes data from: ${tabs.join(', ')})`);
                    } else {
                        $('#cross-tab-indicator').hide();
                    }
                }
            },

            // Collect all form data from all tabs using the enhanced global function
            collectAllFormData: function() {
                if (typeof window.collectAllTabData === 'function') {
                    const tabData = window.collectAllTabData();

                    // Convert to format expected by backend
                    const formData = {
                        common: tabData.common || {},
                        amazon: tabData.marketplaces.amazon || {},
                        marketplaces: tabData.marketplaces || {}
                    };

                    return formData;
                } else {
                    // Fallback to simple collection if enhanced function not available
                    const formData = {
                        common: {},
                        amazon: {},
                        marketplaces: {}
                    };

                    // Collect common tab data
                    $('#common input, #common select, #common textarea').each(function() {
                        const field = $(this);
                        const name = field.attr('name');
                        let value = field.val();

                        if (name && value !== '') {
                            if (field.is(':checkbox')) {
                                value = field.is(':checked') ? '1' : '0';
                            } else if (field.is(':radio') && !field.is(':checked')) {
                                return; // Skip unchecked radio buttons
                            }
                            formData.common[name] = value;
                        }
                    });

                    return formData;
                }
            },

            // Save unified draft
            saveDraft: function() {
                const formData = this.collectAllFormData();

                if (Object.keys(formData.common).length === 0 && Object.keys(formData.amazon).length ===
                    0) {
                    alert('Please fill in some form data before saving a draft.');
                    return;
                }

                this.updateButtonState('#unified-save-draft-btn', true, 'Saving...');

                $.ajax({
                    type: 'POST',
                    url: '/api/channel-lister/save-draft',
                    data: {
                        form_data: formData,
                        draft_id: this.currentDraftId
                    },
                    dataType: 'json'
                }).done((response) => {
                    this.currentDraftId = response.draft_id;
                    this.updateCurrentDraftDisplay(response);
                    this.loadDrafts(); // Refresh draft list
                    this.showMessage('success', 'Draft saved successfully!');
                }).fail((xhr) => {
                    this.showMessage('error', 'Error saving draft: ' + xhr.responseText);
                }).always(() => {
                    this.updateButtonState('#unified-save-draft-btn', false,
                        '<i class="fas fa-save"></i> Save Draft');
                });
            },

            // Load drafts list
            loadDrafts: function() {
                $.ajax({
                    type: 'GET',
                    url: '/api/channel-lister/drafts',
                    dataType: 'json'
                }).done((response) => {
                    this.populateDraftsDropdown(response.drafts);
                }).fail(() => {
                    $('#unified-draft-listing').append(
                        '<option value="" disabled>Error loading drafts</option>');
                });
            },

            // Populate drafts dropdown
            populateDraftsDropdown: function(drafts) {
                const select = $('#unified-draft-listing');
                const currentSelection = select.val();

                // Clear existing options except first
                select.find('option:not(:first)').remove();

                if (drafts && drafts.length > 0) {
                    drafts.forEach((draft) => {
                        const title = draft.title || 'Untitled Draft';
                        const createdAt = new Date(draft.created_at).toLocaleDateString();

                        // Provide default status if undefined
                        const status = draft.status || 'draft';
                        const statusText = status === 'error' ? ' [NEEDS FIX]' :
                            status === 'validated' ? ' [VALIDATED]' : ' [DRAFT]';

                        const optionText = `${title} - ${createdAt}${statusText}`;
                        const option = $('<option></option>')
                            .attr('value', draft.id)
                            .text(optionText)
                            .data('draft', draft);

                        if (status === 'error') {
                            option.addClass('status-error');
                        }

                        select.append(option);
                    });

                    // Restore selection if it still exists
                    if (currentSelection) {
                        select.val(currentSelection);
                    }
                } else {
                    select.append('<option value="" disabled>No drafts found</option>');
                }
            },

            // Handle draft selection change
            onDraftSelectionChange: function() {
                const selectedValue = $('#unified-draft-listing').val();
                const loadButton = $('#unified-load-draft-btn');
                const previewDiv = $('#unified-draft-preview');

                if (selectedValue) {
                    loadButton.prop('disabled', false);

                    const selectedOption = $('#unified-draft-listing option:selected');
                    const draft = selectedOption.data('draft');

                    if (draft) {
                        this.showDraftPreview(draft);
                        previewDiv.slideDown(300);
                    }
                } else {
                    loadButton.prop('disabled', true);
                    previewDiv.slideUp(300);
                }
            },

            // Show draft preview
            showDraftPreview: function(draft) {
                const detailsDiv = $('#unified-draft-details');
                const createdAt = new Date(draft.created_at).toLocaleString();
                const updatedAt = new Date(draft.updated_at).toLocaleString();

                // Provide default status if undefined
                const status = draft.status || 'draft';
                let statusBadge = '';
                switch (status) {
                    case 'error':
                        statusBadge = '<span class="badge badge-danger">Needs Fix</span>';
                        break;
                    case 'validated':
                        statusBadge = '<span class="badge badge-success">Validated</span>';
                        break;
                    case 'exported':
                        statusBadge = '<span class="badge badge-primary">Exported</span>';
                        break;
                    default:
                        statusBadge = '<span class="badge badge-secondary">Draft</span>';
                }

                const html = `
                <div class="row">
                    <div class="col-md-6">
                        <small><strong>Title:</strong> ${draft.title || 'Not set'}</small><br>
                        <small><strong>SKU:</strong> ${draft.sku || 'Not set'}</small><br>
                        <small><strong>Status:</strong> ${statusBadge}</small>
                    </div>
                    <div class="col-md-6">
                        <small><strong>Created:</strong> ${createdAt}</small><br>
                        <small><strong>Updated:</strong> ${updatedAt}</small><br>
                        <small><strong>Format:</strong> Unified (Multi-tab)</small>
                    </div>
                </div>
            `;

                detailsDiv.html(html);
            },


            // Load draft data into form
            loadDraftIntoForm: function(draft) {
                this.currentDraftId = draft.id;
                this.currentStatus = draft.status || 'draft';

                // Update current draft display
                this.updateCurrentDraftDisplay(draft);

                // Use enhanced global function if available
                if (typeof window.populateAllTabData === 'function') {
                    // Clear existing form data first
                    if (typeof window.clearAllForms === 'function') {
                        window.clearAllForms();
                    }

                    // Populate using enhanced function
                    window.populateAllTabData(draft.form_data);
                } else {
                    // Fallback to basic population
                    // Load common data
                    if (draft.form_data.common) {
                        this.populateTabWithData('#common', draft.form_data.common);
                    }

                    // Load Amazon data if available
                    if (draft.form_data.amazon && Object.keys(draft.form_data.amazon).length > 0) {
                        // If Amazon tab exists, populate it
                        if (typeof window.getAmazonListingRequirements === 'function' && draft.form_data
                            .amazon.product_type) {
                            $('#amazon_product_type-id').val(draft.form_data.amazon.product_type);
                            $('#amazon_product_type-searchbox').val(draft.form_data.amazon
                                .product_type);

                            // Load Amazon requirements and then populate
                            window.getAmazonListingRequirements(draft.form_data.amazon.product_type)
                                .done(() => {
                                    setTimeout(() => {
                                        this.populateTabWithData('.amazon-generated-panel',
                                            draft.form_data.amazon);
                                    }, 1000);
                                });
                        }
                    }
                }

                // Update cross-tab indicator
                setTimeout(() => {
                    this.detectCrossTabData();
                }, 1500);
            },

            // Populate tab with data
            populateTabWithData: function(selector, data) {
                Object.keys(data).forEach((fieldName) => {
                    const field = $(`${selector} [name="${fieldName}"]`);
                    const value = data[fieldName];

                    if (field.length > 0 && value !== null && value !== '') {
                        if (field.is('select')) {
                            field.val(value);
                        } else if (field.is(':checkbox')) {
                            field.prop('checked', value === 'true' || value === '1' || value ===
                                true);
                        } else if (field.is(':radio')) {
                            field.filter(`[value="${value}"]`).prop('checked', true);
                        } else {
                            field.val(value);
                        }
                        field.trigger('change');
                    }
                });
            },

            // Validate draft
            validateDraft: function() {
                if (!this.currentDraftId) {
                    alert('Please save a draft first before validating');
                    return;
                }

                this.updateButtonState('#unified-validate-btn', true, 'Validating...');

                // For now, basic validation - can be enhanced later
                const formData = this.collectAllFormData();
                const hasRequiredData = Object.keys(formData.common).length > 0;

                setTimeout(() => {
                    if (hasRequiredData) {
                        this.currentStatus = 'validated';
                        this.showValidationSummary({
                            success: true,
                            message: 'All validations passed!',
                            completion_percentage: 100
                        });
                        $('#unified-export-btn').prop('disabled', false);
                        this.showMessage('success', 'Draft validated successfully!');
                    } else {
                        this.showValidationSummary({
                            success: false,
                            message: 'Please fill in required fields',
                            completion_percentage: 25
                        });
                        this.showMessage('warning', 'Validation failed - please review errors');
                    }

                    this.updateButtonState('#unified-validate-btn', false, '✓ Validate');
                }, 1000);
            },

            // Show validation summary
            showValidationSummary: function(summary) {
                const summaryDiv = $('#unified-validation-summary');
                const detailsDiv = $('#unified-validation-details');

                if (summary.success) {
                    detailsDiv.html('<h6 class="text-success">✓ ' + summary.message + '</h6>');
                    summaryDiv.removeClass('alert-danger alert-warning').addClass('alert-success');
                } else {
                    detailsDiv.html('<h6 class="text-danger">⚠ ' + summary.message + '</h6>');
                    summaryDiv.removeClass('alert-success').addClass('alert-warning');
                }

                if (summary.completion_percentage !== undefined) {
                    this.updateProgress(summary.completion_percentage);
                }

                summaryDiv.show();
            },

            // Update progress bar
            updateProgress: function(percentage) {
                $('#unified-progress-container').show();
                $('#unified-progress-bar').css('width', percentage + '%').attr('aria-valuenow',
                    percentage);
                $('#unified-progress-text').text(`${percentage}% complete`);
            },

            // Show export options
            showExportOptions: function() {
                $('#unified-export-options').slideDown(300);

                // Show Amazon export option if we have Amazon data
                if (this.hasAmazonData) {
                    $('#export-amazon-btn').show();
                }
            },

            // Export draft
            exportDraft: function(format) {
                if (!this.currentDraftId) {
                    alert('Please save and validate a draft first');
                    return;
                }

                const btn = $(`#export-${format}-btn`);
                this.updateButtonState(btn, true, 'Exporting...');

                $.ajax({
                    type: 'POST',
                    url: `/api/channel-lister/export-draft/${this.currentDraftId}`,
                    data: {
                        format: format
                    },
                    dataType: 'json'
                }).done((response) => {
                    if (response.success) {
                        this.showMessage('success', 'Export completed successfully!');

                        // Handle download links
                        if (response.exports.rithum) {
                            window.open(response.exports.rithum.download_url, '_blank');
                        }
                    } else {
                        this.showMessage('error', 'Export failed: ' + response.message);
                    }
                }).fail((xhr) => {
                    this.showMessage('error', 'Export error: ' + xhr.responseText);
                }).always(() => {
                    this.updateButtonState(btn, false, btn.data('original-text') || 'Export');
                });
            },

            // Update current draft display
            updateCurrentDraftDisplay: function(draft) {
                $('#current-draft-status').show();
                $('#current-draft-title').text(draft.title || 'Untitled Draft');

                // Provide default status if undefined
                const status = draft.status || 'draft';
                $('#current-draft-meta').text(`ID: ${draft.id} | ${status.toUpperCase()}`);

                const indicator = $('#current-status-indicator');
                indicator.removeClass('status-draft status-validated status-exported status-error');
                indicator.addClass(`status-${status}`);
            },

            // Utility: Update button state
            updateButtonState: function(selector, disabled, text) {
                const btn = $(selector);
                if (!btn.data('original-text')) {
                    btn.data('original-text', btn.html());
                }
                btn.prop('disabled', disabled).html(text);
            },

            // Utility: Show message
            showMessage: function(type, message) {
                // Enhanced notification system
                if (type === 'success') {
                    this.showToast(message, 'success');
                } else if (type === 'error') {
                    this.showToast(message, 'error');
                    console.error('Error:', message);
                } else if (type === 'warning') {
                    this.showToast(message, 'warning');
                    console.warn('Warning:', message);
                }
            },

            // Show toast notification
            showToast: function(message, type) {
                // Create toast element if it doesn't exist
                if ($('#unified-toast-container').length === 0) {
                    $('body').append(`
                    <div id="unified-toast-container" style="position: fixed; top: 20px; right: 20px; z-index: 9999;"></div>
                `);
                }

                const toastClass = type === 'success' ? 'alert-success' :
                    type === 'error' ? 'alert-danger' : 'alert-warning';
                const icon = type === 'success' ? '✅' :
                    type === 'error' ? '❌' : '⚠️';

                const toast = $(`
                <div class="alert ${toastClass} alert-dismissible fade show" role="alert" style="margin-bottom: 10px; min-width: 300px;">
                    <span style="margin-right: 0.5rem;">${icon}</span>${message}
                    <button type="button" class="close" data-dismiss="alert">
                        <span>&times;</span>
                    </button>
                </div>
            `);

                $('#unified-toast-container').append(toast);

                // Auto-dismiss after 5 seconds
                setTimeout(() => {
                    toast.fadeOut(500, () => toast.remove());
                }, 5000);
            },

            // Setup form change detection
            setupFormChangeDetection: function() {
                // Track changes to any form field
                $(document).on('change input', 'form input, form select, form textarea', () => {
                    // Debounced detection update
                    clearTimeout(this.changeDetectionTimer);
                    this.changeDetectionTimer = setTimeout(() => {
                        this.detectCrossTabData();
                        this.updateFormStatus();
                    }, 500);
                });

                // Track when Amazon product type is loaded
                $(document).on('DOMNodeInserted', '.amazon-generated-panel', () => {
                    setTimeout(() => this.detectCrossTabData(), 1000);
                });
            },

            // Setup auto-detection for various events
            setupAutoDetection: function() {
                // Check data when tabs are switched
                $('.nav-tabs a').on('shown.bs.tab', () => {
                    setTimeout(() => this.detectCrossTabData(), 200);
                });

                // Periodic check for changes (every 10 seconds)
                setInterval(() => {
                    this.detectCrossTabData();
                }, 10000);
            },

            // Update form status indicators
            updateFormStatus: function() {
                if (typeof window.hasSignificantData === 'function') {
                    const hasData = window.hasSignificantData();

                    // Enable/disable save button based on data
                    $('#unified-save-draft-btn').prop('disabled', !hasData);

                    // Update button text to show data status
                    if (hasData) {
                        const summary = window.getDataSummary();
                        const totalFields = summary.commonFields + summary.marketplaceFields;
                        $('#unified-save-draft-btn').html(`💾 Save Draft (${totalFields} fields)`);
                    } else {
                        $('#unified-save-draft-btn').html('💾 Save Draft');
                    }
                }
            },

            // Enhanced draft loading with better feedback
            loadSelectedDraft: function() {
                const selectedDraftId = $('#unified-draft-listing').val();

                if (!selectedDraftId) {
                    this.showMessage('warning', 'Please select a draft to load');
                    return;
                }

                this.updateButtonState('#unified-load-draft-btn', true, '⏳ Loading...');

                $.ajax({
                    type: 'GET',
                    url: `/api/channel-lister/drafts/${selectedDraftId}`,
                    dataType: 'json'
                }).done((response) => {
                    if (response.success && response.draft) {
                        this.loadDraftIntoForm(response.draft);
                        this.showMessage('success',
                            `Draft "${response.draft.title || 'Untitled'}" loaded successfully!`
                        );

                        // Auto-switch to first tab with data
                        this.switchToRelevantTab(response.draft);
                    } else {
                        this.showMessage('error', 'Failed to load draft: Invalid response');
                    }
                }).fail((xhr) => {
                    this.showMessage('error', 'Failed to load draft: ' + (xhr.responseJSON
                        ?.message || xhr.responseText));
                }).always(() => {
                    this.updateButtonState('#unified-load-draft-btn', false, 'Load');
                });
            },

            // Switch to the most relevant tab when loading a draft
            switchToRelevantTab: function(draft) {
                setTimeout(() => {
                    if (draft.form_data.amazon && Object.keys(draft.form_data.amazon).length >
                        0) {
                        // If there's Amazon data and Amazon tab exists, switch to it
                        if ($('#liamazon').length > 0) {
                            $('a[href="#amazon"]').tab('show');
                        }
                    } else if (draft.form_data.common && Object.keys(draft.form_data.common)
                        .length > 0) {
                        // Otherwise switch to common tab
                        $('a[href="#common"]').tab('show');
                    }

                    // Update tab indicators
                    this.updateTabIndicators();
                }, 1500);
            },

            // Update tab navigation with data indicators
            updateTabIndicators: function() {
                if (typeof window.getDataSummary === 'function') {
                    const summary = window.getDataSummary();

                    // Update common tab indicator
                    const commonTab = $('#licommon a');
                    commonTab.find('.tab-data-indicator').remove();
                    if (summary.commonFields > 0) {
                        commonTab.append(
                            `<span class="tab-data-indicator">${summary.commonFields}</span>`);
                    }

                    // Update marketplace tab indicators
                    summary.marketplaces.forEach(marketplace => {
                        if (marketplace.fields > 0) {
                            const tabId = `#li${marketplace.name}`;
                            const tab = $(tabId + ' a');
                            if (tab.length > 0) {
                                tab.find('.tab-data-indicator').remove();
                                tab.append(
                                    `<span class="tab-data-indicator">${marketplace.fields}</span>`
                                );
                            }
                        }
                    });
                }
            },

            // Enhanced form status with visual feedback
            updateFormStatus: function() {
                if (typeof window.hasSignificantData === 'function') {
                    const hasData = window.hasSignificantData();
                    const summary = window.getDataSummary();

                    // Enable/disable save button based on data
                    $('#unified-save-draft-btn').prop('disabled', !hasData);

                    // Update button text to show data status
                    if (hasData) {
                        const totalFields = summary.commonFields + summary.marketplaceFields;
                        $('#unified-save-draft-btn').html(`💾 Save Draft (${totalFields} fields)`);

                        // Add badge to save button
                        $('#unified-save-draft-btn').addClass('btn-with-badge');
                        $('#unified-save-draft-btn .btn-badge').remove();
                        $('#unified-save-draft-btn').append(
                            `<span class="btn-badge">${totalFields}</span>`);
                    } else {
                        $('#unified-save-draft-btn').html('💾 Save Draft');
                        $('#unified-save-draft-btn').removeClass('btn-with-badge');
                        $('#unified-save-draft-btn .btn-badge').remove();
                    }

                    // Update tab indicators
                    this.updateTabIndicators();
                }
            }
        };

        // Initialize the unified draft system
        window.unifiedDraftSystem.init();
    });
</script>
