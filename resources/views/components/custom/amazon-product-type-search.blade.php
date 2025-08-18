@php
    $element_name = $params->field_name;
    $id = $element_name . '-id';
    $tooltip = $params->tooltip ?? '';
    $placeholder = $params->example ?? 'Enter Amazon product type...';
    $maps_to_text = "Maps to {$params->field_name}";
@endphp

<style>
    .focused-field {
        box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25) !important;
        border-color: #007bff !important;
        transition: all 0.3s ease-in-out !important;
    }
    
    .focused-field:focus {
        box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.4) !important;
    }
    
    /* Styling for error status drafts in dropdown */
    .error-status {
        background-color: #fff5f5 !important;
        color: #dc3545 !important;
    }
</style>

<script>
    var removedAmazonFields = [];

    function getAmazonListingRequirements(productType) {
        return $.ajax({
            type: 'POST',
            url: {{ Illuminate\Support\Js::from($requirementsApiUrl) }},
            data: {
                'product_type': productType
            },
            dataType: 'json'
        }).done(function(response) {
            let html = response.data.html;
            let remove = response.data.remove_attributes;
            // Find the panel container that holds the Amazon Product Type Search panel
            // Navigate up to find the container that holds multiple panels
            let panelContainer = $('#amazon_product_type-id').closest('.panel').parent();

            // Remove previous Amazon panels with animation if they exist
            if ($('.amazon-generated-panel').length > 0) {
                $('.amazon-generated-panel').slideUp(300, function() {
                    $(this).remove();
                });
                // Wait for removal animation to complete before adding new panels
                setTimeout(function() {
                    addNewPanelsWithAnimation();
                }, 320);
            } else {
                // No existing panels, add new ones immediately
                addNewPanelsWithAnimation();
            }
            
        function addNewPanelsWithAnimation() {

            // Parse the HTML and add amazon-generated-panel class to each panel
            let htmlWithClass = html.replace(/class="border rounded panel panel-default/g,
                'class="border rounded panel panel-default amazon-generated-panel');

            // Create a temporary container to hold the new panels
            let tempContainer = $('<div>').html(htmlWithClass);
            let newPanels = tempContainer.children('.amazon-generated-panel');
            
            // Hide the panels initially and append them to the container
            newPanels.hide();
            panelContainer.append(newPanels);
            
            // Animate each panel sliding down with a staggered delay
            newPanels.each(function(index) {
                $(this).delay(index * 150).slideDown(400);
            });

            // Calculate total animation time: (number of panels - 1) * stagger delay + slide duration
            let totalAnimationTime = ((newPanels.length - 1) * 150) + 400;
            
            // After all panels have finished animating, scroll to and focus the first form field
            setTimeout(function() {
                scrollToAndFocusFirstField();
            }, totalAnimationTime + 100); // Add small buffer for animation completion

            // Store current product type and show form controls
            currentProductType = productType;
            showFormControls();
        }
        }).fail(function(response) {
            console.error('Failed to get Amazon listing requirements:', response);
            alert('Failed to load listing requirements: ' + (response.responseJSON?.error || response
                .responseText));
        });
    }

    function getAmazonExistingListing(identifier, identifierType) {
        return $.ajax({
            type: 'POST',
            url: {{ Illuminate\Support\Js::from($existingListingApiUrl) }},
            data: {
                'identifier': identifier,
                'identifier_type': identifierType
            },
            dataType: 'json'
        }).done(function(response) {
            let listing = response.data.listing;
            let formFields = response.data.form_fields;
            let productType = response.data.product_type;

            // Pre-populate form with existing listing data
            if (listing && listing.attributes) {
                populateFormWithExistingData(listing.attributes);
            }

            // Set the product type if found
            if (productType) {
                $('#amazon_product_type-id').val(productType);
                $('#amazon_product_type-searchbox').val(productType);
                getAmazonListingRequirements(productType);
            }

            alert('Existing listing loaded successfully!');
        }).fail(function(response) {
            console.error('Failed to get existing listing:', response);
            alert('Failed to load existing listing: ' + (response.responseJSON?.error || 'Listing not found'));
        });
    }

    function populateFormWithExistingData(attributes) {
        // Iterate through attributes and populate form fields
        Object.keys(attributes).forEach(function(key) {
            let field = $('[name="' + key + '"]');
            if (field.length > 0 && attributes[key] && attributes[key].length > 0) {
                let value = attributes[key][0].value;
                if (field.is('select')) {
                    field.val(value);
                } else if (field.is(':checkbox')) {
                    field.prop('checked', value === 'true' || value === '1');
                } else {
                    field.val(value);
                }
            }
        });
    }

    function scrollToAndFocusFirstField() {
        // Find the first form input element in the Amazon generated panels
        let firstField = $('.amazon-generated-panel').first().find('input, select, textarea').first();
        
        if (firstField.length > 0) {
            // Calculate scroll position with some offset above the field for better UX
            let scrollOffset = firstField.offset().top - 100; // 100px above the field
            
            // Smooth scroll to the field
            $('html, body').animate({
                scrollTop: scrollOffset
            }, 600, function() {
                // After scroll completes, focus the field with a slight delay for better visual effect
                setTimeout(function() {
                    firstField.focus();
                    
                    // Add a subtle highlight effect to draw attention
                    firstField.addClass('focused-field');
                    setTimeout(function() {
                        firstField.removeClass('focused-field');
                    }, 2000); // Remove highlight after 2 seconds
                }, 200);
            });
            
            console.log('Auto-scrolled to and focused first field:', firstField.attr('name') || firstField.attr('id'));
        } else {
            // If no form fields found, just scroll to the first panel
            let firstPanel = $('.amazon-generated-panel').first();
            if (firstPanel.length > 0) {
                let scrollOffset = firstPanel.offset().top - 50;
                $('html, body').animate({
                    scrollTop: scrollOffset
                }, 600);
                console.log('No form fields found, scrolled to first panel');
            }
        }
    }
