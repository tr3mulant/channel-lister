<div class="form-group {{ $required }}">
    <div class="form-horizontal">
        <label class="control-label">{{ $label_text }}</label>
    </div>
    <div class="col-sm-4">
        <input type="text" name="{{ $element_name }}" {{ $required }} pattern="^[0-9]{12,13}$" maxlength="13"
            class="form-control upc_field" id="{{ $platform }}_upc">
        <p class="help-block">{{ $tooltip }}</p>
        <p class="help-block">{{ $maps_to_text }}</p>
    </div>

    <div class="col-sm-8">
        <div class="row">
            <div class="col-md-3 .text-center"><input class="btn btn-primary fill_upc"
                    data-platform="{{ $platform }}" type="button" value="Make UPC starting with:"></div>
            <div class="col-md-3"><input type="text" class="form-control" id="{{ $platform }}_upc_seed"
                    placeholder="Nothing" pattern="^\d{0,11}$"></div>
            <div class="col-md-6">
                <select id='{{ $platform }}_upc_start_selction' class="form-control manufacturer_code_select"
                    data-platform="{{ $platform }}">
                    <option value="" selected="">Manufacturer Code</option>

                    {{-- added this instead of 3 hardcoded option instances --}}
                    @foreach ($asr_upc_prefixes as $prefix)
                        <option class="error" value="{{ $prefix }}">{{ 'Purchased UPC Prefix: ' . $prefix }}</option>
                    @endforeach
                    <option value="850549">Best Ride On Cars</option>
                    <option value="616588">Heininger</option>
                    <option value="783152">Flatline Ops</option>
                    <option value="885189">Lucky Star</option>
                    <option value="021563">McNett</option>
                    <option value="060886">Neptune</option>
                    <option value="670468">Productive Fitness</option>
                    <option value="766359">Shomer</option>
                    <option value="706569">Sona</option>
                    <option value="032281">UPD</option>
                    <option value="819673">Zak Tools</option>
                </select>
            </div>
        </div>
    </div>
    <br class="clearfloat">
</div>