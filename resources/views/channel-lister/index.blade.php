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
    <div class="container-fluid">
            <div id="form">
                <!-- Nav tabs -->
                <ul class="nav nav-tabs nav-justified" role="tablist">
                    <li id="licommon" class="nav-item" role="presentation">
                        <a class="nav-link active" href="#common" data-toggle="tab" data-target="#common" role="tab"
                            aria-controls="common" aria-selected="true">Common</a>
                    </li>
                    <li id="dropdown" role="presentation" class="nav-item dropdown">
                        <a class="dropdown-toggle icon-plus" data-toggle="dropdown" href="#"
                            role="button" aria-haspopup="true" aria-expanded="false"></a>
                        <ul id="dropdownadd" class="dropdown-menu">
                        </ul>
                    </li>
                </ul>

                <!-- Tab panes -->
                <form name="user_input" autocomplete="off" id="user_input" action="api/ChannelLister/submitProductData"
                    accept-charset="UTF-8" method="post" target="_blank">
                    <div id="pantab" class="tab-content">
                        <div class="tab-pane fade show active platform-container" id="common" role="tabpanel"
                            aria-labelledby="licommon">

                        </div>
                    </div>
                    <div class="form-control row" id="buttons_div"
                        style="display: flex; align-items: center; justify-content: center;">
                        <div class="col-sm-3">
                            <input class="btn btn-primary" id='submit_button' value="Submit" type="submit">
                        </div>
                    </div>
                    <div id="databaseResponse"></div>
                </form>
                <div style="display: none;" id="easter_egg"></div>
            </div>
        </div>

    {{-- <div id="form">
        <!-- Nav tabs -->
        {{-- <ul class="nav nav-tabs nav-justified">
            <li id="licommon" class="nav-item active"><a href="#common" data-toggle="tab">Common</a></li>
            <li id="dropdown" role="presentation" class="dropdown">
                <a class="dropdown-toggle icon-plus" data-toggle="dropdown" href="#" role="button"
                    aria-haspopup="true" aria-expanded="false"></a>
                <ul id="dropdownadd" class="dropdown-menu">
                </ul>
            </li>
        </ul> 
        <!-- Nav tabs -->
        <ul class="nav nav-tabs nav-justified">
            <li id="licommon" class="nav-item active"><a href="#common" data-toggle="tab">Common</a></li>
        </ul>

        <!-- Add button below -->
        <div class="add-tab-container">
            <div id="dropdown" role="presentation" class="dropdown">
                <a class="dropdown-toggle icon-plus" data-toggle="dropdown" href="#" role="button"
                    aria-haspopup="true" aria-expanded="false"></a>
                <ul id="dropdownadd" class="dropdown-menu">
                </ul>
            </div>
        </div>

        <!-- Tab panes -->
        <form name="user_input" autocomplete="off" id="user_input" action="api/ChannelLister/submitProductData"
            accept-charset="UTF-8" method="post" target="_blank">
            <div id="pantab" class="tab-content">
                <div class="tab-pane active platform-container" id="common">

                </div>
            </div>
            <div class="row" id="buttons_div"
                style="display: flex; align-items: center; justify-content: center;">
                <div class="col-sm-3">
                    <input class="btn btn-primary" id='submit_button' value="Submit" type="submit">
                </div>
            </div>
            <div id="databaseResponse"></div>
        </form>
        <div style="display: none;" id="easter_egg"></div>
    </div> --}}
</x-channel-lister::layout>
