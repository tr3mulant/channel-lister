@if ($marketplace !== 'common')
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4 class="panel-title">
                <a data-toggle="collapse" href="#collapse{{ $marketplace }}{{ 0 }}">Action</a>
            </h4>
        </div>
        <div id="collapse{{ $marketplace }}{{ 0 }}" class="panel-collapse collapse in">
            <div class="panel-body">
                <select class="marketplace_actions {{ $class_str_default }}" id="action_select_{{ $marketplace }}">
                    <option value="">List</option>
                    <option value="DO NOT LIST - {{ $marketplace_name }}">Mark as Do Not List</option>
                    <option value="Restricted - {{ $marketplace_name }}">Mark as Restricted</option>
                </select>
            </div>
        </div>
    </div>
@endif
@foreach ($fields as $grouping_name => $fields)
    <div class="panel-group">
        <x-channel-lister::panel :fields="$fields" :grouping_name="$grouping_name" panel_num="{{ $loop->index + 1 }}" />
    </div>
@endforeach
