<?php

use App\Http\Controllers\Panel\Academics\NewYearController;
use App\Http\Controllers\Panel\Academics\RegisterPanelController;
use App\Http\Controllers\Panel\Leave\FacilitatorLeaveController;
use App\Http\Controllers\Panel\Leave\LeaveCalendarController;
use App\Http\Controllers\Panel\ModuleReadinessController;
use App\Http\Controllers\Panel\WorkspaceController;
use Illuminate\Support\Facades\Route;

// Required inside the existing Role/auth/school.context/language group.
Route::get('workspace/{section}/{group?}', WorkspaceController::class)
    ->where(['section' => '[a-z-]+', 'group' => '[a-z-]+'])
    ->name('panel.workspace');

Route::get('academic-registers/configuration', [RegisterPanelController::class, 'configuration'])
    ->middleware('cache.headers:private;no_store')->name('academic-registers.configuration');

Route::prefix('facilitator-leave')->name('facilitator-leave.')->group(function () {
    Route::get('/', [FacilitatorLeaveController::class, 'index'])->name('index');
    Route::get('/my-cover', [FacilitatorLeaveController::class, 'cover'])->name('cover');
    Route::get('/create', [FacilitatorLeaveController::class, 'create'])->name('create');
    Route::post('/', [FacilitatorLeaveController::class, 'store'])->name('store');
    Route::get('/{absence}', [FacilitatorLeaveController::class, 'show'])->whereNumber('absence')->name('show');
    Route::post('/{absence}/decision', [FacilitatorLeaveController::class, 'decide'])->whereNumber('absence')->name('decide');
    Route::post('/{absence}/sessions/{session}/cover', [FacilitatorLeaveController::class, 'assign'])->whereNumber(['absence', 'session'])->name('assign');
    Route::get('/{absence}/files/{file}', [FacilitatorLeaveController::class, 'download'])->whereNumber(['absence', 'file'])->name('download');
});

Route::get('leave-calendar', [LeaveCalendarController::class, 'index'])->name('leave-calendar.index');
Route::get('leave-calendar/{leave}', [LeaveCalendarController::class, 'show'])->whereNumber('leave')->name('leave-calendar.show');

Route::get('new-year/result', [NewYearController::class, 'result'])->name('new-year.result');

Route::get('school-modules/readiness', ModuleReadinessController::class)->name('school-modules.readiness');
