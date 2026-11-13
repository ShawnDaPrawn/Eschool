<?php

declare(strict_types=1);

use App\Http\Controllers\Panel\Community\EventReadinessController;
use App\Http\Middleware\EnforceReadOnlyYears;
use App\Http\Middleware\PrivateDomainResponse;
use Illuminate\Support\Facades\Route;

Route::middleware([PrivateDomainResponse::class, 'auth', 'school.context', EnforceReadOnlyYears::class])->group(function (): void {
    Route::get('events/{event}/readiness', [EventReadinessController::class, 'show'])->whereNumber('event')->name('event-readiness.show');
    Route::post('events/{event}/readiness', [EventReadinessController::class, 'save'])->whereNumber('event')->name('event-readiness.save');
});