</script>

<div class="amazon-listing-section">
    <!-- Product Type Search Section -->
    <div @class(['form-group', 'required' => $params->required]) @required($params->required)>
        <div class="container">
            <div class="row">
                <label class="col-form-label"
                    for="{{ $element_name }}-searchbox">{{ $params->display_name ?? $params->field_name }}</label>
            </div>
            <div class="row">
                <div class="col-sm-4">
                    <input type="text" class="form-control" id="{{ $element_name }}-searchbox"
                        placeholder="Search for product type...">
                </div>
                <div class="col-sm-8">
                    <div class="row">
                        <input type="text" name="{{ $element_name }}" @class(['form-control', 'required' => $params->required])
                            id="{{ $id }}" placeholder="{{ $placeholder }}" @required($params->required)>
                        <p class="form-text">Maps To: <code>{{ $params->field_name }}</code></p>
                    </div>
                </div>
            </div>
        </div>

        <script>
            $('#{{ $element_name }}-searchbox').focusout(function() {
                $('#{{ $element_name }}-matches').slideUp(300);
            });

            $('#{{ $element_name }}-searchbox').on('focusin click', function() {
                // Only show if there are results and not currently animating
                if ($('#{{ $element_name }}-matches').children().length > 0 && !$('#{{ $element_name }}-matches').is(':animated')) {
                    $('#{{ $element_name }}-matches').slideDown(300);
                }
            });

            // Debounce timer for search input
            var searchTimer;

            $("#{{ $element_name }}-searchbox").on('keyup', function(e) {
                if (e.keyCode === 27 /*escape key*/ ) {
                    $('#{{ $element_name }}-searchbox').focusout();
                    return;
                }

                // Clear the previous timer
                clearTimeout(searchTimer);

                // Set a new timer to execute the search after 300ms delay
                searchTimer = setTimeout(function() {
                    executeQuery();
                }, 300);

                function executeQuery() {
                    var query = $("#{{ $element_name }}-searchbox").val();
                    if (query.length >= 3) {
                        $.ajax({
                            type: 'POST',
                            url: {{ Illuminate\Support\Js::from($apiUrl) }},
                            data: {
                                'query': query
                            },
                            dataType: 'json'
                        }).done(function(response) {
                            var table_html = response.data;
                            var matchesContainer = $("div#{{ $element_name }}-matches");
                            
                            // Stop any current animations and update content
                            matchesContainer.stop(true, true).html(table_html);
                            
                            // Only slide down if searchbox is focused and not already visible
                            if ($('#{{ $element_name }}-searchbox').is(':focus') && !matchesContainer.is(':visible')) {
                                matchesContainer.slideDown(300);
                            } else if ($('#{{ $element_name }}-searchbox').is(':focus')) {
                                matchesContainer.show(); // Just show if already focused and not animating
                            }

                            $("#{{ $element_name }}-matches tr").mousedown(function() {
                                var productType = $(this).children().first().text();
                                var productTypeName = $(this).children().next().text();

                                if (productType !== '-1' && productType !== '') {
                                    $("#{{ $id }}").val(productType);
                                    $("#{{ $element_name }}-searchbox").val(productTypeName);

                                    // Smooth fade out with content clearing
                                    $('#{{ $element_name }}-matches').fadeOut(200, function() {
                                        $(this).empty();
                                    });

                                    // Fetch listing requirements for this product type
                                    getAmazonListingRequirements(productType);
                                }

                                $("#{{ $id }}").trigger('change');
                            });

                            $("#{{ $element_name }}-searchbox").focus();
                        }).fail(function(response) {
                            console.error('Product type search failed:', response);
                            alert('Product type search failed: ' + response.responseText);
                        });
                    }
                }
            });
        </script>

        <br class="clearfloat">
        <div id="{{ $element_name }}-matches" class="cat_results_wrapper" style="display: none;">
        </div>
    </div>

    <!-- Existing Listing Lookup Section -->
    <div class="form-group">
        <div class="">
            <label class="col-form-label">Or lookup existing Amazon listing</label>
        </div>
        <div class="row">
            <div class="col-sm-3">
                <select id="amazon-identifier-type" class="form-control">
                    <option value="ASIN">ASIN</option>
                    <option value="GTIN">GTIN</option>
                    <option value="UPC">UPC</option>
                    <option value="EAN">EAN</option>
                    <option value="ISBN">ISBN</option>
                </select>
            </div>
            <div class="col-sm-6">
                <input type="text" id="amazon-identifier" class="form-control"
                    placeholder="Enter identifier value...">
            </div>
            <div class="col-sm-3">
                <button type="button" id="amazon-lookup-btn" class="btn btn-primary">Lookup Listing</button>
            </div>
        </div>
        <p class="form-text">Search for an existing Amazon listing to pre-populate fields</p>

        <script>
            $('#amazon-lookup-btn').click(function() {
                var identifier = $('#amazon-identifier').val().trim();
                var identifierType = $('#amazon-identifier-type').val();

                if (!identifier) {
                    alert('Please enter an identifier value');
                    return;
                }

                $(this).prop('disabled', true).text('Looking up...');

                getAmazonExistingListing(identifier, identifierType)
                    .always(function() {
                        $('#amazon-lookup-btn').prop('disabled', false).text('Lookup Listing');
                    });
            });

            // Allow Enter key to trigger lookup
            $('#amazon-identifier').keypress(function(e) {
                if (e.which === 13) {
                    $('#amazon-lookup-btn').click();
                }
            });
        </script>
    </div>

    <!-- Draft Listings Section -->
    <div class="form-group">
        <div class="">
            <label class="col-form-label">Or load a saved draft</label>
        </div>
        <div class="row">
            <div class="col-sm-8">
                <select id="amazon-draft-listing" class="form-control">
                    <option value="">Select a saved draft...</option>
                </select>
            </div>
            <div class="col-sm-2">
                <button type="button" id="amazon-load-draft-btn" class="btn btn-success" disabled>Load Draft</button>
            </div>
            <div class="col-sm-2">
                <button type="button" id="amazon-refresh-drafts-btn" class="btn btn-outline-secondary">Refresh</button>
            </div>
        </div>
        <p class="form-text">Load a previously saved draft to continue working on it</p>
        <div id="draft-preview" class="mt-2" style="display: none;">
            <div class="card border-info">
                <div class="card-body p-3">
                    <h6 class="card-title mb-1">Draft Preview</h6>
                    <div id="draft-details"></div>
                </div>
            </div>
        </div>

        <script>
            // Load drafts when the page loads
            $(document).ready(function() {
                loadAmazonDrafts();
            });

            function loadAmazonDrafts() {
                // Load both draft and error status listings so users can fix validation issues
                var draftPromise = $.ajax({
                    type: 'GET',
                    url: '/api/amazon-listing/listings?status=draft&per_page=50',
                    dataType: 'json'
                });
                
                var errorPromise = $.ajax({
                    type: 'GET',
                    url: '/api/amazon-listing/listings?status=error&per_page=50',
                    dataType: 'json'
                });
                
                // Wait for both requests to complete
                $.when(draftPromise, errorPromise).done(function(draftResponse, errorResponse) {
                    // Combine results from both API calls
                    var draftListings = draftResponse[0].listings || [];
                    var errorListings = errorResponse[0].listings || [];
                    var allListings = draftListings.concat(errorListings);
                    
                    // Sort by updated_at descending (most recent first)
                    allListings.sort(function(a, b) {
                        return new Date(b.updated_at) - new Date(a.updated_at);
                    });
                    
                    populateDraftsDropdown(allListings);
                }).fail(function() {
                    console.error('Failed to load drafts');
                    $('#amazon-draft-listing').append('<option value="" disabled>Error loading drafts</option>');
                });
            }
            
            function populateDraftsDropdown(listings) {
                var draftsSelect = $('#amazon-draft-listing');
                var currentSelection = draftsSelect.val(); // Preserve current selection if any
                    
                // Clear existing options except the first one
                draftsSelect.find('option:not(:first)').remove();
                
                if (listings && listings.length > 0) {
                    listings.forEach(function(listing) {
                        var title = listing.title || listing.product_type || 'Untitled Draft';
                        var createdAt = new Date(listing.created_at).toLocaleDateString();
                        var completionText = '';
                        var statusText = '';
                        
                        // Add completion info if available
                        if (listing.completion_percentage !== undefined) {
                            completionText = ` (${listing.completion_percentage}% complete)`;
                        }
                        
                        // Add status indicator
                        if (listing.status === 'error') {
                            statusText = ' [NEEDS FIX]';
                        } else if (listing.status === 'draft') {
                            statusText = ' [DRAFT]';
                        }
                        
                        var optionText = `${title} - ${createdAt}${completionText}${statusText}`;
                        var option = $('<option></option>')
                            .attr('value', listing.id)
                            .text(optionText)
                            .data('listing', listing);
                        
                        // Add CSS class for styling based on status
                        if (listing.status === 'error') {
                            option.addClass('error-status');
                        }
                        
                        draftsSelect.append(option);
                    });
                    
                    // Restore selection if it still exists
                    if (currentSelection) {
                        draftsSelect.val(currentSelection);
                    }
                    
                    console.log('Loaded', listings.length, 'draft and error listings');
                } else {
                    draftsSelect.append('<option value="" disabled>No drafts found</option>');
                }
            }

            // Handle draft selection change
            $('#amazon-draft-listing').change(function() {
                var selectedValue = $(this).val();
                var loadButton = $('#amazon-load-draft-btn');
                var previewDiv = $('#draft-preview');
                
                if (selectedValue) {
                    loadButton.prop('disabled', false);
                    
                    // Show preview of selected draft
                    var selectedOption = $(this).find('option:selected');
                    var listing = selectedOption.data('listing');
                    
                    if (listing) {
                        showDraftPreview(listing);
                        previewDiv.slideDown(300);
                    }
                } else {
                    loadButton.prop('disabled', true);
                    previewDiv.slideUp(300);
                }
            });

            function showDraftPreview(listing) {
                var detailsDiv = $('#draft-details');
                var createdAt = new Date(listing.created_at).toLocaleString();
                var updatedAt = new Date(listing.updated_at).toLocaleString();
                
                // Determine status badge styling
                var statusBadge = '';
                if (listing.status === 'error') {
                    statusBadge = '<span class="badge badge-danger">Needs Fix</span>';
                } else if (listing.status === 'draft') {
                    statusBadge = '<span class="badge badge-secondary">Draft</span>';
                } else {
                    statusBadge = `<span class="badge badge-info">${listing.status}</span>`;
                }
                
                var html = `
                    <div class="row">
                        <div class="col-md-6">
                            <small><strong>Product Type:</strong> ${listing.product_type || 'Not set'}</small><br>
                            <small><strong>Status:</strong> ${statusBadge}</small><br>
                            <small><strong>Created:</strong> ${createdAt}</small>
                        </div>
                        <div class="col-md-6">
                            <small><strong>Title:</strong> ${listing.title || 'Not set'}</small><br>
                            <small><strong>SKU:</strong> ${listing.sku || 'Not set'}</small><br>
                            <small><strong>Last Updated:</strong> ${updatedAt}</small>
                        </div>
                    </div>
                `;
                
                // Add error details section if this is an error status listing
                if (listing.status === 'error' && listing.validation_errors) {
                    html += `
                        <div class="row mt-2">
                            <div class="col-md-12">
                                <div class="alert alert-danger p-2">
                                    <small><strong>⚠️ Validation Issues:</strong></small><br>
                                    <small class="text-muted">This draft has validation errors that need to be fixed:</small>
                                    <ul class="mb-0 mt-1" style="font-size: 0.8em;">
                    `;
                    
                    // Display validation errors
                    if (typeof listing.validation_errors === 'object') {
                        Object.entries(listing.validation_errors).forEach(([field, error]) => {
                            html += `<li><strong>${field}:</strong> ${error}</li>`;
                        });
                    } else if (typeof listing.validation_errors === 'string') {
                        html += `<li>${listing.validation_errors}</li>`;
                    }
                    
                    html += `
                                    </ul>
                                </div>
                            </div>
                        </div>
                    `;
                } else if (listing.status === 'draft') {
                    // Show encouraging message for clean drafts
                    html += `
                        <div class="row mt-2">
                            <div class="col-md-12">
                                <div class="alert alert-info p-2">
                                    <small><strong>✓ Ready to Continue:</strong> This draft is ready for editing and validation.</small>
                                </div>
                            </div>
                        </div>
                    `;
                }
                
                detailsDiv.html(html);
            }

            // Handle load draft button click
            $('#amazon-load-draft-btn').click(function() {
                var selectedListingId = $('#amazon-draft-listing').val();
                
                if (!selectedListingId) {
                    alert('Please select a draft to load');
                    return;
                }

                $(this).prop('disabled', true).text('Loading...');

                $.ajax({
                    type: 'GET',
                    url: `/api/amazon-listing/listings/${selectedListingId}`,
                    dataType: 'json'
                }).done(function(response) {
                    if (response.listing) {
                        loadDraftIntoForm(response.listing);
                        alert('Draft loaded successfully!');
                    } else {
                        alert('Failed to load draft: Invalid response');
                    }
                }).fail(function(xhr) {
                    console.error('Failed to load draft:', xhr);
                    alert('Failed to load draft: ' + (xhr.responseJSON?.message || xhr.responseText));
                }).always(function() {
                    $('#amazon-load-draft-btn').prop('disabled', false).text('Load Draft');
                });
            });

            // Handle refresh drafts button
            $('#amazon-refresh-drafts-btn').click(function() {
                $(this).prop('disabled', true).text('Refreshing...');
                
                loadAmazonDrafts();
                
                setTimeout(function() {
                    $('#amazon-refresh-drafts-btn').prop('disabled', false).text('Refresh');
                }, 1000);
            });

            function loadDraftIntoForm(listing) {
                // Set the current listing ID for future saves
                currentListingId = listing.id;
                
                // Set product type and marketplace
                currentProductType = listing.product_type;
                currentMarketplaceId = listing.marketplace_id;
                
                // Update the product type fields
                $('#amazon_product_type-id').val(listing.product_type);
                $('#amazon_product_type-searchbox').val(listing.product_type);
                
                // Load the form requirements and then populate with saved data
                getAmazonListingRequirements(listing.product_type).done(function() {
                    // Wait a moment for panels to be created, then populate the form
                    setTimeout(function() {
                        populateFormWithDraftData(listing.form_data);
                        showFormControls();
                        updateListingStatus(listing.status);
                        
                        // Handle error status drafts specifically
                        if (listing.status === 'error') {
                            // Show validation errors immediately for error status drafts
                            if (listing.validation_errors) {
                                var validationSummary = {
                                    validation_errors: listing.validation_errors,
                                    missing_required_fields: [],
                                    completion_percentage: listing.completion_percentage || 0,
                                    completed_fields: 0,
                                    total_fields: 0
                                };
                                updateValidationSummary(validationSummary);
                            }
                            
                            // Show helpful message for error status
                            setTimeout(function() {
                                alert('⚠️ This draft has validation errors that need to be fixed. Please review the errors shown below and correct them before validating again.');
                            }, 500);
                        } else {
                            // Get validation summary if available for regular drafts
                            if (listing.validation_summary) {
                                updateValidationSummary(listing.validation_summary);
                            }
                        }
                        
                        console.log('Draft loaded and form populated. Status:', listing.status);
                    }, 1000); // Wait for animations to complete
                });
            }

            function populateFormWithDraftData(formData) {
                if (!formData) return;
                
                // Iterate through saved form data and populate fields
                Object.keys(formData).forEach(function(fieldName) {
                    var field = $('[name="' + fieldName + '"]');
                    var value = formData[fieldName];
                    
                    if (field.length > 0 && value !== null && value !== '') {
                        if (field.is('select')) {
                            field.val(value);
                        } else if (field.is(':checkbox')) {
                            field.prop('checked', value === 'true' || value === '1' || value === true);
                        } else if (field.is(':radio')) {
                            field.filter('[value="' + value + '"]').prop('checked', true);
                        } else {
                            field.val(value);
                        }
                        
                        // Trigger change event for any dependent logic
                        field.trigger('change');
                    }
                });
                
                console.log('Populated form with draft data:', Object.keys(formData).length, 'fields');
            }
        </script>
    </div>

    <!-- Dynamic Amazon panels will be appended to the tab level -->

    <!-- Form submission controls -->
    <div class="form-submission-controls" id="amazon-form-controls" style="display: none;">
        <div class="row mt-4">
            <div class="col-md-12">
                <h5>Listing Actions</h5>

                <!-- Validation Summary -->
                <div id="validation-summary" class="alert" style="display: none;">
                    <div id="validation-details"></div>
                </div>

                <!-- Action Buttons -->
                <div class="btn-group" role="group">
                    <button type="button" id="save-draft-btn" class="btn btn-secondary">Save Draft</button>
                    <button type="button" id="validate-listing-btn" class="btn btn-warning">Validate</button>
                    <button type="button" id="generate-csv-btn" class="btn btn-success" disabled>Generate CSV</button>
                    <button type="button" id="generate-json-btn" class="btn btn-info" disabled>Generate JSON</button>
                </div>

                <!-- Status Display -->
                <div id="listing-status" class="mt-3" style="display: none;">
                    <strong>Status: </strong><span id="status-text"></span>
                    <div id="progress-bar-container" class="mt-2" style="display: none;">
                        <div class="progress">
                            <div id="progress-bar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                        </div>
                        <small id="progress-text" class="text-muted"></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    var currentListingId = null;
    var currentProductType = null;
    var currentMarketplaceId = 'ATVPDKIKX0DER'; // Default US marketplace

    function showFormControls() {
        $('#amazon-form-controls').show();
    }

    function hideFormControls() {
        $('#amazon-form-controls').hide();
    }

    function updateValidationSummary(summary) {
        const summaryDiv = $('#validation-summary');
        const detailsDiv = $('#validation-details');

        if (summary.validation_errors && Object.keys(summary.validation_errors).length > 0) {
            let errorHtml = '<h6>Validation Errors:</h6><ul>';
            Object.entries(summary.validation_errors).forEach(([field, error]) => {
                errorHtml += `<li><strong>${field}:</strong> ${error}</li>`;
            });
            errorHtml += '</ul>';

            detailsDiv.html(errorHtml);
            summaryDiv.removeClass('alert-success').addClass('alert-danger').show();
        } else if (summary.missing_required_fields && summary.missing_required_fields.length > 0) {
            let missingHtml = '<h6>Missing Required Fields:</h6><ul>';
            summary.missing_required_fields.forEach(field => {
                missingHtml += `<li>${field}</li>`;
            });
            missingHtml += '</ul>';

            detailsDiv.html(missingHtml);
            summaryDiv.removeClass('alert-success').addClass('alert-warning').show();
        } else {
            detailsDiv.html('<h6>✓ All validations passed!</h6>');
            summaryDiv.removeClass('alert-danger alert-warning').addClass('alert-success').show();
        }

        // Update progress
        if (summary.completion_percentage !== undefined) {
            updateProgress(summary.completion_percentage, summary.completed_fields, summary.total_fields);
        }
    }

    function updateProgress(percentage, completed, total) {
        $('#progress-bar-container').show();
        $('#progress-bar').css('width', percentage + '%').attr('aria-valuenow', percentage);
        $('#progress-text').text(`${completed} of ${total} fields completed (${percentage}%)`);
    }

    function updateListingStatus(status) {
        $('#listing-status').show();
        $('#status-text').text(status);

        // Enable/disable buttons based on status
        if (status === 'validated') {
            $('#generate-csv-btn, #generate-json-btn').prop('disabled', false);
        } else {
            $('#generate-csv-btn, #generate-json-btn').prop('disabled', true);
        }
    }

    function collectFormData() {
        const formData = {};

        // Collect all form fields from the Amazon generated panels
        $('.amazon-generated-panel input, .amazon-generated-panel select, .amazon-generated-panel textarea').each(
            function() {
                const field = $(this);
                const name = field.attr('name');
                let value = field.val();

                if (field.is(':checkbox')) {
                    value = field.is(':checked') ? '1' : '0';
                }

                if (name && value !== '') {
                    formData[name] = value;
                }
            });

        // Add product type and marketplace
        formData['product_type'] = currentProductType;
        formData['marketplace_id'] = currentMarketplaceId;

        return formData;
    }

    // Event handlers
    $('#save-draft-btn').click(function() {
        const formData = collectFormData();

        if (!currentProductType) {
            alert('Please select a product type first');
            return;
        }

        $(this).prop('disabled', true).text('Saving...');

        $.ajax({
            type: 'POST',
            url: 'api/amazon-listing/submit',
            data: {
                product_type: currentProductType,
                marketplace_id: currentMarketplaceId,
                form_data: formData,
                listing_id: currentListingId
            },
            dataType: 'json'
        }).done(function(response) {
            if (response.success) {
                currentListingId = response.listing_id;
                updateListingStatus(response.status);
                updateValidationSummary(response.validation_summary);
                alert('Draft saved successfully!');
                
                // Refresh the drafts list to include the newly saved draft
                loadAmazonDrafts();
            } else {
                alert('Failed to save draft: ' + response.message);
            }
        }).fail(function(xhr) {
            alert('Error saving draft: ' + xhr.responseText);
        }).always(function() {
            $('#save-draft-btn').prop('disabled', false).text('Save Draft');
        });
    });

    $('#validate-listing-btn').click(function() {
        if (!currentListingId) {
            // Save first, then validate
            $('#save-draft-btn').click();
            return;
        }

        $(this).prop('disabled', true).text('Validating...');

        $.ajax({
            type: 'POST',
            url: 'api/amazon-listing/validate',
            data: {
                listing_id: currentListingId
            },
            dataType: 'json'
        }).done(function(response) {
            if (response.success) {
                updateListingStatus(response.status);
                updateValidationSummary(response.validation_summary);
                alert(response.message);
            } else {
                alert('Failed to validate listing: ' + response.message);
            }
        }).fail(function(xhr) {
            alert('Error validating listing: ' + xhr.responseText);
        }).always(function() {
            $('#validate-listing-btn').prop('disabled', false).text('Validate');
        });
    });

    $('#generate-csv-btn').click(function() {
        generateFile('csv');
    });

    $('#generate-json-btn').click(function() {
        generateFile('json');
    });

    function generateFile(format) {
        if (!currentListingId) {
            alert('Please save and validate your listing first');
            return;
        }

        const btn = $(`#generate-${format}-btn`);
        btn.prop('disabled', true).text(`Generating ${format.toUpperCase()}...`);

        $.ajax({
            type: 'POST',
            url: 'api/amazon-listing/generate-file',
            data: {
                listing_id: currentListingId,
                format: format
            },
            dataType: 'json'
        }).done(function(response) {
            if (response.success) {
                alert(`${format.toUpperCase()} file generated successfully!`);

                // Open download in new window
                window.open(response.download_url, '_blank');
            } else {
                alert(`Failed to generate ${format.toUpperCase()} file: ` + response.message);
            }
        }).fail(function(xhr) {
            alert(`Error generating ${format.toUpperCase()} file: ` + xhr.responseText);
        }).always(function() {
            btn.prop('disabled', false).text(`Generate ${format.toUpperCase()}`);
        });
    }
</script>

<p class="form-text">{!! $tooltip !!}</p>
