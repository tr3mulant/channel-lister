<div class="form-group {{ $required }}">
    <label class="col-form-label" for="{{ $id }}">{{ $label_text }}</label>
    <div class="input-group">
        <div class="input-group-prepend">
            <span class="input-group-text">$</span>
        </div>
        <input type="number" name="{{ $element_name }}" class="{{ $classStrDefault }}" id="{{ $id }}" 
               step="0.01" min="0" placeholder="{{ $placeholder }}" {{ $required }}>
        <div class="input-group-append">
            <button type="button" class="btn btn-outline-secondary" onclick="showShippingCalculator('{{ $id }}')">
                <i class="fas fa-calculator"></i> Calculate
            </button>
        </div>
    </div>
    <p class="form-text">{!! $tooltip !!}</p>
    <p class="form-text">{!! $maps_to_text !!}</p>
</div>

<!-- Shipping Calculator Modal -->
<div class="modal fade" id="shipping-calculator-modal-{{ $id }}" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Shipping Cost Calculator</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <x-channel-lister::shipping-calculator 
                    :classStrDefault="$classStrDefault" 
                    :showDimensionalCalculator="true" 
                />
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="useCalculatedShipping('{{ $id }}')">
                    Use Calculated Cost
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function showShippingCalculator(fieldId) {
    const modal = document.getElementById(`shipping-calculator-modal-${fieldId}`);
    if (modal) {
        $(modal).modal('show');
    }
}

function useCalculatedShipping(fieldId) {
    const calculatedCost = document.getElementById('selected-shipping-cost')?.value || 
                          document.getElementById('manual-shipping-cost')?.value;
    
    if (calculatedCost && calculatedCost > 0) {
        document.getElementById(fieldId).value = calculatedCost;
        $(`#shipping-calculator-modal-${fieldId}`).modal('hide');
    } else {
        alert('Please calculate or enter a shipping cost first');
    }
}
</script>