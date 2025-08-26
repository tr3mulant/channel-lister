<div @class(['form-group container-fluid', 'required' => $required]) @required($required)>
    <div class="row">
        <label class="col-form-label font-weight-bold" for="{{ $platform }}_upc">{{ $label_text }}</label>
    </div>

    <div class="row">
        <div class="col-12 col-md-4 px-0">
            <input type="text" name="{{ $element_name }}" @required($required) pattern="^[0-9]{12,13}$" maxlength="13"
                class="form-control upc_field" id="{{ "{$platform}_upc" }}">
            <p class="form-text text-secondary">{!! $tooltip !!}</p>
            <p class="form-text text-secondary">{!! $maps_to_text !!}</p>
        </div>
        <div class="col-12 col-md-8">
            <div class="row">
                <div class="col text-center">
                    <input class="btn btn-primary fill_upc" data-platform="{{ $platform }}" type="button"
                        value="Make UPC starting with:">
                </div>
                <div class="col">
                    <input class="form-control" type="text" class="" id="{{ "{$platform}_upc_seed" }}"
                        placeholder="Nothing" pattern="^\d{0,11}$">
                </div>
                <div class="col">
                    <select class="form-control manufacturer_code_select" id="{{ "{$platform}_upc_start_selection" }}"
                        data-platform="{{ $platform }}">
                        <option value="" selected="">Manufacturer Code</option>

                        @foreach ($user_defined_upc_prefixes as $prefix)
                            <option class="" value="{{ $prefix['prefix'] }}">
                                {{ "Prefix: {$prefix['name']}" }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>
