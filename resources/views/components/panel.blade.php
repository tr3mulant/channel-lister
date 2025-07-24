@if (inverted)
    <div class="{{ $this->class }} panel-default" id="{{ $this->id }}">
        <div id="panel-content-{{ $this->idCount }}" class="panel-collapse collapse {{ $start_collapsed ? '' : 'in' }}">
            <div class="panel-body">{{ $this->content }}</div>
        </div>
        <div class="panel-heading sticky-top" data-toggle="collapse" href="#panel-content-{{ $this->idCount }}">
            <h4 class="panel-title">
                <a>{{ $this->title }}</a>
            </h4>
        </div>
    </div>
@else
    <div class="{{ $this->class }} panel-default" id="{{ $this->id }}">
        <div class="panel-heading sticky-top" data-toggle="collapse" href="#panel-content-{{ $this->idCount }}">
            <h4 class="panel-title">
                <a>{{ $this->title }}</a>
            </h4>
        </div>
        <div id="panel-content-{{ $this->idCount }}" class="panel-collapse collapse {{ $start_collapsed ? '' : 'in' }}">
            <div class="panel-body">

                @foreach ($fields as $groups => $field)
                    <x-channel-lister::channel-lister-form-input :params="$field" />
                @endforeach
            </div>
        </div>
    </div>
@endif
