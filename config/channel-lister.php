<?php

return [
    'enabled' => env('CHANNEL_LISTER_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Channel Lister Domain
    |--------------------------------------------------------------------------
    |
    | This is the subdomain where Channel Lister will be accessible from. If the
    | setting is null, Channel Lister will reside under the same domain as the
    | application. Otherwise, this value will be used as the subdomain.
    |
    */
    'domain' => env('CHANNEL_LISTER_DOMAIN', null),

    /*
    |--------------------------------------------------------------------------
    | Channel Lister Path
    |--------------------------------------------------------------------------
    |
    | This is the URI path where Channel Lister will be accessible from. Feel free
    | to change this path to anything you like. Note that the URI will not
    | affect the paths of its internal API that aren't exposed to users.
    |
    */
    'path' => env('CHANNEL_LISTER_PATH', null),

    /*
    |--------------------------------------------------------------------------
    | Channel Lister API Path
    |--------------------------------------------------------------------------
    |
    | This is the URI path where Channel Lister's API will be accessible from.
    | Feel free to change this path to anything you like.
    |
    */
    'api_path' => env('CHANNEL_LISTER_API_PATH', 'api'),

    /*
    |--------------------------------------------------------------------------
    | Channel Lister Route Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will be assigned to every Channel Lister route, giving you
    | the chance to add your own middleware to this list or change any of
    | the existing middleware. Or, you can simply stick with this list.
    |
    */
    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Channel Lister Route Middleware
    |--------------------------------------------------------------------------
    |
    | The marketplace.disabled key can be filled with a list of marketplaces that should be disabled
    | for the channel-lister form tabs
    |
    */
    'marketplaces' => [
        'disabled' => [],
    ],

    'upc_prefixes' => [],

    'cache_prefix' => 'channel-lister',

    'default_warehouse' => env('CHANNEL_LISTER_DEFAULT_WAREHOUSE', 'channellister'),

    /*
    |--------------------------------------------------------------------------
    | Amazon SP-API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Amazon Selling Partner API integration. These settings
    | are used for dynamic listing creation and product type search.
    |
    */
    'amazon' => [
        'sp_api_base_url' => env('CHANNEL_LISTER_AMAZON_SP_API_BASE_URL', 'https://sellingpartnerapi-na.amazon.com'),
        'access_token' => env('CHANNEL_LISTER_AMAZON_SP_API_ACCESS_TOKEN'),
        'marketplace_id' => env('CHANNEL_LISTER_AMAZON_MARKETPLACE_ID', 'ATVPDKIKX0DER'), // US marketplace default
        'region' => env('CHANNEL_LISTER_AMAZON_SP_API_REGION', 'us-east-1'),
        'client_id' => env('CHANNEL_LISTER_AMAZON_SP_API_CLIENT_ID'),
        'client_secret' => env('CHANNEL_LISTER_AMAZON_SP_API_CLIENT_SECRET'),
        'refresh_token' => env('CHANNEL_LISTER_AMAZON_SP_API_REFRESH_TOKEN'),

        /*
        |--------------------------------------------------------------------------
        | Amazon Caching Configuration
        |--------------------------------------------------------------------------
        |
        | Configure caching behavior for Amazon SP-API responses and schema files.
        | This helps reduce API calls and improve performance.
        |
        */
        'cache' => [
            // Disk to use for persistent schema caching (local, s3, etc.)
            'disk' => env('CHANNEL_LISTER_AMAZON_CACHE_DISK', 'local'),

            // Cache TTL in seconds
            'ttl' => [
                'product_types_search' => env('CHANNEL_LISTER_AMAZON_CACHE_TTL_PRODUCT_SEARCH', 3600), // 1 hour
                'listing_requirements' => env('CHANNEL_LISTER_AMAZON_CACHE_TTL_REQUIREMENTS', 86400), // 24 hours
                'schema_files' => env('CHANNEL_LISTER_AMAZON_CACHE_TTL_SCHEMA', 604800), // 7 days
            ],

            // Directory path within the disk for schema files
            'schema_path' => env('CHANNEL_LISTER_AMAZON_CACHE_SCHEMA_PATH', 'amazon-schemas'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | ShipStation API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for ShipStation API integration for shipping cost calculation.
    | If api_key is not set, users will need to enter shipping costs manually.
    |
    */
    'shipstation' => [
        'api_key' => env('SHIPSTATION_API_KEY'),
        'base_url' => env('SHIPSTATION_BASE_URL', 'https://api.shipengine.com/v1'),
    ],
];
