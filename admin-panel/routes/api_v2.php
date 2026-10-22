<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V2\Platform\ContextController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v2
|--------------------------------------------------------------------------
|
| Loaded by bootstrap/app.php under /api/v2 with the same "api" middleware
| group as v1 (throttle:api, Sanctum's stateful check, upload checks), so
| every route here is limited to 60 requests a minute per user.
|
| Every route sits in a role group: auth:sanctum plus role:<Role> (or staff,
| or role.any for routes every profile calls). openapi.yaml describes every
| route here and the Contracts suite fails on a route it does not describe.
| See wiki/API-Overview "API v2".
|
| New endpoints follow the domain recipe: a thin controller in
| Api/V2/<Domain>, a FormRequest, a service and a JSON Resource.
*/

// Every signed-in profile: the user, their schools, the current school and
// what the apps need on launch.
Route::middleware(['auth:sanctum', 'role.any'])->name('api.v2.')->group(function () {
    Route::get('me/context', [ContextController::class, 'show'])->name('me.context');
});

// Sign-in, one-time codes and password resets (Phase 1): 5 a minute per account.
Route::prefix('auth')->middleware(['throttle:auth'])->name('api.v2.auth.')->group(function () {
    //
});

// Money writes (fees, wallet; Phase 5) go in the "money" group inside their
// profile's group: 30 a minute per token.
$money = ['throttle:money'];

Route::prefix('student')->middleware(['auth:sanctum', 'role:Student', 'student.session.active'])->name('api.v2.student.')->group(function () use ($money) {
    Route::middleware($money)->group(function () {
        //
    });
});

Route::prefix('parent')->middleware(['auth:sanctum', 'role:Parent', 'parent.children.session.active', 'parent.policy'])->name('api.v2.parent.')->group(function () use ($money) {
    Route::middleware($money)->group(function () {
        //
    });
});

Route::prefix('teacher')->middleware(['auth:sanctum', 'role:Teacher', 'teacher.policy'])->name('api.v2.teacher.')->group(function () {
    //
});

// Super Admin and the roles a school creates for its staff (not Parent or Student).
Route::prefix('staff')->middleware(['auth:sanctum', 'staff'])->name('api.v2.staff.')->group(function () use ($money) {
    Route::middleware($money)->group(function () {
        //
    });
});
