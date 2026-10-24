<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V2\Academics\AcademicReferenceController;
use App\Http\Controllers\Api\V2\Academics\CurriculumController;
use App\Http\Controllers\Api\V2\Academics\MarkController;
use App\Http\Controllers\Api\V2\Academics\RegisterController;
use App\Http\Controllers\Api\V2\Academics\ReportController;
use App\Http\Controllers\Api\V2\Academics\SubjectStandardController;
use App\Http\Controllers\Api\V2\Academics\TeachingGroupController;
use App\Http\Controllers\Api\V2\Academics\TermCalendarController;
use App\Http\Controllers\Api\V2\Academics\TimetableController;
use App\Http\Controllers\Api\V2\Academics\TransitionController;
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
use App\Http\Middleware\AuthorizeRegister;
use App\Http\Middleware\AuthorizeReport;
use App\Http\Middleware\AuthorizeSubjectStandard;
use App\Http\Middleware\AuthorizeTeachingGroup;
use App\Http\Middleware\AuthorizeTermCalendar;
use App\Http\Middleware\AuthorizeTimetable;
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
    Route::prefix('transitions')->name('transitions.')->group(function () {
        $controller = TransitionController::class;
        Route::get('configuration', [$controller, 'configuration'])->name('configuration');
        Route::get('learners', [$controller, 'learners'])->name('learners');
        Route::get('learners/{learner}', [$controller, 'learner'])->whereNumber('learner')->name('learner');
        Route::put('policies/{grade}', [$controller, 'policy'])->whereNumber('grade')->name('policy');
        Route::put('retention', [$controller, 'retention'])->name('retention');
        foreach (['preview' => 'preview', 'commit' => 'commit', 'exports/preview' => 'exportPreview', 'exports/prepare' => 'exportPrepare'] as $path => $action) {
            Route::post($path, [$controller, $action])->name(str_replace('exports/', 'export-', $path));
        }
        Route::get('exports/{package}', [$controller, 'exportStatus'])->whereNumber('package')->name('export-status');
        Route::post('exports/{package}/retry', [$controller, 'exportRetry'])->whereNumber('package')->name('export-retry');
        Route::get('exports/{package}/download', [$controller, 'exportDownload'])->whereNumber('package')->name('export-download');
    });
    Route::prefix('reports')->name('reports.')->group(function () {
        $controller = ReportController::class;
        $policy = AuthorizeReport::class;
        Route::get('configuration', [$controller, 'configuration'])->middleware($policy.':staff')->name('configuration');
        Route::get('roster', [$controller, 'roster'])->middleware($policy.':staff')->name('roster');
        Route::put('templates', [$controller, 'templates'])->middleware([$policy.':manage', 'idempotent'])->name('templates');
        Route::put('comment-banks', [$controller, 'banks'])->middleware([$policy.':manage', 'idempotent'])->name('comment-banks');
        Route::post('preview', [$controller, 'preview'])->middleware($policy.':selection')->name('preview');
        Route::post('prepare', [$controller, 'prepare'])->middleware([$policy.':selection', 'idempotent'])->name('prepare');
        Route::get('comments', [$controller, 'comments'])->middleware($policy.':staff')->name('comments');
        Route::get('learners/{learner}', [$controller, 'family'])->whereNumber('learner')->middleware($policy.':family')->name('family');
        Route::post('cards/{card}/comment', [$controller, 'comment'])->whereNumber('card')->middleware([$policy.':comment', 'idempotent'])->name('comment');
        Route::get('cards/{card}/access', [$controller, 'access'])->whereNumber('card')->middleware($policy.':family-card')->name('access');
        Route::post('cards/{card}/read', [$controller, 'read'])->whereNumber('card')->middleware([$policy.':family-card', 'idempotent'])->name('read');
        Route::get('cards/{card}/download', [$controller, 'download'])->whereNumber('card')->middleware($policy.':download')->name('download');
        Route::get('batches', [$controller, 'batches'])->middleware($policy.':staff')->name('batches');
        Route::get('batches/{batch}', [$controller, 'show'])->whereNumber('batch')->middleware($policy.':read')->name('show');
        foreach (['source-preview' => 'source', 'release-preview' => 'releasePreview'] as $path => $action) {
            Route::get('batches/{batch}/'.$path, [$controller, $action])->whereNumber('batch')->middleware($policy.':read')->name($path);
        }
        foreach (['refresh', 'release', 'retry'] as $action) {
            Route::post('batches/{batch}/'.$action, [$controller, $action])->whereNumber('batch')->middleware([$policy.':write', 'idempotent'])->name($action);
        }
        foreach (['grade-sign' => ['grade', 'grade_head'], 'principal-sign' => ['principal', 'principal']] as $path => [$action, $role]) {
            Route::post('batches/{batch}/'.$path, [$controller, $action])->whereNumber('batch')->middleware([$policy.':'.$role, 'idempotent'])->name($path);
        }
    });
    Route::prefix('registers')->name('registers.')->group(function () {
        $controller = RegisterController::class;
        $policy = AuthorizeRegister::class;
        Route::get('choices', [$controller, 'choices'])->middleware($policy.':staff')->name('choices');
        Route::post('open', [$controller, 'open'])->middleware($policy.':staff')->name('open');
        Route::get('settings', [$controller, 'settings'])->middleware($policy.':manage')->name('settings.show');
        Route::put('settings', [$controller, 'settings'])->middleware([$policy.':manage', 'idempotent'])->name('settings');
        Route::get('learners/{learner}/today', [$controller, 'today'])->whereNumber('learner')->middleware($policy.':family')->name('today');
        Route::get('learners/{learner}/qr', [$controller, 'qr'])->whereNumber('learner')->middleware($policy.':family')->name('qr');
        Route::post('learners/{learner}/qr/rotate', [$controller, 'rotate'])->whereNumber('learner')->middleware([$policy.':family', 'idempotent'])->name('qr.rotate');
        Route::get('{register}', [$controller, 'show'])->whereNumber('register')->middleware($policy.':read')->name('show');
        // The durable operation UUID is the sync idempotency key. Fresh policy runs first.
        Route::post('{register}/sync', [$controller, 'sync'])->whereNumber('register')->middleware($policy.':sync')->name('sync');
    });
    Route::prefix('timetable')->name('timetable.')->group(function () {
        $controller = TimetableController::class;
        foreach (['week', 'next'] as $action) {
            Route::get($action, [$controller, $action])->middleware(AuthorizeTimetable::class.':read')->name($action);
        }
        Route::middleware(AuthorizeTimetable::class.':manage')->group(function () use ($controller) {
            Route::get('configuration', [$controller, 'configuration'])->name('configuration');
            Route::post('rooms', [$controller, 'room'])->middleware('idempotent')->name('rooms.store');
            Route::put('rooms/{room}', [$controller, 'room'])->whereNumber('room')->middleware('idempotent')->name('rooms.update');
            Route::post('bells', [$controller, 'bells'])->middleware('idempotent')->name('bells.store');
            Route::put('bells/{schedule}', [$controller, 'bells'])->whereNumber('schedule')->middleware('idempotent')->name('bells.update');
            foreach (['preview', 'commit', 'import'] as $action) {
                Route::post($action, [$controller, $action])->middleware('idempotent')->name($action);
            }
        });
    });
    Route::prefix('marks')->name('marks.')->group(function () {
        $controller = MarkController::class;
        $policy = AuthorizeMarks::class;
        Route::get('learners/{learner}/periods', [$controller, 'periods'])->whereNumber('learner')->middleware($policy.':family')->name('periods');
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
