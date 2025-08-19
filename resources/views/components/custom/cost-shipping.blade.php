<div class="form-group {{ $required }}">
    <label class="col-form-label" for="{{ $id }}">{{ $label_text }}</label>
    <div class="input-group">
        <div class="input-group-prepend">
            <span class="input-group-text">$</span>
        </div>
        <input type="number" name="{{ $element_name }}" class="{{ $classStrDefault }}" id="{{ $id }}" step="0.01"
            min="0" placeholder="{{ $placeholder }}" {{ $required }}>
        <div class="input-group-append">
            <button type="button" class="btn btn-outline-primary" onclick="showShippingCalculator('{{ $id }}')">
                <i class="fas fa-calculator"></i> Calculate Shipping
            </button>
        </div>
    </div>
    <p class="form-text">{!! $tooltip !!}</p>
    <p class="form-text">{!! $maps_to_text !!}</p>
</div>

<!-- Shipping Calculator Modal -->
<div class="modal fade" id="shipping-calculator-modal-{{ $id }}" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-shipping-fast"></i> Shipping Cost Calculator
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="shipping-calculator-{{ $id }}" class="shipping-calculator-container">
                    <div id="api-status-message-{{ $id }}" class="alert" style="display: none;"></div>
                    
                    <!-- Manual Cost Input (shown when API is not available) -->
                    <div id="manual-shipping-section-{{ $id }}" style="display: none;">
                        <div class="form-group">
                            <label class="col-form-label" for="manual-shipping-cost-{{ $id }}">Enter Shipping Cost</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">$</span>
                                </div>
                                <input type="number" class="form-control" id="manual-shipping-cost-{{ $id }}" 
                                       step="0.01" min="0" placeholder="0.00">
                            </div>
                            <small class="form-text text-muted">Enter the estimated shipping cost manually</small>
                        </div>
                    </div>

                    <!-- API Calculation Section -->
                    <div id="api-calculation-section-{{ $id }}">
                        <!-- Location Detection -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="col-form-label" for="from-zip-{{ $id }}">From ZIP Code</label>
                                    <input type="text" class="form-control" id="from-zip-{{ $id }}" 
                                           placeholder="98225" maxlength="5">
                                    <small class="form-text text-muted">
                                        <button type="button" id="detect-location-{{ $id }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-location-arrow"></i> Detect from IP
                                        </button>
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="col-form-label" for="to-zip-{{ $id }}">To ZIP Code</label>
                                    <input type="text" class="form-control" id="to-zip-{{ $id }}" 
                                           placeholder="90210" maxlength="5">
                                    <small class="form-text text-muted">Destination ZIP code</small>
                                </div>
                            </div>
                        </div>

                        <!-- Package Dimensions -->
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="col-form-label" for="package-length-{{ $id }}">Length (in)</label>
                                    <input type="number" class="form-control" id="package-length-{{ $id }}" 
                                           step="0.1" min="0.1" placeholder="12">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="col-form-label" for="package-width-{{ $id }}">Width (in)</label>
                                    <input type="number" class="form-control" id="package-width-{{ $id }}" 
                                           step="0.1" min="0.1" placeholder="8">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="col-form-label" for="package-height-{{ $id }}">Height (in)</label>
                                    <input type="number" class="form-control" id="package-height-{{ $id }}" 
                                           step="0.1" min="0.1" placeholder="6">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="col-form-label" for="package-weight-{{ $id }}">Weight (lbs)</label>
                                    <input type="number" class="form-control" id="package-weight-{{ $id }}" 
                                           step="0.1" min="0.1" placeholder="2.5">
                                </div>
                            </div>
                        </div>

                        <!-- Auto-fill from existing form fields -->
                        <div class="form-group">
                            <button type="button" id="autofill-dimensions-{{ $id }}" class="btn btn-sm btn-info">
                                <i class="fas fa-magic"></i> Auto-fill from Form Data
                            </button>
                            <small class="form-text text-muted">
                                Automatically populate dimensions from ship_length, ship_width, ship_height, and ship_weight fields
                            </small>
                        </div>

                        <!-- Dimensional Weight Info -->
                        <div id="dimensional-weight-info-{{ $id }}" class="alert alert-info" style="display: none; position: relative;">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('dimensional-weight-info-{{ $id }}').style.display='none';" style="position: absolute; top: 10px; right: 10px; padding: 2px 8px; font-size: 12px;">
                                ×
                            </button>
                            <h6><i class="fas fa-info-circle"></i> Dimensional Weight Calculation:</h6>
                            <div id="dimensional-weight-details-{{ $id }}"></div>
                        </div>

                        <!-- Calculate Button -->
                        <div class="form-group">
                            <button type="button" id="calculate-shipping-{{ $id }}" class="btn btn-primary">
                                <span id="calculate-spinner-{{ $id }}" class="spinner-border spinner-border-sm" role="status" style="display: none;">
                                    <span class="sr-only">Loading...</span>
                                </span>
                                <i class="fas fa-calculator"></i> Calculate Shipping Rates
                            </button>
                            <button type="button" id="calculate-dimensional-weight-{{ $id }}" class="btn btn-outline-secondary ml-2">
                                <i class="fas fa-cube"></i> Calculate Dimensional Weight
                            </button>
                        </div>

                        <!-- Results -->
                        <div id="shipping-results-{{ $id }}" style="display: none;">
                            <h6><i class="fas fa-list"></i> Available Shipping Options:</h6>
                            <div id="shipping-rates-list-{{ $id }}" style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px; background-color: #fafafa;"></div>
                            
                            <!-- Selected Cost Input (outside scrollable area) -->
                            <div id="selected-cost-section-{{ $id }}" style="display: none;">
                                <div class="form-group mt-3">
                                    <label class="col-form-label">Selected Shipping Cost</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">$</span>
                                        </div>
                                        <input type="number" class="form-control" id="selected-shipping-cost-{{ $id }}" 
                                               step="0.01" min="0" readonly>
                                    </div>
                                    <small class="form-text text-muted">Click a shipping option above to select</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" class="btn btn-success" onclick="useCalculatedShipping('{{ $id }}')">
                    <i class="fas fa-check"></i> Use Selected Cost
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    initializeShippingCalculator('{{ $id }}');
});

