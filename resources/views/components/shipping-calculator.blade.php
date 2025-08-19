<div id="shipping-calculator" class="card">
    <div class="card-header">
        <h5 class="mb-0">Shipping Cost Calculator</h5>
    </div>
    <div class="card-body">
        <div id="api-status-message" class="alert" style="display: none;"></div>
        
        <!-- Manual Cost Input (shown when API is not available) -->
        <div id="manual-shipping-section" style="display: none;">
            <div class="form-group">
                <label class="col-form-label" for="manual-shipping-cost">Enter Shipping Cost</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">$</span>
                    </div>
                    <input type="number" name="manual_shipping_cost" class="{{ $classStrDefault }}" id="manual-shipping-cost" 
                           step="0.01" min="0" placeholder="0.00">
                </div>
                <small class="form-text text-muted">Enter the estimated shipping cost manually</small>
            </div>
        </div>

        <!-- API Calculation Section -->
        <div id="api-calculation-section">
            <!-- Location Detection -->
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="col-form-label" for="from-zip">From ZIP Code</label>
                        <input type="text" name="from_zip" class="{{ $classStrDefault }}" id="from-zip" 
                               placeholder="98225" maxlength="5" value="{{ $fromZip }}">
                        <small class="form-text text-muted">
                            <button type="button" id="detect-location" class="btn btn-sm btn-outline-primary">
                                Detect from IP
                            </button>
                        </small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="col-form-label" for="to-zip">To ZIP Code</label>
                        <input type="text" name="to_zip" class="{{ $classStrDefault }}" id="to-zip" 
                               placeholder="90210" maxlength="5" value="{{ $toZip }}">
                        <small class="form-text text-muted">Destination ZIP code</small>
                    </div>
                </div>
            </div>

            @if($showDimensionalCalculator)
            <!-- Package Dimensions -->
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="col-form-label" for="package-length">Length (in)</label>
                        <input type="number" name="package_length" class="{{ $classStrDefault }}" id="package-length" 
                               step="0.1" min="0.1" placeholder="12">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="col-form-label" for="package-width">Width (in)</label>
                        <input type="number" name="package_width" class="{{ $classStrDefault }}" id="package-width" 
                               step="0.1" min="0.1" placeholder="8">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="col-form-label" for="package-height">Height (in)</label>
                        <input type="number" name="package_height" class="{{ $classStrDefault }}" id="package-height" 
                               step="0.1" min="0.1" placeholder="6">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="col-form-label" for="package-weight">Weight (lbs)</label>
                        <input type="number" name="package_weight" class="{{ $classStrDefault }}" id="package-weight" 
                               step="0.1" min="0.1" placeholder="2.5">
                    </div>
                </div>
            </div>

            <!-- Dimensional Weight Info -->
            <div id="dimensional-weight-info" class="alert alert-info" style="display: none;">
                <h6>Dimensional Weight Calculation:</h6>
                <div id="dimensional-weight-details"></div>
            </div>
            @endif

            <!-- Calculate Button -->
            <div class="form-group">
                <button type="button" id="calculate-shipping" class="btn btn-primary">
                    <span id="calculate-spinner" class="spinner-border spinner-border-sm" role="status" style="display: none;">
                        <span class="sr-only">Loading...</span>
                    </span>
                    Calculate Shipping Rates
                </button>
                @if($showDimensionalCalculator)
                <button type="button" id="calculate-dimensional-weight" class="btn btn-outline-secondary ml-2">
                    Calculate Dimensional Weight
                </button>
                @endif
            </div>

            <!-- Results -->
            <div id="shipping-results" style="display: none;">
                <h6>Available Shipping Options:</h6>
                <div id="shipping-rates-list"></div>
                
                <!-- Final shipping cost selection -->
                <div class="form-group mt-3">
                    <label class="col-form-label" for="selected-shipping-cost">Selected Shipping Cost</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">$</span>
                        </div>
                        <input type="number" name="calculated_shipping_cost" class="{{ $classStrDefault }}" 
                               id="selected-shipping-cost" step="0.01" min="0" readonly>
                    </div>
                    <small class="form-text text-muted">This cost will be used for your product listing</small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Shipping Calculator JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const shippingCalculator = new ShippingAPIService();
    let hasApiKey = false;

    // Check API availability on load
    checkApiAvailability();

    async function checkApiAvailability() {
        try {
            const response = await shippingCalculator.checkApiAvailability();
            hasApiKey = response.has_api_key;
            
            const statusMessage = document.getElementById('api-status-message');
            const manualSection = document.getElementById('manual-shipping-section');
            const apiSection = document.getElementById('api-calculation-section');
            
            if (hasApiKey) {
                statusMessage.className = 'alert alert-success';
                statusMessage.textContent = 'API available - automatic calculations enabled';
                statusMessage.style.display = 'block';
                manualSection.style.display = 'none';
                apiSection.style.display = 'block';
            } else {
                statusMessage.className = 'alert alert-warning';
                statusMessage.textContent = 'API not configured - please enter shipping cost manually';
                statusMessage.style.display = 'block';
                manualSection.style.display = 'block';
                apiSection.style.display = 'none';
            }
        } catch (error) {
            console.error('Error checking API availability:', error);
            // Default to manual entry on error
            showManualEntry();
        }
    }

    // Detect location from IP
    document.getElementById('detect-location')?.addEventListener('click', async function() {
        try {
            const location = await shippingCalculator.getLocation();
            if (location.success && location.zip_code) {
                document.getElementById('from-zip').value = location.zip_code;
                
                // Show location info
                const button = this;
                const originalText = button.textContent;
                button.textContent = `Detected: ${location.city}, ${location.state}`;
                button.classList.add('btn-success');
                setTimeout(() => {
                    button.textContent = originalText;
                    button.classList.remove('btn-success');
                }, 3000);
            }
        } catch (error) {
            console.error('Error detecting location:', error);
        }
    });

    // Calculate dimensional weight
    document.getElementById('calculate-dimensional-weight')?.addEventListener('click', async function() {
        const length = document.getElementById('package-length').value;
        const width = document.getElementById('package-width').value;
        const height = document.getElementById('package-height').value;
        const weight = document.getElementById('package-weight').value;

        if (!length || !width || !height || !weight) {
            alert('Please fill in all dimension and weight fields');
            return;
        }

        try {
            const result = await shippingCalculator.calculateDimensionalWeight({
                length: parseFloat(length),
                width: parseFloat(width),
                height: parseFloat(height),
                weight: parseFloat(weight)
            });

            if (result.success) {
                showDimensionalWeightInfo(result.data);
            }
        } catch (error) {
            console.error('Error calculating dimensional weight:', error);
        }
    });

    // Calculate shipping rates
    document.getElementById('calculate-shipping')?.addEventListener('click', async function() {
        if (!hasApiKey) {
            // Handle manual entry
            const manualCost = document.getElementById('manual-shipping-cost').value;
            if (!manualCost || manualCost <= 0) {
                alert('Please enter a valid shipping cost');
                return;
            }
            return;
        }

        // Validate required fields for API calculation
        const fromZip = document.getElementById('from-zip').value;
        const toZip = document.getElementById('to-zip').value;
        const length = document.getElementById('package-length')?.value;
        const width = document.getElementById('package-width')?.value;
        const height = document.getElementById('package-height')?.value;
        const weight = document.getElementById('package-weight')?.value;

        if (!fromZip || !toZip) {
            alert('Please fill in both ZIP codes');
            return;
        }

        if (document.getElementById('package-length') && (!length || !width || !height || !weight)) {
            alert('Please fill in all package dimensions and weight');
            return;
        }

        const spinner = document.getElementById('calculate-spinner');
        const button = this;
        const originalText = button.textContent;
        
        spinner.style.display = 'inline-block';
        button.disabled = true;

        try {
            const rateData = {
                from_zip: fromZip,
                to_zip: toZip,
                length: parseFloat(length || 12),
                width: parseFloat(width || 8),
                height: parseFloat(height || 6),
                weight: parseFloat(weight || 1)
            };

            console.log('Sending rate data:', rateData);
            const result = await shippingCalculator.calculateRates(rateData);
            console.log('Received result:', result);
            
            if (result.success && result.rates) {
                showShippingResults(result);
            } else if (result.manual_entry_required) {
                showManualEntry(result.message);
            } else {
                console.error('API error:', result);
                alert(result.message || 'Error calculating shipping rates');
            }
        } catch (error) {
            console.error('Error calculating shipping rates:', error);
            alert('Error calculating shipping rates. Please try again. Check console for details.');
        } finally {
            spinner.style.display = 'none';
            button.disabled = false;
        }
    });

    function showDimensionalWeightInfo(data) {
        const infoDiv = document.getElementById('dimensional-weight-info');
        const detailsDiv = document.getElementById('dimensional-weight-details');
        
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

    function showShippingResults(result) {
        const resultsDiv = document.getElementById('shipping-results');
        const ratesDiv = document.getElementById('shipping-rates-list');
        
        let html = '<div class="list-group">';
        
        result.rates.forEach((rate, index) => {
            const deliveryInfo = rate.delivery_days ? 
                `<small class="text-muted">Delivery: ${rate.delivery_days} days</small>` : '';
            
            html += `
                <div class="list-group-item list-group-item-action" onclick="selectShippingRate('${rate.amount}', '${rate.service_name}', '${rate.carrier}')">
                    <div class="d-flex w-100 justify-content-between">
                        <h6 class="mb-1">${rate.carrier} - ${rate.service_name}</h6>
                        <strong>$${rate.amount}</strong>
                    </div>
                    ${deliveryInfo}
                </div>
            `;
        });
        
        html += '</div>';
        ratesDiv.innerHTML = html;
        resultsDiv.style.display = 'block';
    }

    function showManualEntry(message = 'API not available - please enter shipping cost manually') {
        const statusMessage = document.getElementById('api-status-message');
        const manualSection = document.getElementById('manual-shipping-section');
        const apiSection = document.getElementById('api-calculation-section');
        
        statusMessage.className = 'alert alert-warning';
        statusMessage.textContent = message;
        statusMessage.style.display = 'block';
        manualSection.style.display = 'block';
        apiSection.style.display = 'none';
    }

    // Make selectShippingRate global
    window.selectShippingRate = function(amount, serviceName, carrier) {
        document.getElementById('selected-shipping-cost').value = amount;
        
        // Update visual selection
        document.querySelectorAll('#shipping-rates-list .list-group-item').forEach(item => {
            item.classList.remove('active');
        });
        event.target.closest('.list-group-item').classList.add('active');
    };
});

// Shipping API Service Class (from the provided code)
class ShippingAPIService {
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
            console.log('Making API call to:', `${this.baseUrl}/calculate`);
            const response = await fetch(`${this.baseUrl}/calculate`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                body: JSON.stringify(rateData)
            });
            
            console.log('Response status:', response.status);
            
            if (!response.ok) {
                const errorText = await response.text();
                console.error('API error response:', errorText);
                throw new Error(`HTTP ${response.status}: ${errorText}`);
            }
            
            const result = await response.json();
            return result;
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
}
</script>