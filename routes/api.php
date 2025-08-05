<?php

use IGE\ChannelLister\Http\Controllers\Api\ChannelListerController;
use IGE\ChannelLister\Http\Controllers\Api\ChannelListerFieldController;
use Illuminate\Support\Facades\Route;

Route::prefix('channel-lister')->name('api.channel-lister.')->group(function () {
    Route::get('build-modal-view', [ChannelListerController::class, 'buildModalView'])
        ->name('build-modal-view');

    Route::get('get-form-data-by-platform/{platform}', [ChannelListerController::class, 'formDataByPlatform'])
        ->name('get-form-data-by-platform');

    Route::get('build-upc', [ChannelListerController::class, 'buildUpc'])->name('build-upc');

    Route::get('is-upc-valid', [ChannelListerController::class, 'isUpcValid'])->name('is-upc-valid');

    Route::get('add-bundle-component-row', [ChannelListerController::class, 'addBundleComponentRow'])->name('add-bundle-component-row');
});

Route::prefix('channel-lister-field')->name('api.channel-lister-field.')->group(function () {
    Route::get('search', [ChannelListerFieldController::class, 'search'])->name('search');
});
