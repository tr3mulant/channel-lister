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
            <x-channel-lister::alert-message />
            @break
        
        @case('checkbox')
            <x-channel-lister::checkbox-form-input />
            @break
        
        @case('clonesite-tags')
            <x-channel-lister::clonesite-tags />
            @break
        
        @case('clonesite-cats')
            <x-channel-lister::clonesite-category />
            @break
        
        @case('commaseparated')
            <x-channel-lister::comma-separated-form-input />
            @break
        
        @case('currency')
            <x-channel-lister::currency-form-input />
            @break
        
        @case('custom')
            <x-channel-lister::custom-form-input />
            @break
        
        @case('decimal')
            <x-channel-lister::decimal-form-input />
            @break
        
        @case('integer')
            <x-channel-lister::integer-form-input />
            @break
        
        @case('select')
            <x-channel-lister::select-form-input />
            @break
        
        @case('text')
            <x-channel-lister::text-form-input />
            @break
        
        @case('textarea')
            <x-channel-lister::textarea-form-input />
            @break
        
        @case('url')
            <x-channel-lister::url-form-input />
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