<?php

declare(strict_types=1);

use App\Http\Controllers\Panel\SchoolBusiness\ReenrolmentPanelController;
use App\Http\Middleware\EnforceReadOnlyYears;
use App\Http\Middleware\PrivateDomainResponse;
use Illuminate\Support\Facades\Route;

Route::middleware([PrivateDomainResponse::class, 'auth', 'school.context', 'module:reenrolment,read', EnforceReadOnlyYears::class])
    ->prefix('school-reenrolment')->name('reenrolment-panel.')->group(function (): void {
        Route::get('/', [ReenrolmentPanelController::class, 'index'])->name('index');
        Route::get('new', [ReenrolmentPanelController::class, 'create'])->name('create');
        Route::get('{contract}', [ReenrolmentPanelController::class, 'show'])->whereNumber('contract')->name('show');
        Route::post('/', [ReenrolmentPanelController::class, 'write'])->defaults('operation', 'publish')->name('publish');
        Route::post('{contract}/withdraw', [ReenrolmentPanelController::class, 'write'])->whereNumber('contract')->defaults('operation', 'withdraw')->name('withdraw');
        Route::post('{contract}/reminders', [ReenrolmentPanelController::class, 'write'])->whereNumber('contract')->defaults('operation', 'remind')->name('remind');
    });
