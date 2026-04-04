<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing');
});

Route::get('/offline', function () {
    return view('offline');
})->name('offline');