function showShippingCalculator(fieldId) {
    const modal = document.getElementById(`shipping-calculator-modal-${fieldId}`);
    if (modal) {
        $(modal).modal('show');
        checkApiAvailability(fieldId);
    }
}

function useCalculatedShipping(fieldId) {
    const calculatedCost = document.getElementById(`selected-shipping-cost-${fieldId}`)?.value || 
                          document.getElementById(`manual-shipping-cost-${fieldId}`)?.value;
    
    if (calculatedCost && calculatedCost > 0) {
        document.getElementById(fieldId).value = calculatedCost;
        $(`#shipping-calculator-modal-${fieldId}`).modal('hide');
    } else {
        alert('Please calculate or enter a shipping cost first');
    }
}

function initializeShippingCalculator(fieldId) {
    if (typeof window.shippingAPIService === 'undefined') {
        window.shippingAPIService = new ShippingAPIService();
    }

    // Auto-fill dimensions button
    document.getElementById(`autofill-dimensions-${fieldId}`)?.addEventListener('click', function() {
        // Try to find existing form fields for dimensions and weight
        const shipLength = document.querySelector('input[name="ship_length"]')?.value;
        const shipWidth = document.querySelector('input[name="ship_width"]')?.value; 
        const shipHeight = document.querySelector('input[name="ship_height"]')?.value;
        const shipWeight = document.querySelector('input[name="ship_weight"]')?.value;
        
        if (shipLength) document.getElementById(`package-length-${fieldId}`).value = shipLength;
        if (shipWidth) document.getElementById(`package-width-${fieldId}`).value = shipWidth;
        if (shipHeight) document.getElementById(`package-height-${fieldId}`).value = shipHeight;
        if (shipWeight) document.getElementById(`package-weight-${fieldId}`).value = shipWeight;
        
        if (shipLength || shipWidth || shipHeight || shipWeight) {
            alert('Dimensions filled from existing form data!');
        } else {
            alert('No dimension data found in form. Please enter manually.');
        }
    });

    // Detect location button
    document.getElementById(`detect-location-${fieldId}`)?.addEventListener('click', async function() {
        try {
            const location = await window.shippingAPIService.getLocation();
            if (location.success && location.zip_code) {
                document.getElementById(`from-zip-${fieldId}`).value = location.zip_code;
                
                const button = this;
                const originalHTML = button.innerHTML;
                button.innerHTML = `<i class="fas fa-check"></i> Detected: ${location.city}, ${location.state}`;
                button.classList.remove('btn-outline-primary');
                button.classList.add('btn-success');
                setTimeout(() => {
                    button.innerHTML = originalHTML;
                    button.classList.remove('btn-success');
                    button.classList.add('btn-outline-primary');
                }, 3000);
            }
        } catch (error) {
            console.error('Error detecting location:', error);
        }
    });

    // Calculate dimensional weight
    document.getElementById(`calculate-dimensional-weight-${fieldId}`)?.addEventListener('click', async function() {
        const length = document.getElementById(`package-length-${fieldId}`).value;
        const width = document.getElementById(`package-width-${fieldId}`).value;
        const height = document.getElementById(`package-height-${fieldId}`).value;
        const weight = document.getElementById(`package-weight-${fieldId}`).value;

        if (!length || !width || !height || !weight) {
            alert('Please fill in all dimension and weight fields');
            return;
        }

        try {
            const result = await window.shippingAPIService.calculateDimensionalWeight({
                length: parseFloat(length),
                width: parseFloat(width),
                height: parseFloat(height),
                weight: parseFloat(weight)
            });

            if (result.success) {
                showDimensionalWeightInfo(result.data, fieldId);
            }
        } catch (error) {
            console.error('Error calculating dimensional weight:', error);
        }
    });

    // Calculate shipping rates
    document.getElementById(`calculate-shipping-${fieldId}`)?.addEventListener('click', async function() {
        await calculateShippingRates(fieldId);
    });
}

