{{-- resources/views/components/channel-lister-form-input.blade.php --}}

@php
    // Validate required 'type' parameter
    if (!isset($params->type)) {
        throw new RuntimeException("Params missing required field 'type'");
    }
@endphp

@try
    @switch($params->input_type)
        @case('alert')
            @include('channel-lister::components.alert-message')
            @break
        
        @case('checkbox')
            @include('channel-lister::components.checkbox-form-input')
            @break
        
        @case('clonesite-tags')
            @include('channel-lister::components.clonesite-tags')
            @break
        
        @case('clonesite-cats')
            @include('channel-lister::components.clonesite-category')
            @break
        
        @case('commaseparated')
            @include('channel-lister::components.comma-separated-form-input')
            @break
        
        @case('currency')
            @include('channel-lister::components.currency-form-input')
            @break
        
        @case('custom')
            @include('channel-lister::components.custom-form-input')
            @break
        
        @case('decimal')
            @include('channel-lister::components.decimal-form-input')
            @break
        
        @case('integer')
            @include('channel-lister::components.integer-form-input')
            @break
        
        @case('select')
            @include('channel-lister::components.select-form-input')
            @break
        
        @case('text')
            @include('channel-lister::components.text-form-input')
            @break
        
        @case('textarea')
            @include('channel-lister::components.textarea-form-input')
            @break
        
        @case('url')
            @include('channel-lister::components.url-form-input')
            @break
        
        @default
            <div class="alert alert-danger">
                <strong>Error:</strong> Unrecognized input_type: '{{ $params->input_type }}'
            </div>
    @endswitch

@catch(Exception $e)
    <div class="alert alert-danger">
        <strong>Exception:</strong> {{ $e->getMessage() }}
        @if(config('app.debug'))
            <br><small>{{ $e->getFile() }}:{{ $e->getLine() }}</small>
        @endif
    </div>
@endtry