<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V2\Academics\AcademicReferenceController;
use App\Http\Controllers\Api\V2\Academics\CurriculumController;
use App\Http\Controllers\Api\V2\Academics\MarkController;
use App\Http\Controllers\Api\V2\Academics\SubjectStandardController;
use App\Http\Controllers\Api\V2\Academics\TeachingGroupController;
use App\Http\Controllers\Api\V2\Academics\TermCalendarController;
use App\Http\Controllers\Api\V2\People\EnrolmentController;
use App\Http\Controllers\Api\V2\People\ProfileConfigurationController;
use App\Http\Controllers\Api\V2\People\ProfileController;
use App\Http\Controllers\Api\V2\People\ProfileFileController;
use App\Http\Controllers\Api\V2\Platform\ContextController;
use App\Http\Controllers\Api\V2\Platform\GuardianApprovalController;
use App\Http\Controllers\Api\V2\Platform\GuardianInvitationController;
use App\Http\Controllers\Api\V2\Platform\HandleController;
use App\Http\Controllers\Api\V2\Platform\NotificationPreferenceController;
use App\Http\Controllers\Api\V2\School\MySchoolController;
use App\Http\Middleware\AuthorizeClassGrade;
use App\Http\Middleware\AuthorizeCurriculum;
use App\Http\Middleware\AuthorizeMarks;
use App\Http\Middleware\AuthorizeSubjectStandard;
use App\Http\Middleware\AuthorizeTeachingGroup;
use App\Http\Middleware\AuthorizeTermCalendar;
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

// Keep fresh class permissions ahead of idempotent replay as well as validation.
Route::prefix('academics')->middleware(['auth:sanctum', 'school.context', 'role.any', 'learner.approved', 'module:academics', 'cache.headers:private;no_store'])->name('api.v2.academics.')->group(function () {
    Route::prefix('marks')->name('marks.')->group(function () {
        $controller = MarkController::class;
        $policy = AuthorizeMarks::class;
        Route::get('learners/{learner}', [$controller, 'family'])->whereNumber('learner')->middleware($policy.':family')->name('family');
        Route::get('groups/{group}', [$controller, 'matrix'])->whereNumber('group')->middleware($policy.':read')->name('matrix');
        foreach (['preview', 'capture', 'correct', 'moderate'] as $action) {
            Route::post('groups/{group}/tasks/{task}/'.$action, [$controller, $action])->whereNumber('group')->whereNumber('task')
                ->middleware([$policy.':'.$action, 'idempotent'])->name($action);
        }
        Route::post('groups/{group}/release-preview', [$controller, 'releasePreview'])->whereNumber('group')->middleware([$policy.':release', 'idempotent'])->name('release-preview');
        Route::post('groups/{group}/release', [$controller, 'release'])->whereNumber('group')->middleware([$policy.':release', 'idempotent'])->name('release');
    });
    Route::prefix('teaching-groups')->name('teaching-groups.')->middleware(AuthorizeTeachingGroup::class)->group(function () {
        Route::get('/', [TeachingGroupController::class, 'index'])->name('index');
        Route::post('/', [TeachingGroupController::class, 'store'])->middleware('idempotent')->name('store');
        Route::get('{group}', [TeachingGroupController::class, 'show'])->whereNumber('group')->name('show');
        Route::put('{group}', [TeachingGroupController::class, 'update'])->whereNumber('group')->middleware('idempotent')->name('update');
        Route::get('{group}/{kind}', [TeachingGroupController::class, 'rows'])->whereNumber('group')->whereIn('kind', ['memberships', 'teachers'])->name('rows');
        Route::post('{group}/{kind}', [TeachingGroupController::class, 'saveRow'])->whereNumber('group')->whereIn('kind', ['memberships', 'teachers'])->middleware('idempotent')->name('rows.store');
        Route::put('{group}/{kind}/{entry}', [TeachingGroupController::class, 'saveRow'])->whereNumber('group')->whereNumber('entry')->whereIn('kind', ['memberships', 'teachers'])->middleware('idempotent')->name('rows.update');
    });
    Route::prefix('allocations')->name('allocations.')->middleware(AuthorizeTeachingGroup::class)->group(function () {
        Route::get('{kind}', [TeachingGroupController::class, 'rows'])->whereIn('kind', ['homerooms', 'leadership'])->name('index');
        Route::post('{kind}', [TeachingGroupController::class, 'saveRow'])->whereIn('kind', ['homerooms', 'leadership'])->middleware('idempotent')->name('store');
        Route::put('{kind}/{entry}', [TeachingGroupController::class, 'saveRow'])->whereNumber('entry')->whereIn('kind', ['homerooms', 'leadership'])->middleware('idempotent')->name('update');
    });
    Route::prefix('curriculum-plans')->name('curriculum.')->group(function () {
        Route::get('/', [CurriculumController::class, 'index'])->middleware(AuthorizeCurriculum::class.':read')->name('index');
        Route::post('/', [CurriculumController::class, 'store'])->middleware([AuthorizeCurriculum::class.':manage', 'idempotent'])->name('store');
        Route::get('{plan}', [CurriculumController::class, 'show'])->whereNumber('plan')->middleware(AuthorizeCurriculum::class.':read')->name('show');
        Route::put('{plan}', [CurriculumController::class, 'update'])->whereNumber('plan')->middleware([AuthorizeCurriculum::class.':manage', 'idempotent'])->name('update');
        Route::post('{plan}/copy', [CurriculumController::class, 'copy'])->whereNumber('plan')->middleware([AuthorizeCurriculum::class.':copy', 'idempotent'])->name('copy');
        Route::post('{plan}/assessments/{assessment}/publish', [CurriculumController::class, 'publish'])->whereNumber('plan')->whereNumber('assessment')->middleware([AuthorizeCurriculum::class.':publish', 'idempotent'])->name('publish');
    });
    Route::get('subject-standards', [SubjectStandardController::class, 'index'])->middleware(AuthorizeSubjectStandard::class.':read')->name('subject-standards.index');
    Route::get('subjects/{subject}/standard', [SubjectStandardController::class, 'show'])->whereNumber('subject')->middleware(AuthorizeSubjectStandard::class.':list')->name('subjects.standard.show');
    Route::put('subjects/{subject}/standard', [SubjectStandardController::class, 'update'])->whereNumber('subject')->middleware([AuthorizeSubjectStandard::class.':edit', 'idempotent'])->name('subjects.standard.update');
    Route::post('subjects/from-standard', [SubjectStandardController::class, 'store'])->middleware([AuthorizeSubjectStandard::class.':create', 'idempotent'])->name('subjects.standard.store');
    Route::get('terms', [TermCalendarController::class, 'index'])->name('terms.index');
    Route::post('terms', [TermCalendarController::class, 'store'])->middleware([AuthorizeTermCalendar::class.':create', 'idempotent'])->name('terms.store');
    Route::put('terms/{term}', [TermCalendarController::class, 'update'])->whereNumber('term')->middleware([AuthorizeTermCalendar::class.':edit', 'idempotent'])->name('terms.update');
    Route::delete('terms/{term}', [TermCalendarController::class, 'destroy'])->whereNumber('term')->middleware([AuthorizeTermCalendar::class.':delete', 'idempotent'])->name('terms.destroy');
    Route::get('term-templates', [TermCalendarController::class, 'templates'])->middleware(AuthorizeTermCalendar::class.':list')->name('term-templates.index');
    Route::post('term-templates', [TermCalendarController::class, 'saveTemplate'])->middleware([AuthorizeTermCalendar::class.':create', 'idempotent'])->name('term-templates.store');
    Route::post('term-templates/{template}/apply', [TermCalendarController::class, 'applyTemplate'])->whereNumber('template')->middleware([AuthorizeTermCalendar::class.':apply', 'idempotent'])->name('term-templates.apply');
    Route::get('reference', [AcademicReferenceController::class, 'index'])->name('reference');
    Route::middleware([AuthorizeClassGrade::class, 'idempotent'])->group(function () {
        Route::get('classes/{class}/grade-level', [AcademicReferenceController::class, 'show'])->whereNumber('class')->name('class-grade.show');
        Route::put('classes/{class}/grade-level', [AcademicReferenceController::class, 'update'])->whereNumber('class')->name('class-grade.update');
    });
});

