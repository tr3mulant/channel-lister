<script>
    var removedFields = [];

    function getAmazonInventoryTemplate(node_path) {
        return $.ajax({
            type: 'POST',
            url: 'api/channel-lister/getAmazonCategoryFromNodePath/',
            data: {
                'node_path': node_path
            },
            dataType: 'json'
        }).done(function(response) {
            let category = response.data;
            if (category !== '') {
                setAmazonCategoryOption(category);
            }
        }).fail(function(response) {
            alert(response.responseText);
        });
    }

    function getAmazonAttributeInput(node_path) {
        return $.ajax({
            type: 'POST',
            url: 'api/channel-lister/getAmazonCategorySpecificOptions/',
            data: {
                'path': node_path
            },
            dataType: 'json'
        }).done(function(response) {
            let html = response.data.html;
            let remove = response.data.remove_attributes;
            removedFields = replaceFields(html, remove, 'amazon_atts', 'product_type_amazon-id', removedFields);
        }).fail(function(response) {
            alert(response.responseText);
        });
    }
</script>

<x-channel-lister::custom.category-search.generic-category-search :params="$params" :api-url="$apiUrl" />
