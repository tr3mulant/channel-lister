<div class="d-flex justify-content-between align-items-center mt-3">
    <div>
        <small class="text-muted" id="results-info">
            @if ($fields->total() > 0)
                Showing {{ $fields->firstItem() }} to {{ $fields->lastItem() }} of {{ $fields->total() }} results
            @else
                No results found
            @endif
        </small>
    </div>
    <div>
        {{ $fields->links('channel-lister::components.paginated-links-bootstrap-4') }}
    </div>
</div>
