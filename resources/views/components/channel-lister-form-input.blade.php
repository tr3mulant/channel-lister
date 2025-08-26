@switch($params->input_type)
    @case(\IGE\ChannelLister\Enums\InputType::ALERT)
        <x-channel-lister::alert-message :params="$params" :class-str-default="$classStrDefault" />
    @break

    @case(\IGE\ChannelLister\Enums\InputType::CHECKBOX)
        <x-channel-lister::checkbox-form-input :params="$params" :class-str-default="$classStrDefault" />
    @break

    @case(\IGE\ChannelLister\Enums\InputType::COMMA_SEPARATED)
        <x-channel-lister::comma-separated-form :params="$params" :class-str-default="$classStrDefault" />
    @break

    @case(\IGE\ChannelLister\Enums\InputType::CURRENCY)
        <x-channel-lister::currency-form-input :params="$params" :class-str-default="$classStrDefault" />
    @break

    @case(\IGE\ChannelLister\Enums\InputType::CUSTOM)
        <x-channel-lister::custom.form-input :params="$params" :class-str-default="$classStrDefault" />
    @break

    @case(\IGE\ChannelLister\Enums\InputType::DECIMAL)
        <x-channel-lister::decimal-form-input :params="$params" :class-str-default="$classStrDefault" />
    @break

    @case(\IGE\ChannelLister\Enums\InputType::INTEGER)
        <x-channel-lister::integer-form-input :params="$params" :class-str-default="$classStrDefault" />
    @break

    @case(\IGE\ChannelLister\Enums\InputType::SELECT)
        <x-channel-lister::select-form-input :params="$params" :class-str-default="$classStrDefault" />
    @break

    @case(\IGE\ChannelLister\Enums\InputType::TEXT)
        <x-channel-lister::text-form-input :params="$params" :class-str-default="$classStrDefault" />
    @break

    @case(\IGE\ChannelLister\Enums\InputType::TEXTAREA)
        <x-channel-lister::textarea-form-input :params="$params" :class-str-default="$classStrDefault" />
    @break

    @case(\IGE\ChannelLister\Enums\InputType::URL)
        <x-channel-lister::url-form-input :params="$params" :class-str-default="$classStrDefault" />
    @break

    @default
        {{-- @dump($params->toArray()) --}}
        <div class="alert alert-danger">
            <strong>Error:</strong> Unrecognized input_type: '{{ $params->input_type }}'
        </div>
@endswitch
