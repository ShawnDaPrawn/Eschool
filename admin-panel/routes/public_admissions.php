<?php

use App\Http\Controllers\PublicPages\AdmissionsController;
use App\Http\Middleware\EncryptCookies;
use App\Http\Middleware\PublicIntakeBoundary;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;

// Own CSRF-protected session; ambient panel identity grants no application access.
// No implicit model binding runs before the explicit school boundary.
Route::middleware([EncryptCookies::class, AddQueuedCookiesToResponse::class, StartSession::class, ValidateCsrfToken::class, PublicIntakeBoundary::class, 'throttle:admissions-public'])
    ->prefix('apply/{schoolSlug}/{intakeId}')->where(['schoolSlug' => '[a-z0-9][a-z0-9-]*', 'intakeId' => '[a-f0-9-]{36}'])->name('admissions.public.')->group(function () {
        Route::get('/', [AdmissionsController::class, 'form'])->name('form');
        Route::post('/', [AdmissionsController::class, 'begin'])->middleware('throttle:admissions-create')->name('begin');
        Route::post('/another', [AdmissionsController::class, 'another'])->name('another');
        Route::get('/resume', [AdmissionsController::class, 'resume'])->name('resume');
        Route::post('/resume/request', [AdmissionsController::class, 'resumeRequest'])->middleware('throttle:admissions-resume')->name('resume-request');
        Route::post('/resume', [AdmissionsController::class, 'exchange'])->middleware('throttle:admissions-exchange')->name('exchange');
        Route::get('/application', [AdmissionsController::class, 'application'])->name('application');
        Route::post('/application', [AdmissionsController::class, 'save'])->name('save');
        Route::post('/submit', [AdmissionsController::class, 'submit'])->name('submit');
        Route::post('/offer', [AdmissionsController::class, 'decide'])->name('decide');
        Route::post('/documents', [AdmissionsController::class, 'upload'])->middleware('throttle:admissions-upload')->name('upload');
        Route::post('/documents/read', [AdmissionsController::class, 'document'])->name('document');
        Route::post('/documents/remove', [AdmissionsController::class, 'remove'])->name('remove');
    });