async function checkApiAvailability(fieldId) {
    try {
        const response = await window.shippingAPIService.checkApiAvailability();
        const hasApiKey = response.has_api_key;
        
        const statusMessage = document.getElementById(`api-status-message-${fieldId}`);
        const manualSection = document.getElementById(`manual-shipping-section-${fieldId}`);
        const apiSection = document.getElementById(`api-calculation-section-${fieldId}`);
        
        if (hasApiKey) {
            statusMessage.className = 'alert alert-success';
            statusMessage.innerHTML = '<i class="fas fa-check-circle"></i> API available - automatic calculations enabled';
            statusMessage.style.display = 'block';
            manualSection.style.display = 'none';
            apiSection.style.display = 'block';
        } else {
            statusMessage.className = 'alert alert-warning';
            statusMessage.innerHTML = '<i class="fas fa-exclamation-triangle"></i> API not configured - please enter shipping cost manually';
            statusMessage.style.display = 'block';
            manualSection.style.display = 'block';
            apiSection.style.display = 'none';
        }
    } catch (error) {
        console.error('Error checking API availability:', error);
        showManualEntry(fieldId, 'Error checking API - please enter manually');
    }
}

async function calculateShippingRates(fieldId) {
    const hasApiKey = !document.getElementById(`manual-shipping-section-${fieldId}`).style.display || 
                     document.getElementById(`manual-shipping-section-${fieldId}`).style.display === 'none';
    
    if (!hasApiKey) {
        const manualCost = document.getElementById(`manual-shipping-cost-${fieldId}`).value;
        if (!manualCost || manualCost <= 0) {
            alert('Please enter a valid shipping cost');
            return;
        }
        // Set the manual cost in the selected shipping cost field
        const selectedCostInput = document.getElementById(`selected-shipping-cost-${fieldId}`);
        const selectedCostSection = document.getElementById(`selected-cost-section-${fieldId}`);
        const resultsDiv = document.getElementById(`shipping-results-${fieldId}`);
        
        if (selectedCostInput) {
            selectedCostInput.value = manualCost;
        }
        
        // Show the results and selected cost sections
        resultsDiv.style.display = 'block';
        selectedCostSection.style.display = 'block';
        return;
    }

    const fromZip = document.getElementById(`from-zip-${fieldId}`).value;
    const toZip = document.getElementById(`to-zip-${fieldId}`).value;
    const length = document.getElementById(`package-length-${fieldId}`).value;
    const width = document.getElementById(`package-width-${fieldId}`).value;
    const height = document.getElementById(`package-height-${fieldId}`).value;
    const weight = document.getElementById(`package-weight-${fieldId}`).value;

    if (!fromZip || !toZip) {
        alert('Please fill in both ZIP codes');
        return;
    }

    if (!length || !width || !height || !weight) {
        alert('Please fill in all package dimensions and weight');
        return;
    }

    const spinner = document.getElementById(`calculate-spinner-${fieldId}`);
    const button = document.getElementById(`calculate-shipping-${fieldId}`);
    const originalHTML = button.innerHTML;
    
    spinner.style.display = 'inline-block';
    button.disabled = true;

    try {
        const rateData = {
            from_zip: fromZip,
            to_zip: toZip,
            length: parseFloat(length),
            width: parseFloat(width),
            height: parseFloat(height),
            weight: parseFloat(weight)
        };

        const result = await window.shippingAPIService.calculateRates(rateData);
        
        if (result.success && result.rates) {
            showShippingResults(result, fieldId);
        } else if (result.manual_entry_required) {
            showManualEntry(fieldId, result.message);
        } else {
            alert(result.message || 'Error calculating shipping rates');
        }
    } catch (error) {
        console.error('Error calculating shipping rates:', error);
        alert('Error calculating shipping rates. Please try again.');
    } finally {
        spinner.style.display = 'none';
        button.disabled = false;
        button.innerHTML = originalHTML;
    }
}

