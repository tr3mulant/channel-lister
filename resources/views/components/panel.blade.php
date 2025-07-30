@if ($inverted)
    <div class="{{ $class }}" id="{{ $id }}">
        <div @class(['panel-collapse collapse', 'show' => !$start_collapsed]) id="panel-content-{{ $id_count }}">
            <div class="card-body">{{ $content }}</div>
        </div>
        <div class="card-header" data-toggle="collapse" href="#panel-content-{{ $id_count }}">
            <h4 class="card-title">
                <a>{{ $title }}</a>
            </h4>
        </div>
    </div>
@else
    <div @class(['border rounded', $class]) id="{{ $id }}">
        <div class="card-header" data-toggle="collapse" href="#panel-content-{{ $id_count }}">
            <h4 class="card-title">
                <a>{{ $title }}</a>
            </h4>
        </div>
        <div @class(['panel-collapse collapse', 'show' => !$start_collapsed]) id="panel-content-{{ $id_count }}">
            <div class="card-body bg-light">

                @foreach ($fields as $groups => $field)
                    <x-channel-lister::channel-lister-form-input :params="$field" />
                @endforeach
            </div>
        </div>
    </div>
@endif
