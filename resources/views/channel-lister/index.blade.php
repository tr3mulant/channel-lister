@push('footer-scripts')
    <script>
        document.getElementsByTagName("body")[0]
            .insertAdjacentHTML("afterbegin",
                '<div id="loading"><img src="{{ asset('vendor/channel-lister/images/load_large.gif') }}"></div>');
        var platforms = {{ Illuminate\Support\Js::from($platform_json) }};
    </script>
    <script src="{{ asset('vendor/channel-lister/js/channel-lister.js') }}"></script>
@endpush
<x-channel-lister::layout>
    <div class="container-fluid mt-2">
        <div id="form">
            <!-- Nav tabs -->
            <ul class="nav nav-tabs nav-justified" role="tablist">
                <li id="licommon" class="nav-item" role="presentation">
                    <a class="nav-link active h-100" href="#common" data-toggle="tab" data-target="#common" role="tab"
                        aria-controls="common" aria-selected="true">Common</a>
                </li>
                <li id="dropdown" role="presentation" class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle h-100 icon-plus" data-toggle="dropdown" href="#"
                        role="button" aria-haspopup="true" aria-expanded="false"></a>
                    <ul id="dropdownadd" class="dropdown-menu">
                    </ul>
                </li>
            </ul>

            <!-- Tab panes -->
            <form name="user_input" autocomplete="off" id="user_input" action="api/ChannelLister/submitProductData"
                accept-charset="UTF-8" method="post" target="_blank" class="border-left border-right border-bottom">
                <div class="container-lg py-4">
                    <div id="pantab" class="tab-content">
                        <div class="tab-pane fade show active platform-container" id="common" role="tabpanel"
                            aria-labelledby="licommon">
                            <x-channel-lister::channel-lister-fields marketplace="common" />
                        </div>
                    </div>
                    <div class="d-flex justify-content-center align-items-center row" id="buttons_div">
                        <div class="col-sm-3">
                            <input class="btn btn-primary" id='submit_button' value="Submit" type="submit">
                        </div>
                    </div>
                    <div id="databaseResponse"></div>
                </div>
            </form>
            <div style="display: none;" id="easter_egg"></div>
        </div>
    </div>
</x-channel-lister::layout>
