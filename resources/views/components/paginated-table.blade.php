<div class="card">
    <div class="card-body">
        @if ($tableData->count() > 0)
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            @foreach ($columns as $column)
                                <th>{{ $column['label'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody id="table-body">
                        @include('channel-lister::channel-lister-field.partials.table-rows', [
                            'fields' => $tableData,
                        ])
                    </tbody>
                </table>
            </div>
            <div id="pagination-container">
                @include('channel-lister::channel-lister-field.partials.pagination', [
                    'fields' => $tableData,
                ])
            </div>
        @else
            <div class="text-center py-5">
                <h4>{{ $emptyMessage }}</h4>
                @if ($createRoute)
                    <p class="text-muted">Get started by creating your first entry.</p>
                    <a href="{{ $createRoute }}" class="btn btn-primary">
                        {{ $createButtonText }}
                    </a>
                @endif
            </div>
        @endif
    </div>
</div>
