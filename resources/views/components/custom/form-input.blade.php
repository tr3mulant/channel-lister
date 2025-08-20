@switch($params->field_name)
    @case('UPC')
    @case('amazon_upc')

    @case('upc_walmart')
    @case('upc_ebay')

    @case('upc_newegg')
    @case('upc_sears')

    @case('upc_wish')
        <x-channel-lister::custom.upc :params="$params" :class-str-default="$classStrDefault" />
    @break

    @case('Labels')
        <x-channel-lister::custom.label :params="$params" :class-str-default="$classStrDefault" />
    @break

    @case('Cost Shipping')
        <x-channel-lister::custom.cost-shipping :params="$params" :class-str-default="$classStrDefault" />
    @break

    @case('calculated_shipping_service')
        <x-channel-lister::custom.calculated-shipping-service :params="$params" :class-str-default="$classStrDefault" />
    @break

    @case('listed_by')
        <x-channel-lister::custom.listed-by :params="$params" :class-str-default="$classStrDefault" />
    @break

    @case('Bundle Components')
    @case('BundleComponents')
        <x-channel-lister::custom.sku-bundle :params="$params" :class-str-default="$classStrDefault" />
    @break

    @case('prop65')
        <x-channel-lister::custom.prop65 :params="$params" :class-str-default="$classStrDefault" />
    @break

    @case('brand_id_wish')
        <x-channel-lister::custom.wish-brand-directory-input :params="$params" :class-str-default="$classStrDefault" />
    @break

    @case('special_features_amazon')
    @case('thesaurus_attribute_amazon')

    @case('thesaurus_subject_amazon')
    @case('target_audience_amazon')

    @case('specific_uses_amazon')
        <x-channel-lister::custom.amazon-special-refinements :params="$params" :class-str-default="$classStrDefault" />
    @break

    @case('amazon_product_type')
        <x-channel-lister::custom.amazon-product-type-search :params="$params" :class-str-default="$classStrDefault" />
    @break

    @default
        <div class="alert alert-danger">
            <strong>Failure:</strong> Unable to build input field for {{ $params->field_name }}
        </div>
@endswitch
