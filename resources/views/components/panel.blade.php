@if ($inverted)
    <div class="{{ $class }} panel-default" id="{{ $id }}">
        <div @class(['panel-collapse collapse', 'show' => !$start_collapsed]) id="panel-content-{{ $id_count }}">
            <div class="panel-body">{{ $content }}</div>
        </div>
        <div class="panel-heading sticky-top" data-toggle="collapse" href="#panel-content-{{ $id_count }}">
            <h4 class="panel-title">
                <a>{{ $title }}</a>
            </h4>
        </div>
    </div>
@else
    <div class="{{ $class }} panel-default" id="{{ $id }}">
        <div class="panel-heading sticky-top" data-toggle="collapse" href="#panel-content-{{ $id_count }}">
            <h4 class="panel-title">
                <a>{{ $title }}</a>
            </h4>
        </div>
        <div @class(['panel-collapse collapse', 'show' => !$start_collapsed]) id="panel-content-{{ $id_count }}">
            <div class="panel-body">

                @foreach ($fields as $groups => $field)
                    <x-channel-lister::channel-lister-form-input :params="$field" />
                @endforeach
            </div>
        </div>
    </div>
@endif
