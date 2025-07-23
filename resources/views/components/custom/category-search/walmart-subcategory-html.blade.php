<script>
    var removedFields = [];
    function getWalmartAttributeInput(subcat_name) {
        return $.ajax({
            type: 'POST',
            url: 'api/ChannelLister/getWalmartSubcategorySpecificAttributes',
            data: { 'subcat': subcat_name },
            dataType: 'json'
        }).success(function (response) {
            console.log(response);
            let html = response.data.html;
            let remove = response.data.remove_attributes;
            removedFields = replaceFields(html, remove, 'walmart_atts', 'walmart_subcat-searchbox', removedFields);
        }).error(function (response) {
            console.error(response);
            let msg = "Something went wrong\nPlease see console for details";
            alert(msg);
        });
    }
</script>
<x-generic-cat-search params={{ $params }} api-url={{ $apiUrl }} />