function showDimensionalWeightInfo(data, fieldId) {
    const infoDiv = document.getElementById(`dimensional-weight-info-${fieldId}`);
    const detailsDiv = document.getElementById(`dimensional-weight-details-${fieldId}`);
    
    let html = `<p><strong>Cubic Size:</strong> ${data.cubic_size} cubic inches</p>`;
    html += `<p><strong>Actual Weight:</strong> ${data.actual_weight} lbs</p>`;
    html += '<div class="row">';
    
    Object.entries(data.dimensional_weights).forEach(([carrier, weights]) => {
        const carrierName = carrier.replace('_', ' ').toUpperCase();
        html += `
            <div class="col-md-4">
                <strong>${carrierName}</strong><br>
                Dim Weight: ${weights.dimensional_weight} lbs<br>
                Billable: ${weights.billable_weight} lbs<br>
                <small>(Divisor: ${weights.divisor})</small>
            </div>
        `;
    });
    
    html += '</div>';
    detailsDiv.innerHTML = html;
    infoDiv.style.display = 'block';
}

function showShippingResults(result, fieldId) {
    const resultsDiv = document.getElementById(`shipping-results-${fieldId}`);
    const ratesDiv = document.getElementById(`shipping-rates-list-${fieldId}`);
    const selectedCostSection = document.getElementById(`selected-cost-section-${fieldId}`);
    
    // Only populate the rates list (for scrollable area)
    let html = '<div class="list-group">';
    
    result.rates.forEach((rate, index) => {
        const deliveryInfo = rate.delivery_days ? 
            `<small class="text-muted"><i class="fas fa-clock"></i> Delivery: ${rate.delivery_days} days</small>` : '';
        
        html += `
            <div class="list-group-item list-group-item-action" onclick="selectShippingRate('${rate.amount}', '${rate.service_name}', '${rate.carrier}', '${fieldId}')">
                <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1"><i class="fas fa-truck"></i> ${rate.carrier} - ${rate.service_name}</h6>
                    <strong class="text-success">$${rate.amount}</strong>
                </div>
                ${deliveryInfo}
            </div>
        `;
    });
    
    html += '</div>';
    
    // Populate only the rates list in the scrollable area
    ratesDiv.innerHTML = html;
    
    // Show the results section and the selected cost section
    resultsDiv.style.display = 'block';
    selectedCostSection.style.display = 'block';
}

function showManualEntry(fieldId, message = 'API not available - please enter shipping cost manually') {
    const statusMessage = document.getElementById(`api-status-message-${fieldId}`);
    const manualSection = document.getElementById(`manual-shipping-section-${fieldId}`);
    const apiSection = document.getElementById(`api-calculation-section-${fieldId}`);
    
    statusMessage.className = 'alert alert-warning';
    statusMessage.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${message}`;
    statusMessage.style.display = 'block';
    manualSection.style.display = 'block';
    apiSection.style.display = 'none';
}

function selectShippingRate(amount, serviceName, carrier, fieldId) {
    document.getElementById(`selected-shipping-cost-${fieldId}`).value = amount;
    
    // Update visual selection
    document.querySelectorAll(`#shipping-rates-list-${fieldId} .list-group-item`).forEach(item => {
        item.classList.remove('active');
    });
    event.target.closest('.list-group-item').classList.add('active');
}

// Shipping API Service Class (ensure it exists globally)
if (typeof window.ShippingAPIService === 'undefined') {
    window.ShippingAPIService = class {
        constructor(baseUrl = '/api/shipping') {
            this.baseUrl = baseUrl;
        }
        
        async checkApiAvailability() {
            try {
                const response = await fetch(`${this.baseUrl}/check-api`);
                return await response.json();
            } catch (error) {
                console.error('Error checking API availability:', error);
                throw error;
            }
        }
        
        async getLocation() {
            try {
                const response = await fetch(`${this.baseUrl}/location`);
                return await response.json();
            } catch (error) {
                console.error('Error getting location:', error);
                throw error;
            }
        }
        
        async calculateRates(rateData) {
            try {
                const response = await fetch(`${this.baseUrl}/calculate`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    },
                    body: JSON.stringify(rateData)
                });
                
                return await response.json();
            } catch (error) {
                console.error('Error calculating rates:', error);
                throw error;
            }
        }
        
        async getCarriers() {
            try {
                const response = await fetch(`${this.baseUrl}/carriers`);
                return await response.json();
            } catch (error) {
                console.error('Error getting carriers:', error);
                throw error;
            }
        }
        
        async calculateDimensionalWeight(dimensions) {
            try {
                const response = await fetch(`${this.baseUrl}/dimensional-weight`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    },
                    body: JSON.stringify(dimensions)
                });
                
                return await response.json();
            } catch (error) {
                console.error('Error calculating dimensional weight:', error);
                throw error;
            }
        }
    };
}
</script>
