<?php

use App\Http\Controllers\PublicPages\OpenBadgesController;
use Illuminate\Support\Facades\Route;

// Open Badges 3.0 (Action Plan 5.9): public, outside web and api middleware,
// so no browser session, cookie or signed-in identity takes part. A verifier
// learns only what the credential it holds already says.
Route::prefix('open-badges')->name('open-badges.')->middleware('throttle:60,1')->group(function () {
    Route::post('verify', [OpenBadgesController::class, 'verify'])->name('verify');
    Route::get('status/{credential}', [OpenBadgesController::class, 'status'])->whereUuid('credential')->name('status');
    Route::get('issuers/{schoolSlug}', [OpenBadgesController::class, 'issuer'])->where('schoolSlug', '[a-z0-9][a-z0-9-]*')->name('issuer');
});
