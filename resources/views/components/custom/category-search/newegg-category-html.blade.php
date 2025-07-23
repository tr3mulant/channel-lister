<script>
    function buildNeweggCategoryAttributes(cat, subcat_id, type) {
        return $.ajax({
            type: "GET",
            url: "api/ChannelLister/getNeweggCategorySpecificAttributes/",
            data: {
                'cat': cat,
                'subcat_id': subcat_id,
                'type': type,
            },
            dataType: "json",
        }).done(function (response) {
            if (response.status === 'success') {
                $('#neweggAttributes').html(response.data);
            } else {
                alert("Failed to load newegg attributes form: " + response.errors.join(', '));
                console.error(response);
            }
        }).fail(function (response) {
            console.error(response);
            alert("Failed to load newegg attributes form!");
        });
    }
</script>
<x-generic-cat-search params={{ $params }} api-url={{ $apiUrl }} />
<div id='neweggAttributes'></div>
