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
                    $(this).delay(index * 25).slideDown(100);
                });

                // Calculate total animation time: (number of panels - 1) * stagger delay + slide duration
                let totalAnimationTime = ((newPanels.length - 1) * 25) + 100;

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
        {{-- <div class="container-fluid"> --}}
        <div class="">
            <label class="col-form-label font-weight-bold"
                for="{{ $element_name }}-searchbox">{{ $params->display_name ?? $params->field_name }}</label>
        </div>
        <div class="row">
            <div class="col-12 col-md-4 mb-1">
                <input type="text" class="form-control" id="{{ $element_name }}-searchbox"
                    placeholder="Search for product type...">
            </div>
            <div class="col-md-8">
                <div class="">
                    <input type="text" name="{{ $element_name }}" @class(['form-control', 'required' => $params->required])
                        id="{{ $id }}" placeholder="{{ $placeholder }}" @required($params->required)>
                    <p class="form-text">Maps To: <code>{{ $params->field_name }}</code></p>
                </div>
            </div>
        </div>
        {{-- </div> --}}

        <script>
            $('#{{ $element_name }}-searchbox').focusout(function() {
                $('#{{ $element_name }}-matches').slideUp(300);
            });

            $('#{{ $element_name }}-searchbox').on('focusin click', function() {
                // Only show if there are results and not currently animating
                if ($('#{{ $element_name }}-matches').children().length > 0 && !$('#{{ $element_name }}-matches').is(
                        ':animated')) {
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
                            if ($('#{{ $element_name }}-searchbox').is(':focus') && !matchesContainer.is(
                                    ':visible')) {
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
            <div class="col-sm-3 mb-1">
                <select id="amazon-identifier-type" class="form-control">
                    <option value="ASIN">ASIN</option>
                    <option value="GTIN">GTIN</option>
                    <option value="UPC">UPC</option>
                    <option value="EAN">EAN</option>
                    <option value="ISBN">ISBN</option>
                </select>
            </div>
            <div class="col-sm-6 mb-1">
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

    <!-- Informational message about unified draft system -->
    <div class="alert alert-info mt-3" role="alert">
        <h6 class="alert-heading">
            <span style="margin-right: 0.5rem;">ℹ️</span>Enhanced Draft Management
        </h6>
        <p class="mb-2">
            <strong>Amazon draft functionality has been upgraded!</strong>
            All draft saving, loading, validation, and export features are now available through the
            <strong>Unified Product Draft</strong> controls at the top of the page.
        </p>
        <small class="text-muted">
            <span style="margin-right: 0.25rem;">💡</span>
            <strong>Tip:</strong> The unified system can save drafts that include data from both the Common tab and
            Amazon tab,
            then export everything together in Rithum format with Amazon data as custom attributes.
        </small>
    </div>

    <!-- Dynamic Amazon panels will be appended to the tab level -->

    <!-- Note: Form submission controls are now handled by the unified controls above all tabs -->
</div>

<script>
    // Global variables for Amazon product type functionality
    var currentListingId = null;
    var currentProductType = null;
    var currentMarketplaceId = 'ATVPDKIKX0DER'; // Default US marketplace

    // These functions are kept for backward compatibility with existing Amazon tab functionality
    function showFormControls() {
        // This is now handled by the unified draft controls
        console.log('Amazon product type loaded:', currentProductType);
    }

    function hideFormControls() {
        // This is now handled by the unified draft controls
        console.log('Amazon form controls hidden');
    }
</script>

<p class="form-text text-secondary">{!! $tooltip !!}</p>
