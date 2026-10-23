<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V2\People\EnrolmentController;
use App\Http\Controllers\Api\V2\Platform\ContextController;
use App\Http\Controllers\Api\V2\Platform\GuardianApprovalController;
use App\Http\Controllers\Api\V2\Platform\GuardianInvitationController;
use App\Http\Controllers\Api\V2\Platform\HandleController;
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
| Every route sits in a role group: auth:sanctum, school.context (the
| school the request works for, from the user's memberships) and
| role:<Role> (or staff, or role.any for routes every profile calls).
| Writes in these groups accept an Idempotency-Key header (the idempotent
| middleware); money writes require one. openapi.yaml describes every
| route here and the Contracts suite fails on a route it does not
| describe. See wiki/API-Overview "API v2".
|
| New endpoints follow the domain recipe: a thin controller in
| Api/V2/<Domain>, a FormRequest, a service and a JSON Resource.
*/

// Every signed-in profile: the user, their schools, the current school and
// what the apps need on launch.
Route::middleware(['auth:sanctum', 'school.context', 'role.any', 'learner.approved', 'idempotent'])->name('api.v2.')->group(function () {
    Route::get('me/context', [ContextController::class, 'show'])->name('me.context');
    Route::get('me/handle', [HandleController::class, 'show']);
    Route::put('me/handle', [HandleController::class, 'update'])->middleware('throttle:auth');
});

// Sign-in, one-time codes and password resets (Phase 1): 5 a minute per account.
Route::prefix('auth')->middleware(['throttle:auth'])->name('api.v2.auth.')->group(function () {
    //
});

// Money writes (a later phase) go in the "money" group inside their
// profile's group: 30 a minute per token, and every write needs an
// Idempotency-Key.
$money = ['throttle:money', 'idempotent:required'];

Route::prefix('student')->middleware(['auth:sanctum', 'school.context', 'role:Student', 'learner.approved', 'student.session.active', 'idempotent'])->name('api.v2.student.')->group(function () use ($money) {
    Route::middleware($money)->group(function () {
        //
    });
});

Route::prefix('parent')->middleware(['auth:sanctum', 'school.context', 'role:Parent', 'parent.children.session.active', 'parent.policy', 'idempotent'])->name('api.v2.parent.')->group(function () use ($money) {
    Route::middleware($money)->group(function () {
        //
    });
});

Route::prefix('teacher')->middleware(['auth:sanctum', 'school.context', 'role:Teacher', 'teacher.policy', 'idempotent'])->name('api.v2.teacher.')->group(function () {
    //
});

// Super Admin and the roles a school creates for its staff (not Parent or Student).
Route::prefix('staff')->middleware(['auth:sanctum', 'school.context', 'staff', 'idempotent'])->name('api.v2.staff.')->group(function () use ($money) {
    Route::middleware($money)->group(function () {
        //
    });
});

Route::prefix('guardian')->middleware(['auth:sanctum', 'school.context', 'role:Parent', 'idempotent', 'throttle:auth'])->group(function () {
    Route::get('handle-proposals', [HandleController::class, 'proposals']);
    Route::post('handle-proposals/{proposal}', [HandleController::class, 'decide'])->whereNumber('proposal');
    Route::get('approvals', [GuardianApprovalController::class, 'index']);
    Route::post('approvals/{learner}', [GuardianApprovalController::class, 'store'])->whereNumber('learner');
});

Route::prefix('guardian/invitations')->middleware(['school.context:public', 'throttle:auth'])->group(function () {
    Route::get('{token}', [GuardianInvitationController::class, 'show']);
    Route::post('{token}/accept', [GuardianInvitationController::class, 'accept'])->middleware('portal.sign-in');
});
Route::post('guardians/{guardian}/invitations', [GuardianInvitationController::class, 'store'])
    ->whereNumber('guardian')->middleware(['auth:sanctum', 'school.context', 'staff', 'idempotent', 'throttle:auth']);

Route::prefix('staff/learners/{learner}')->whereNumber('learner')->middleware(['auth:sanctum', 'school.context', 'staff', 'idempotent'])->group(function () {
    Route::get('enrolments', [EnrolmentController::class, 'index']);
    Route::post('enrolments', [EnrolmentController::class, 'store']);
});