// Every signed-in profile: the user, their schools, the current school and
// what the apps need on launch.
Route::middleware(['auth:sanctum', 'school.context', 'role.any', 'learner.approved', 'idempotent'])->name('api.v2.')->group(function () {
    Route::get('schools/{school}/my-school', [MySchoolController::class, 'show'])->whereNumber('school')->middleware('cache.headers:private;no_store');
    Route::post('me/profile/fields/{field}/file', [ProfileFileController::class, 'upload'])->where('field', '[a-z][a-z0-9_]{0,63}');
    Route::post('people/{person}/profile/fields/{field}/file', [ProfileFileController::class, 'uploadPerson'])->whereNumber('person')->where('field', '[a-z][a-z0-9_]{0,63}');
    Route::get('me/profile', [ProfileController::class, 'show'])->middleware('cache.headers:private;no_store');
    Route::put('me/profile', [ProfileController::class, 'update']);
    Route::get('people/{person}/profile', [ProfileController::class, 'show'])->whereNumber('person')->middleware('cache.headers:private;no_store');
    Route::put('people/{person}/profile', [ProfileController::class, 'update'])->whereNumber('person');
    Route::get('profile-fields', [ProfileConfigurationController::class, 'index']);
    Route::post('profile-fields/categories', [ProfileConfigurationController::class, 'category']);
    Route::put('profile-fields/categories/{category}', [ProfileConfigurationController::class, 'category'])->whereNumber('category');
    Route::delete('profile-fields/categories/{category}', [ProfileConfigurationController::class, 'removeCategory'])->whereNumber('category');
    Route::post('profile-fields/definitions', [ProfileConfigurationController::class, 'field']);
    Route::put('profile-fields/definitions/{definition}', [ProfileConfigurationController::class, 'field'])->whereNumber('definition');
    Route::delete('profile-fields/definitions/{definition}', [ProfileConfigurationController::class, 'removeField'])->whereNumber('definition');
    Route::get('me/context', [ContextController::class, 'show'])->name('me.context');
    Route::get('me/notification-preferences', [NotificationPreferenceController::class, 'index']);
    Route::put('me/notification-preferences', [NotificationPreferenceController::class, 'update']);
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
