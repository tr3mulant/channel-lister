<div class="form-group {{ $required }}">
    <div class=""> {{-- was form-horizontal--}}
        <label class="col-form-label">{{ $label_text }}</label>
    </div>
    <div class="col-sm-4">
        <input type="text" class="form-group" id="{{ $element_name }}-searchbox" placeholder="Search...">
    </div>
    <div class="col-sm-8">
        <div class="row">
            <input type="text" name="{{ $element_name }}" class="{{ $classStrDefault }}" id="{{ $id }}"
                placeholder="{{ $placeholder }}" {{ $required }}>
            <p class="form-text">{!! $maps_to_text !!}</p>
        </div>
    </div>
    <script>
        $('#{{ $element_name }}-searchbox').focusout(function() {
            $('#{{ $element_name }}-matches').css("visibility", "hidden");
        });
        $('#{{ $element_name }}-searchbox').on('focusin click', function() {
            $('#{{ $element_name }}-matches').css("visibility", "visible");
        });

        $("#{{ $element_name }}-searchbox").on('keyup', function(e) {
            if (e.keyCode === 27 /*escape key*/ ) {
                $('#{{ $element_name }}-searchbox').focusout();
                return;
            }

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
                    }).success(function(response) {
                        var table_html = response.data;
                        $("div#{{ $element_name }}-matches").html(table_html);
                        $("div#{{ $element_name }}-matches").css("visibility", "visible");
                        $("#{{ $element_name }}-matches tr").mousedown(function() {
                            var found_match = false;
                            var cat_id = $(this).children().first().text();
                            var cat_name = $(this).children().next().text();
                            switch ('{{ $marketplace }}') {
                                case 'amazon':
                                    if (cat_id != '-1') {
                                        $("#{{ $id }}").val(cat_id);
                                        $("#{{ $element_name }}-searchbox").val(cat_name);
                                        getAmazonInventoryTemplate(cat_name);
                                        getAmazonAttributeInput(cat_name);
                                        found_match = true;
                                    }
                                    //cat_name is the node path
                                    // if it's not empty, call function to get specific fields
                                    break;

                                case 'newegg':
                                    if (cat_id != '0') {
                                        let cat_id_parts = cat_id.split('/');
                                        let cat = cat_id_parts[0];
                                        let subcat_id = cat_id_parts[1];
                                        let type = cat_id_parts[2];
                                        $("#{{ $id }}").val(cat);
                                        $("#subcat_id_newegg-id").val(subcat_id);
                                        $("#type_newegg-id").val(type);
                                        $("#{{ $element_name }}-searchbox").val(cat_name);
                                        buildNeweggCategoryAttributes(cat, subcat_id, type);
                                        found_match = true;
                                    }
                                    break;

                                case 'sears':
                                    if (cat_id != '-1') {
                                        $("#{{ $id }}").val(cat_id);
                                        $("#{{ $element_name }}-searchbox").val(cat_name);
                                        found_match = true;
                                        getSearsAttributeInput(cat_id);
                                    }
                                    break;

                                case 'walmart':
                                    let subcat_name = '';
                                    let subcat_title = '';
                                    if (cat_name.includes('|')) {
                                        console.log('cat_name', cat_name);
                                        subcat_name = cat_name.split('|')[0];
                                        subcat_title = cat_name.split('|')[1];
                                        // subcat_name = cat_name.split('|')[1];
                                        // cat_name    = cat_name.split('|')[0];
                                        // $('#walmart_subcat-id').val(subcat_name);
                                        // getWalmartAttributeInput(cat_name,subcat_name);
                                        getWalmartAttributeInput(subcat_name);
                                        found_match = true;
                                    }
                                    $("#{{ $element_name }}-searchbox").val(cat_name);
                                    //removed this tentatively to fix the walmart fills
                                    // $("#{{ $id }}").val(cat_name);
                                    $("#{{ $id }}").val(subcat_name);
                                    break;
                                    // case 'wish':

                                    // 	break;
                                default:
                                    if (cat_id != '-1') {
                                        $("#{{ $id }}").val(cat_id);
                                        $("#{{ $element_name }}-searchbox").val(cat_name);
                                        found_match = true;
                                    }
                            }
                            $("#{{ $id }}").trigger('change');
                            if (!found_match) {
                                executeQuery();
                            }
                        });
                        $("#{{ $element_name }}-searchbox").focus();
                    }).error(function(response) {
                        console.error(response);
                        alert(response.responseText);
                    });
                }
            }
            executeQuery();
        });
    </script>
    <br class="clearfloat">
    <div id="{{ $element_name }}-matches" class="cat_results_wrapper">
    </div>
</div>
<p class="form-text">{!! $tooltip !!}</p>
