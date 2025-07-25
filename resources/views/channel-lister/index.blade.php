@push('footer-scripts')
    <script>
        document.getElementsByTagName("body")[0]
            .insertAdjacentHTML("afterbegin",
                '<div id="loading"><img src="{{ asset('vendor/channel-lister/images/load_large.gif') }}"></div>');
        var platforms = {{ Illuminate\Support\Js::from($platform_json) }};
        console.log(platforms);
    </script>
    <script src="{{ asset('vendor/channel-lister/js/channel-lister.js') }}"></script>
@endpush
<x-channel-lister::layout>
    <div id="form">
        <!-- Nav tabs -->
        <ul class="nav nav-tabs nav-justified">
            <li id="licommon" class="active"><a href="#common" data-toggle="tab">Common</a></li>
            <li id="dropdown" role="presentation" class="dropdown">
                <a class="dropdown-toggle glyphicon glyphicon-plus" data-toggle="dropdown" href="#" role="button"
                    aria-haspopup="true" aria-expanded="false"></a>
                <ul id="dropdownadd" class="dropdown-menu">
                </ul>
            </li>
        </ul>

        <!-- Tab panes -->
        <form name="user_input" autocomplete="off" id="user_input" action="api/ChannelLister/submitProductData"
            accept-charset="UTF-8" method="post" target="_blank">
            <div id="pantab" class="tab-content">
                <div class="tab-pane active platform-container" id="common">
                    {{ 'this should be writeTabeContents("common")' }}
                </div>
            </div>
            <div class="form-group row" id="buttons_div"
                style="display: flex; align-items: center; justify-content: center;">
                <div class="col-sm-3">
                    <input class="btn btn-primary" id='submit_button' value="Submit" type="submit">
                </div>
                <div class="col-sm-3">
                    <div class="row">
                        <div class="col">
                            <input class="form-check-input" type="checkbox" name="gs1" id="gs1-id">
                            <label class="form-check-label">Generate GS1 Csv</label>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            <input class="form-check-input" type="checkbox" name="auto_upload" id="auto_upload-id">
                            <label class="form-check-label">Automatic File Upload to ChannelAdvisor</label>
                        </div>
                    </div>
                </div>
                <!--<input class="btn btn-default" name="Clear" type="button" value="Clear Code" onClick="clearCode()">-->
            </div>
            <div id="databaseResponse"></div>
        </form>
        <div style="display: none;" id="easter_egg"></div>
    </div>
</x-channel-lister::layout>
