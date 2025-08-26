@push('footer-scripts')
    <script src="{{ asset('vendor/channel-lister/js/channel-lister-field.js') }}"></script>
@endpush
<x-channel-lister::layout>
    <div class="container my-4">
        <div class="row">
            <div class="col-lg-12">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h1>Channel Lister Fields</h1>
                    <a href="{{ route('channel-lister-field.create') }}" class="btn btn-primary">
                        Create New Field
                    </a>
                </div>

                @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif
                <!-- Search Form -->
                <div class="card mb-3">
                    <div class="card-body">
                        <form id="search-form" data-search-url="{{ route('api.channel-lister-field.search') }}">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="search">Search</label>
                                        <input type="text" class="form-control" id="search" name="search"
                                            placeholder="Search fields...">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="marketplace">Marketplace</label>
                                        <select class="form-control" id="marketplace" name="marketplace">
                                            <option value="">All</option>
                                            @foreach ($marketplaces as $item)
                                                <option value="{{ $item['id'] }}">{{ $item['name'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="required">Required</label>
                                        <select class="form-control" id="required" name="required">
                                            <option value="">All</option>
                                            <option value="1">Required</option>
                                            <option value="0">Optional</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="per_page">Per Page</label>
                                        <select class="form-control" id="per_page" name="per_page">
                                            <option value="15">15</option>
                                            <option value="25">25</option>
                                            <option value="50">50</option>
                                            <option value="100">100</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <div>
                                            <button type="submit" class="btn btn-primary">Search</button>
                                            <button type="button" class="btn btn-secondary"
                                                id="clear-search">Clear</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <x-channel-lister::paginated-table :table-data="$fields" :columns="$columns"
                    empty-message="No Channel Lister Fields Found" :create-route="route('channel-lister-field.create')"
                    create-button-text="Create New Field" />
            </div>
        </div>
    </div>
</x-channel-lister::layout>
