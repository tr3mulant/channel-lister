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
