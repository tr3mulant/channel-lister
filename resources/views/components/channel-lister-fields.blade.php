@if ($marketplace !== 'common')
    <div class="card">
        <div class="card-header">
            <h4 class="card-title">
                <a data-toggle="collapse" href="#collapse{{ $marketplace }}{{ 0 }}">Action</a>
            </h4>
        </div>
        <div id="collapse{{ $marketplace }}{{ 0 }}" class="panel-collapse collapse in">
            <div class="card-body">
                <select class="marketplace_actions {{ $classStrDefault }}" id="action_select_{{ $marketplace }}">
                    <option value="">List</option>
                    <option value="DO NOT LIST - {{ $marketplace_name }}">Mark as Do Not List</option>
                    <option value="Restricted - {{ $marketplace_name }}">Mark as Restricted</option>
                </select>
            </div>
        </div>
    </div>
@endif
@foreach ($fields as $grouping_name => $fields)
    <div class="">
        <x-channel-lister::panel :fields="$fields" :grouping_name="$grouping_name" :panel_num="$loop->index + 1" />
    </div>
@endforeach
