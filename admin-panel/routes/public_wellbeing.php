<?php

use App\Http\Controllers\PublicPages\IncidentReportingController;
use App\Http\Middleware\PublicReportingBoundary;
use Illuminate\Support\Facades\Route;

// Deliberately outside web/api middleware: no browser session, cookies or
// ambient authenticated identity participates in an anonymous report.
Route::middleware([PublicReportingBoundary::class, 'throttle:wellbeing-public'])
    ->prefix('school/{schoolSlug}/report')->where(['schoolSlug' => '[a-z0-9][a-z0-9-]*'])->name('public.wellbeing.')->group(function () {
        Route::get('/', [IncidentReportingController::class, 'form'])->name('form');
        Route::post('/', [IncidentReportingController::class, 'submit'])->middleware('throttle:wellbeing-submit')->name('submit');
        Route::post('/follow-up', [IncidentReportingController::class, 'followUp'])->name('follow-up');
        Route::post('/replies', [IncidentReportingController::class, 'reply'])->name('reply');
    });
