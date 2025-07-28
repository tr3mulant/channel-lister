<?php

use IGE\ChannelLister\Http\Controllers\ChannelListerController;
use IGE\ChannelLister\Http\Controllers\ChannelListerFieldController;
use Illuminate\Support\Facades\Route;

Route::get('/channel-lister', [ChannelListerController::class, 'index'])->name('channel-lister');

Route::resource('/channel-lister-fields', ChannelListerFieldController::class)
    ->names([
        'index' => 'channel-lister-fields.index',
        'create' => 'channel-lister-fields.create',
        'edit' => 'channel-lister-fields.edit',
        'show' => 'channel-lister-fields.show',
        'store' => 'channel-lister-fields.store',
        'update' => 'channel-lister-fields.update',
        'destroy' => 'channel-lister-fields.destroy',
    ]);
