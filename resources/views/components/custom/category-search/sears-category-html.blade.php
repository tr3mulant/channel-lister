<script>
    $('body').on('change', 'div#sears_atts select', function () {
        var selected_options = $('#sears_atts select').map(function () {
            if (this.value != '') return this.value;
        }).get()
        $("[id='Sears Style Or Type-id']").val(selected_options.join(', '));
    });
    function getSearsAttributeInput(id) {
        $("[id='Sears Style Or Type-id']").val('');
        return $.ajax({
            type: 'GET',
            url: 'api/ChannelLister/getCategorySpecificOptions/sears/' + id,
            dataType: 'json'
        }).success(function (response) {
            $html = response.data;
            $('#sears_atts').html($html);
        }).error(function (response) {
            console.error(response);
            alert(response.responseText);
        });
    }
</script>
<x-generic-cat-search params={{ $params }} api-url={{ $apiUrl }} />
<div id='sears_atts'></div>