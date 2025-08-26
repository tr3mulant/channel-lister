<?php

use IGE\ChannelLister\Http\Controllers\ChannelListerController;
use IGE\ChannelLister\Http\Controllers\ChannelListerFieldController;
use Illuminate\Support\Facades\Route;

Route::get('/channel-lister', [ChannelListerController::class, 'index'])->name('channel-lister');

Route::resource('/channel-lister-field', ChannelListerFieldController::class)
    ->parameters(['channel-lister-field' => 'field'])
    ->names([
        'index' => 'channel-lister-field.index',
        'create' => 'channel-lister-field.create',
        'edit' => 'channel-lister-field.edit',
        'show' => 'channel-lister-field.show',
        'store' => 'channel-lister-field.store',
        'update' => 'channel-lister-field.update',
        'destroy' => 'channel-lister-field.destroy',
    ]);
