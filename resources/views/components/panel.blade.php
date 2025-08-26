@if ($inverted)
    <div class="{{ $class }}" id="{{ "panel-$panelId-$idCount" }}">
        <div @class(['panel-collapse collapse', 'show' => !$startCollapsed]) id="{{ "panel-content-$panelId-$idCount" }}">
            <div class="card-body">{{ $content }}</div>
        </div>
        <div class="card-header" data-toggle="collapse" href="#{{ "panel-content-$panelId-$idCount" }}">
            <h4 class="card-title">
                <a>{{ $title }}</a>
            </h4>
        </div>
    </div>
@else
    <div @class(['border rounded', $class]) id="{{ "panel-$panelId-$idCount" }}">
        <div class="card-header" data-toggle="collapse" href="#{{ "panel-content-$panelId-$idCount" }}">
            <h4 class="card-title">
                <a>{{ $title }}</a>
            </h4>
        </div>
        <div @class(['panel-collapse collapse', 'show' => !$startCollapsed]) id="{{ "panel-content-$panelId-$idCount" }}">
            <div class="card-body bg-light">

                @foreach ($fields as $groups => $field)
                    <x-channel-lister::channel-lister-form-input :params="$field" />
                @endforeach
            </div>
        </div>
    </div>
@endif
