{{-- resources/views/components/channel-lister-form-input.blade.php --}}
@php
    // Validate required 'type' parameter
    if (!isset($params->type)) {
        throw new RuntimeException("Params missing required field 'type'");
    }
@endphp
@switch($params->input_type)
    @case('alert')
        <x-channel-lister::alert-message />
    @break

    @case('checkbox')
        <x-channel-lister::checkbox-form-input />
    @break

    @case('clonesite-tags')
        <x-channel-lister::clone-site-tags />
    @break

    @case('clonesite-cats')
        <x-channel-lister::clone-site-category />
    @break

    @case('commaseparated')
        <x-channel-lister::comma-separated-form />
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
