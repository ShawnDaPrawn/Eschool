<?php

declare(strict_types=1);

use App\Http\Controllers\Internal\ContentSourceController;
use Illuminate\Support\Facades\Route;

// Private service capabilities never start a browser session.
Route::get('internal/content-source/{school}/{version}/{generation}', ContentSourceController::class)->whereNumber('school')->whereNumber('version')->whereUuid('generation')->name('content-converter.source');
