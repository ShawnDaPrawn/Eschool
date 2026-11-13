<?php

declare(strict_types=1);

use App\Http\Controllers\Panel\People\PeopleWorkspaceController;
use App\Http\Middleware\EnforceReadOnlyYears;
use App\Http\Middleware\PrivateDomainResponse;
use Illuminate\Support\Facades\Route;

Route::middleware([PrivateDomainResponse::class, 'auth', 'school.context', 'role.any', 'learner.approved', EnforceReadOnlyYears::class])->group(function (): void {
    Route::get('people/facilitators', [PeopleWorkspaceController::class, 'facilitators'])->middleware('module:groups,read')->name('people.facilitators.index');
    Route::prefix('people/alumni')->name('people-pool-panel.')->middleware('module:people_pool,read')->group(function (): void {
        Route::get('/', [PeopleWorkspaceController::class, 'index'])->name('index');
        Route::get('invite', [PeopleWorkspaceController::class, 'create'])->name('create');
        Route::post('members', [PeopleWorkspaceController::class, 'invite'])->name('invite');
        Route::get('members/{member}', [PeopleWorkspaceController::class, 'detail'])->whereNumber('member')->name('detail');
        Route::post('members/{member}/vetting', [PeopleWorkspaceController::class, 'vet'])->whereNumber('member')->name('vet');
        Route::get('bookings', [PeopleWorkspaceController::class, 'bookings'])->name('bookings');
        Route::post('bookings', [PeopleWorkspaceController::class, 'book'])->name('book');
        Route::post('bookings/{booking}/cancel', [PeopleWorkspaceController::class, 'cancel'])->whereNumber('booking')->name('cancel');
    });
});
