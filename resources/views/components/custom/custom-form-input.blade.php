{{-- resources/views/components/custom-form-input.blade.php --}}

@switch($params->field_name)
    @case('UPC')
    @case('amazon_upc')
    @case('upc_walmart')
    @case('upc_ebay')
    @case('upc_newegg')
    @case('upc_sears')
    @case('upc_wish')
        @include('channel-lister::custom.upc-html')
        @break
    
    @case('Labels')
        @include('channel-lister::custom.label-html')
        @break
    
    @case('item_type_amazon')
        @include('channel-lister::custom.amazon-item-type-html')
        @break
    
    @case('category_dealsonly')
        @include('channel-lister::custom.category-dealsonly-html')
        @break
    
    @case('StoreCategoryID')
    @case('StoreCategoryID2')
        @include('channel-lister::custom.ebay-store-category-html')
        @break
    
    @case('ebay_categories')
        @include('channel-lister::custom.ebay-category-html')
        @break
    
    @case('jet_cat')
        @include('channel-lister::custom.jet-category-html')
        @break
    
    @case('taxonomy_etsy')
        @include('channel-lister::custom.etsy-category-html')
        @break
    
    @case('category_newegg')
        @include('channel-lister::custom.newegg-category-html')
        @break
    
    @case('sears_cat')
        @include('channel-lister::custom.sears-category-html')
        @break
    
    @case('walmart_cat')
        @include('channel-lister::custom.walmart-category-html')
        @break
    
    @case('walmart_subcat')
        @include('channel-lister::custom.walmart-subcategory-html')
        @break
    
    @case('cost_shipping')
        @include('channel-lister::custom.cost-shipping-html')
        @break
    
    @case('calculated_shipping_service')
        @include('channel-lister::custom.calculated-shipping-service-html')
        @break
    
    @case('listed_by')
        @include('channel-lister::custom.listed-by-html')
        @break
    
    @case('Bundle Components')
    @case('BundleComponents')
        @include('channel-lister::custom.sku-bundle-html')
        @break
    
    @case('prop65')
        @include('channel-lister::custom.prop65-html')
        @break
    
    @case('brand_id_wish')
        @include('channel-lister::custom.wish-brand-directory-id-input')
        @break
    
    @case('special_features_amazon')
    @case('thesaurus_attribute_amazon')
    @case('thesaurus_subject_amazon')
    @case('target_audience_amazon')
    @case('specific_uses_amazon')
        @include('channel-lister::custom.amazon-special-refinements-html')
        @break
    
    @default
        <div class="alert alert-danger">
            <strong>Failure:</strong> Unable to build input field for {{ $params->field_name }}
        </div>
@endswitch