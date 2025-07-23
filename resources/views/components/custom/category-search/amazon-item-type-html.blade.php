<script>
    var removedFields = [];
    function getAmazonInventoryTemplate(node_path) {
        return $.ajax({
            type: 'POST',
            url: 'api/ChannelLister/getAmazonCategoryFromNodePath/',
            data: { 'node_path': node_path },
            dataType: 'json'
        }).done(function (response) {
            let category = response.data;
            if (category !== '') {
                setAmazonCategoryOption(category);
            }
        }).fail(function (response) {
            alert(response.responseText);
        });
    }
    function getAmazonAttributeInput(node_path) {
        return $.ajax({
            type: 'POST',
            url: 'api/ChannelLister/getAmazonCategorySpecificOptions/',
            data: { 'path': node_path },
            dataType: 'json'
        }).done(function (response) {
            let html = response.data.html;
            let remove = response.data.remove_attributes;
            removedFields = replaceFields(html, remove, 'amazon_atts', 'product_type_amazon-id', removedFields);
        }).fail(function (response) {
            alert(response.responseText);
        });
    }
</script>

{{-- these lines feel important but not sure where to put them or if necessary 
This function is called in a bunch of the other form components, how should I encorporate
this in the laravel version of this app/ package? --}}
{{-- <?php --}}
// $html .= $this->buildGenericCategorySearchHtml($params, 'api/ChannelLister/getAmazonItemTypeOptions/');

<x-generic-cat-search params={{ $params }} api-url={{ $apiUrl }} />