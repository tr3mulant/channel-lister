<?php

use IGE\ChannelLister\Http\Controllers\Api\AmazonListingController;
use IGE\ChannelLister\Http\Controllers\Api\ChannelListerController;
use IGE\ChannelLister\Http\Controllers\Api\ChannelListerFieldController;
use IGE\ChannelLister\Http\Controllers\Api\ShippingController;
use IGE\ChannelLister\Http\Middleware\AmazonSpApiAuth;
use Illuminate\Support\Facades\Route;

Route::prefix('channel-lister')->name('api.channel-lister.')->group(function () {
    Route::get('build-modal-view', [ChannelListerController::class, 'buildModalView'])
        ->name('build-modal-view');

    Route::get('get-form-data-by-platform/{platform}', [ChannelListerController::class, 'formDataByPlatform'])
        ->name('get-form-data-by-platform');

    Route::get('build-upc', [ChannelListerController::class, 'buildUpc'])->name('build-upc');

    Route::get('is-upc-valid', [ChannelListerController::class, 'isUpcValid'])->name('is-upc-valid');

    Route::get('add-bundle-component-row', [ChannelListerController::class, 'addBundleComponentRow'])->name('add-bundle-component-row');

    Route::get('getCountryCodeOptions/{country}/{digits}', [ChannelListerController::class, 'getCountryCodeOptions'])->name('get-country-code-options');

    // Legacy endpoint (backward compatibility)
    Route::post('/', [ChannelListerController::class, 'submitProductData'])->name('submit-product-data');

    // ===== UNIFIED DRAFT SYSTEM ENDPOINTS =====

    // Save unified draft from all marketplace tabs
    Route::post('save-draft', [ChannelListerController::class, 'saveDraft'])->name('save-draft');

    // Load specific draft
    Route::get('drafts/{draft}', [ChannelListerController::class, 'loadDraft'])->name('load-draft');

    // Get list of drafts
    Route::get('drafts', [ChannelListerController::class, 'getDrafts'])->name('get-drafts');

    // Export draft in specified format(s)
    Route::post('export-draft/{draft}', [ChannelListerController::class, 'exportDraft'])->name('export-draft');

    // Delete draft
    Route::delete('drafts/{draft}', [ChannelListerController::class, 'deleteDraft'])->name('delete-draft');
});

Route::prefix('channel-lister-field')->name('api.channel-lister-field.')->group(function () {
    Route::get('search', [ChannelListerFieldController::class, 'search'])->name('search');
});

Route::prefix('amazon-listing')->name('api.amazon-listing.')->middleware(['api', AmazonSpApiAuth::class])->group(function () {
    Route::post('search-product-types', [AmazonListingController::class, 'searchProductTypes'])
        ->name('search-product-types');

    Route::post('listing-requirements', [AmazonListingController::class, 'getListingRequirements'])
        ->name('listing-requirements');

    Route::post('existing-listing', [AmazonListingController::class, 'getExistingListing'])
        ->name('existing-listing');

    // Form submission and management
    Route::post('submit', [AmazonListingController::class, 'submitListing'])
        ->name('submit');

    Route::post('validate', [AmazonListingController::class, 'validateListing'])
        ->name('validate');

    Route::post('generate-file', [AmazonListingController::class, 'generateFile'])
        ->name('generate-file');

    Route::get('listings', [AmazonListingController::class, 'getListings'])
        ->name('listings');

    Route::get('listings/{listing}', [AmazonListingController::class, 'getListingStatus'])
        ->name('listing-status');

    Route::get('listings/{listing}/download', [AmazonListingController::class, 'downloadFile'])
        ->name('download-file');
});

Route::prefix('shipping')->name('api.shipping.')->group(function () {
    Route::get('check-api', [ShippingController::class, 'checkApiAvailability'])->name('check-api');
    Route::get('location', [ShippingController::class, 'getLocation'])->name('location');
    Route::post('calculate', [ShippingController::class, 'calculateRates'])->name('calculate');
    Route::get('carriers', [ShippingController::class, 'getCarriers'])->name('carriers');
    Route::post('dimensional-weight', [ShippingController::class, 'calculateDimensionalWeight'])->name('dimensional-weight');
});
