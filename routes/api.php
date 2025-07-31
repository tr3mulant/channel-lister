<?php

use IGE\ChannelLister\Http\Controllers\Api\ChannelListerController;
use Illuminate\Support\Facades\Route;

Route::prefix('channel-lister')->name('api.')->group(function () {
    Route::get('build-modal-view', [ChannelListerController::class, 'buildModalView'])
        ->name('build-modal-view');

    Route::get('get-form-data-by-platform/{platform}', [ChannelListerController::class, 'formDataByPlatform'])
        ->name('get-form-data-by-platform');

    Route::get('build-upc', [ChannelListerController::class, 'buildUpc'])->name('build-upc');

    Route::get('is-upc-valid', [ChannelListerController::class, 'isUpcValid'])->name('is-upc-valid');
});
