@if ($marketplace !== 'common')
    <div class="card">
        <div class="card-header">
            <h4 class="card-title">
                <a data-toggle="collapse" href="{{ "#collapse-$marketplace-0" }}">Action</a>
            </h4>
        </div>
        <div id="{{ "collapse-$marketplace-0" }}" class="panel-collapse collapse show">
            <div class="card-body">
                <select @class(['marketplace_actions form-control', $classStrDefault]) id="{{ "action_select_$marketplace" }}">
                    <option value="">List</option>
                    <option value="{{ "DO NOT LIST - $marketplace_name" }}">Mark as Do Not List</option>
                    <option value="{{ "Restricted - $marketplace_name" }}">Mark as Restricted</option>
                </select>
            </div>
        </div>
    </div>
@endif

<div @class([$classStrDefault])>
    @foreach ($fields as $grouping_name => $fields)
        <x-channel-lister::panel :fields="$fields" :grouping-name="$grouping_name" :panel-id="$marketplace" :id-count="$loop->index + 1"
            :start-collapsed="false" />
    @endforeach
</div>
