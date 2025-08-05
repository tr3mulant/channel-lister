<script>
    function getWalmartAttributeInput(cat_name, subcat_name) {
        return $.ajax({
            type: 'POST',
            url: 'api/ChannelLister/getWalmartCategorySpecificAttributes/',
            data: {
                'cat': cat_name,
                'subcat': subcat_name
            },
            dataType: 'json'
        }).success(function(response) {
            $html = response.data;
            $('#walmart_atts').html($html);
            $('.selectpicker').selectpicker();
        }).error(function(response) {
            console.error(response);
            alert(response.responseText);
        });
    }
</script>
<x-channel-lister::custom.category-search.generic-category-search :params="$params" :api-url="$apiUrl" />
<div id='walmart_atts'></div>
