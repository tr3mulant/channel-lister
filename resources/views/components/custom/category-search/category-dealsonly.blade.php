<div class="form-group">
    <label class="col-form-label" for="tbSearch">{{ $label_text }}</label>
    <div id="dows_category_holder">
        <input id="tbSearch" value="" class="form-group" type="text" placeholder="Search...">
        <script>
            $("#tbSearch").keyup(function() {
                var query = $("#tbSearch").val();
                $.ajax({
                    type: "GET",
                    url: "api_webstore.php",
                    data: {
                        'action': "search-categories",
                        'query': query
                    },
                    dataType: "json",
                }).done(function(response) {
                    var tableHTML =
                        "<select id='{{ $id }}' class='select_wide' size='10' name='{{ $element_name }}' >";
                    $.each(response, function(index, value) {
                        tableHTML += "<option value='" + index + "' class='match'>" + value +
                            "</option>";
                    })
                    tableHTML += "</select>";
                    $("div#matchesSelect").html(tableHTML);
                });
            });
            $("#tbSearch").keyup();
        </script>
        <div id="matchesSelect"></div>
        <p class="form-text">{!! $maps_to_text !!}</p>
    </div>
</div>
