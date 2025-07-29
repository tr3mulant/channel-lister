<div id="{{ $container_id }}">
    <x-channel-lister::select-form-input :params="$params" />
    <div id="prop65-warning-type-container" class="container hidden">
        <x-channel-lister::select-form-input :params="$prop65_warning" />
    </div>
    <div id="prop65-chemical-name-container" class="container hidden">
        <x-channel-lister::select-form-input :params="$prop65_chem_base" />
    </div>
</div>