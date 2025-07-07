<?php

use IGE\ChannelLister\Http\Controllers\ChannelListerController;
use Illuminate\Support\Facades\Route;

Route::get('/channel-lister', [ChannelListerController::class, 'index'])->name('channel-lister');
