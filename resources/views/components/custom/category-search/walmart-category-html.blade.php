<script>
    function getWalmartAttributeInput(cat_name, subcat_name) {
        return $.ajax({
            type: 'POST',
            url: 'api/ChannelLister/getWalmartCategorySpecificAttributes/',
            data: { 'cat': cat_name, 'subcat': subcat_name },
            dataType: 'json'
        }).success(function (response) {
            $html = response.data;
            $('#walmart_atts').html($html);
            $('.select-picker').selectpicker();
        }).error(function (response) {
            console.error(response);
            alert(response.responseText);
        });
    }
</script>
<x-generic-cat-search params={{ $params }} api-url={{ $apiUrl }} />
<div id='walmart_atts'></div>
