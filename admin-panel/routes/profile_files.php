<?php

declare(strict_types=1);

use App\Http\Controllers\People\ProfileAssetController;
use Illuminate\Support\Facades\Route;

// Loaded in the authenticated API middleware group: bearer and portal cookies.
Route::get('me/{file}', [ProfileAssetController::class, 'show'])->where('file', '[0-9a-f-]{36}\\.[a-z0-9]{1,8}')->name('me');
Route::get('people/{person}/{file}', [ProfileAssetController::class, 'showPerson'])->whereNumber('person')->where('file', '[0-9a-f-]{36}\\.[a-z0-9]{1,8}')->name('person');
