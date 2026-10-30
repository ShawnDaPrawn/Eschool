<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V2\Academics\AcademicReferenceController;
use App\Http\Controllers\Api\V2\Academics\ContentController;
use App\Http\Controllers\Api\V2\Academics\CurriculumController;
use App\Http\Controllers\Api\V2\Academics\MarkController;
use App\Http\Controllers\Api\V2\Academics\RegisterController;
use App\Http\Controllers\Api\V2\Academics\ReportController;
use App\Http\Controllers\Api\V2\Academics\SubjectStandardController;
use App\Http\Controllers\Api\V2\Academics\TeachingGroupController;
use App\Http\Controllers\Api\V2\Academics\TermCalendarController;
use App\Http\Controllers\Api\V2\Academics\TimetableController;
use App\Http\Controllers\Api\V2\Academics\TransitionController;
use App\Http\Controllers\Api\V2\Activities\ActivityFileController;
use App\Http\Controllers\Api\V2\Activities\GroupChatController;
use App\Http\Controllers\Api\V2\Activities\GroupController;
use App\Http\Controllers\Api\V2\Assistance\AssistanceController;
use App\Http\Controllers\Api\V2\Cards\CardController;
use App\Http\Controllers\Api\V2\Cards\CardEncodingController;
use App\Http\Controllers\Api\V2\Cards\CardOfflineController;
use App\Http\Controllers\Api\V2\Classroom\FoundationController;
use App\Http\Controllers\Api\V2\Commerce\TicketController;
use App\Http\Controllers\Api\V2\Commerce\VendorCounterController;
use App\Http\Controllers\Api\V2\Commerce\VendorSessionController;
use App\Http\Controllers\Api\V2\Commerce\WalletPaymentController;
use App\Http\Controllers\Api\V2\Communication\MessageController;
use App\Http\Controllers\Api\V2\Consultations\ConsultationController;
use App\Http\Controllers\Api\V2\Discipline\DisciplineController;
use App\Http\Controllers\Api\V2\Facilitators\SessionController;
use App\Http\Controllers\Api\V2\FamilyForms\FamilyFormController;
use App\Http\Controllers\Api\V2\FamilyVisits\VisitController;
use App\Http\Controllers\Api\V2\Gallery\GalleryController;
use App\Http\Controllers\Api\V2\Growth\MasteryController;
use App\Http\Controllers\Api\V2\Growth\MyGradeController;
use App\Http\Controllers\Api\V2\Growth\ProgressNotesController;
use App\Http\Controllers\Api\V2\Growth\RiskController;
use App\Http\Controllers\Api\V2\Learning\DiaryController;
use App\Http\Controllers\Api\V2\Learning\Digests\DigestController;
use App\Http\Controllers\Api\V2\Learning\ExamCoachController;
use App\Http\Controllers\Api\V2\Learning\PracticeController;
use App\Http\Controllers\Api\V2\Learning\TeachingDraftController;
use App\Http\Controllers\Api\V2\Learning\TutorController;
use App\Http\Controllers\Api\V2\Live\LiveController;
use App\Http\Controllers\Api\V2\Money\ParentFeeController;
use App\Http\Controllers\Api\V2\Money\ParentFeeItemConsentController;
use App\Http\Controllers\Api\V2\Money\StorefrontController;
use App\Http\Controllers\Api\V2\Money\WalletController;
use App\Http\Controllers\Api\V2\Money\WalletDepositController;
use App\Http\Controllers\Api\V2\Money\WalletTransferController;
use App\Http\Controllers\Api\V2\Money\WalletWithdrawalController;
use App\Http\Controllers\Api\V2\Pastoral\CoverController;
use App\Http\Controllers\Api\V2\Pastoral\SupportController;
use App\Http\Controllers\Api\V2\Pathways\PathwayController;
use App\Http\Controllers\Api\V2\PeerTutoring\PeerController;
use App\Http\Controllers\Api\V2\People\EnrolmentController;
use App\Http\Controllers\Api\V2\People\ProfileConfigurationController;
use App\Http\Controllers\Api\V2\People\ProfileController;
use App\Http\Controllers\Api\V2\People\ProfileFileController;
use App\Http\Controllers\Api\V2\Platform\ContextController;
use App\Http\Controllers\Api\V2\Platform\GuardianApprovalController;
use App\Http\Controllers\Api\V2\Platform\GuardianInvitationController;
use App\Http\Controllers\Api\V2\Platform\HandleController;
use App\Http\Controllers\Api\V2\Platform\NotificationPreferenceController;
use App\Http\Controllers\Api\V2\Platform\PrivateStaffNoticeController;
use App\Http\Controllers\Api\V2\Platform\QuietHoursController;
use App\Http\Controllers\Api\V2\Recognition\RecognitionController;
use App\Http\Controllers\Api\V2\School\MySchoolController;
use App\Http\Controllers\Api\V2\Transfers\TransferController;
use App\Http\Controllers\Api\V2\Wellbeing\IncidentPhotoController;
use App\Http\Controllers\Api\V2\Wellbeing\PublicReportingController;
use App\Http\Controllers\Api\V2\Wellbeing\WellbeingController;
use App\Http\Controllers\Api\V2\Wellbeing\WellbeingInboxController;
use App\Http\Middleware\AuthorizeClassGrade;
use App\Http\Middleware\AuthorizeConsultation;
use App\Http\Middleware\AuthorizeCover;
use App\Http\Middleware\AuthorizeCurriculum;
use App\Http\Middleware\AuthorizeMarks;
use App\Http\Middleware\AuthorizeMastery;
use App\Http\Middleware\AuthorizeRecognition;
use App\Http\Middleware\AuthorizeRegister;
use App\Http\Middleware\AuthorizeReport;
use App\Http\Middleware\AuthorizeSubjectStandard;
use App\Http\Middleware\AuthorizeSupport;
use App\Http\Middleware\AuthorizeTeachingGroup;
use App\Http\Middleware\AuthorizeTermCalendar;
use App\Http\Middleware\AuthorizeTimetable;
use App\Http\Middleware\AuthorizeTransfer;
use App\Http\Middleware\AuthorizeWellbeing;
use App\Http\Middleware\CardDeviceContext;
use App\Http\Middleware\CardEncoderContext;
use App\Http\Middleware\FoundationCredentialContext;
use App\Http\Middleware\FoundationStaffContext;
use App\Http\Middleware\PrivateAcademicContent;
use App\Http\Middleware\PrivateDomainResponse;
use App\Http\Middleware\PrivateMastery;
use App\Http\Middleware\PrivateWellbeing;
use App\Http\Middleware\PublicReportingBoundary;
use App\Http\Middleware\RequireCommerceAuthority;
use App\Http\Middleware\RequireFeeItemConsentAuthority;
use App\Http\Middleware\RequireTicketAuthority;
use App\Http\Middleware\RequireVendorCounter;
use App\Http\Middleware\RequireWalletAuthority;
use App\Http\Middleware\RequireWalletDepositAuthority;
use App\Http\Middleware\RequireWalletWithdrawalAuthority;
use App\Services\Recognition\RecognitionQueries;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

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

Route::prefix('consultations')->name('api.v2.consultations.')->middleware([PrivateDomainResponse::class, 'auth:sanctum', 'school.context', 'role.any', 'learner.approved', 'module:consultations,read'])->group(function () {
    $controller = ConsultationController::class;
    foreach (['access' => 'access', 'audiences' => 'audiences', 'managed' => 'managed', '' => 'mine', '{consultation}/configuration' => 'configuration', '{consultation}/results' => 'results', '{consultation}/managed-results' => 'manager-results', '{consultation}/responses' => 'responses', '{consultation}/history' => 'history', '{consultation}/history/{event}' => 'history-entry', '{consultation}' => 'ballot'] as $path => $action) {
        Route::get($path, [$controller, 'read'])->whereNumber(['consultation', 'event'])->middleware(AuthorizeConsultation::class.':'.$action)->name($action);
    }
    foreach (['' => 'create', '{consultation}' => 'edit', '{consultation}/publish' => 'publish', '{consultation}/close' => 'close', '{consultation}/cancel' => 'cancel', '{consultation}/vote' => 'vote'] as $path => $action) {
        Route::post($path, [$controller, 'write'])->whereNumber('consultation')->middleware(AuthorizeConsultation::class.':'.$action)->name($action);
    }
});

// Anonymous transport is deliberately stateless even when a portal cookie exists.
Route::prefix('wellbeing/public/{schoolSlug}')->where(['schoolSlug' => '[a-z0-9][a-z0-9-]*'])->name('api.v2.wellbeing.public.')
    ->withoutMiddleware([EnsureFrontendRequestsAreStateful::class, 'throttle:api'])
    ->middleware([PrivateWellbeing::class, PublicReportingBoundary::class, 'throttle:wellbeing-public'])->group(function () {
        $controller = PublicReportingController::class;
        Route::get('configuration', [$controller, 'action'])->name('categories');
        Route::post('reports', [$controller, 'action'])->middleware('throttle:wellbeing-submit')->name('submit');
        Route::post('follow-up', [$controller, 'action'])->name('read');
        Route::post('follow-up/replies', [$controller, 'action'])->name('reply');
        Route::post('photos', [IncidentPhotoController::class, 'publicPhoto'])->name('photo-upload');
        Route::post('photos/download', [IncidentPhotoController::class, 'publicPhoto'])->name('photo-read');
    });

Route::prefix('wellbeing')->name('api.v2.wellbeing.')->middleware([PrivateWellbeing::class, 'auth:sanctum', 'school.context', 'role.any', 'learner.approved', 'module:wellbeing,read'])->group(function () {
    $inbox = WellbeingInboxController::class;
    Route::get('notices', [$inbox, 'index'])->middleware(AuthorizeWellbeing::class.':own-notices')->name('own-notices');
    Route::post('notices/{notice}/read', [$inbox, 'read'])->whereNumber('notice')->middleware(AuthorizeWellbeing::class.':own-notice-read')->name('own-notice-read');
    $photo = IncidentPhotoController::class;
    Route::post('own/photos', [$photo, 'own'])->middleware(['module:wellbeing,write', AuthorizeWellbeing::class.':own-photo-upload'])->name('own-photo-upload');
    Route::post('own/photos/download', [$photo, 'own'])->middleware(AuthorizeWellbeing::class.':own-photo-read')->name('own-photo-read');
    Route::get('reports/{report}/photos/{photo}', [$photo, 'staff'])->whereNumber('report')->whereUuid('photo')->middleware(AuthorizeWellbeing::class.':report-photo')->name('report-photo');
    $controller = WellbeingController::class;
    $reads = ['access' => 'access', 'configuration' => 'configuration', 'management/cases' => 'managed-cases', 'categories/{category}/team' => 'team-assignments', 'management/cases/{case}/assignments' => 'case-assignments', 'reports' => 'reports', 'reports/{report}' => 'report', 'cases' => 'cases', 'cases/{case}' => 'case', 'cases/{case}/appointments' => 'appointments', 'cases/{case}/professionals' => 'case-professionals', 'cases/{case}/incident-choices' => 'case-report-choices'];
    foreach ($reads as $path => $action) {
        Route::get($path, [$controller, 'read'])->whereNumber(['category', 'case', 'report'])->middleware(AuthorizeWellbeing::class.':'.$action)->name($action);
    }
    $writes = ['categories' => 'category-create', 'categories/{category}' => 'category-save', 'categories/{category}/team' => 'team-create', 'categories/{category}/team/{assignment}' => 'team-save', 'management/cases' => 'case-create', 'management/cases/{case}/assignments' => 'case-assignment-create', 'management/cases/{case}/assignments/{assignment}' => 'case-assignment-save',
        'own/reports' => 'identified-submit', 'own/follow-up' => 'identified-read', 'own/follow-up/replies' => 'identified-reply', 'own/identify' => 'identify', 'reports/{report}/replies' => 'report-respond', 'cases/{case}/entries' => 'case-entry', 'cases/{case}/incident-link' => 'case-link', 'cases/{case}/status' => 'case-status', 'cases/{case}/appointments' => 'appointment-create', 'cases/{case}/appointments/{appointment}' => 'appointment-save'];
    foreach ($writes as $path => $action) {
        $middleware = [AuthorizeWellbeing::class.':'.$action];
        if ($action !== 'identified-read') {
            array_unshift($middleware, 'module:wellbeing,write');
        }
        Route::post($path, [$controller, 'write'])->whereNumber(['category', 'case', 'assignment', 'report', 'appointment'])->middleware($middleware)->name($action);
    }
});

Route::prefix('vendor')->name('api.v2.vendor.')->middleware([PrivateDomainResponse::class])->group(function (): void {
    $session = VendorSessionController::class;
    Route::post('sign-in', [$session, 'signIn'])->middleware(['throttle:login', 'portal.sign-in'])->name('sign-in');
    Route::get('session', [$session, 'show'])->middleware(['auth:sanctum', 'school.context', 'role:Vendor'])->name('session');
    Route::post('sign-out', [$session, 'signOut'])->middleware(['auth:sanctum'])->name('sign-out');
    Route::prefix('counters/{vendor}')->whereNumber('vendor')->middleware(['auth:sanctum', 'school.context', 'role:Vendor', 'module:vendors,read', RequireVendorCounter::class])->group(function (): void {
        $counter = VendorCounterController::class;
        $payments = WalletPaymentController::class;
        Route::get('payments/catalogue', [$payments, 'handle'])->name('payments.catalogue');
        Route::get('payments/receipts/{payment}', [$payments, 'handle'])->whereUuid('payment')->name('payments.receipt');
        Route::get('payments/card/{payment}/portrait', [$payments, 'handle'])->whereUuid('payment')->name('payments.portrait');
        Route::post('payments/lookup', [$payments, 'handle'])->middleware('throttle:money')->name('payments.lookup');
        Route::post('payments/card', [$payments, 'handle'])->middleware('throttle:money')->name('payments.card');
        Route::post('payments/pay', [$payments, 'handle'])->middleware('throttle:money')->name('payments.pay');
        Route::post('payments/operations/{operation}/resolve', [$payments, 'handle'])->whereUuid('operation')->middleware('throttle:money')->name('payments.resolve');
        Route::get('settlements', [$counter, 'handle'])->name('settlements');
        Route::get('settlements/{settlement}', [$counter, 'handle'])->whereNumber('settlement')->name('settlement');
        Route::get('orders', [$counter, 'handle'])->name('orders');
        Route::get('orders/{order}', [$counter, 'handle'])->whereNumber('order')->name('receipt');
        Route::post('lookup', [$counter, 'handle'])->name('lookup');
        Route::post('tickets/lookup', [$counter, 'handle'])->name('tickets.lookup');
        Route::post('ticket-operations/{operation}/resolve', [$counter, 'handle'])->whereUuid('operation')->name('tickets.resolve');
        Route::post('tickets/redeem', [$counter, 'handle'])->middleware('idempotent:required')->name('tickets.redeem');
        Route::post('collect', [$counter, 'handle'])->middleware('idempotent:required')->name('collect');
        Route::post('operations/{operation}/resolve', [$counter, 'handle'])->whereUuid('operation')->name('resolve');
    });
});

Route::prefix('facilitator')->name('api.v2.facilitator.')->group(function () {
    $c = SessionController::class;
    Route::post('sign-in', [$c, 'signIn'])->middleware(['throttle:login', 'portal.sign-in'])->name('sign-in');
    Route::get('session', [$c, 'show'])->middleware(['auth:sanctum', 'school.context', 'role.any'])->name('session');
    Route::post('sign-out', [$c, 'signOut'])->middleware(['auth:sanctum'])->name('sign-out');
});

Route::prefix('activities/groups/{group}/materials/{material}/files')->where(['group' => '[1-9][0-9]{0,17}', 'material' => '[1-9][0-9]{0,17}'])
    ->middleware([PrivateDomainResponse::class, 'auth:sanctum', 'school.context', 'role.any', 'learner.approved', 'module:groups,read'])
    ->name('api.v2.activity-files.')->group(function () {
        $c = ActivityFileController::class;
        Route::get('/', [$c, 'listing'])->name('listing');
        Route::post('/', [$c, 'upload'])->name('upload');
        Route::get('{file}/content', [$c, 'read'])->whereUuid('file')->name('read');
        Route::post('{file}/remove', [$c, 'remove'])->whereUuid('file')->name('remove');
    });

Route::prefix('activities')->middleware([PrivateDomainResponse::class, 'auth:sanctum', 'school.context', 'role.any', 'learner.approved', 'module:groups,read', 'module:chat,read'])
    ->name('api.v2.group-chat.')->group(function () {
        $c = GroupChatController::class;
        Route::get('chat-policy', [$c, 'policy'])->name('policy');
        Route::put('chat-policy', [$c, 'policySave'])->name('policy-save');
        Route::prefix('groups/{group}/chat')->where(['group' => '[1-9][0-9]{0,17}'])->group(function () use ($c) {
            Route::get('operations/{operation}', [$c, 'receipt'])->whereUuid('operation')->name('receipt');
            Route::get('threads', [$c, 'index'])->name('index');
            Route::post('threads', [$c, 'create'])->name('create');
            Route::get('threads/{thread}', [$c, 'show'])->whereUuid('thread')->name('show');
            Route::post('threads/{thread}/messages', [$c, 'send'])->whereUuid('thread')->name('send');
            Route::post('threads/{thread}/state', [$c, 'state'])->whereUuid('thread')->name('state');
            Route::post('threads/{thread}/messages/{message}/remove', [$c, 'remove'])->whereUuid('thread')->where('message', '[1-9][0-9]{0,17}')->name('remove');
            Route::post('threads/{thread}/read', [$c, 'markRead'])->whereUuid('thread')->name('mark-read');
        });
    });

Route::prefix('activities')->middleware(['auth:sanctum', 'school.context', 'role.any', 'learner.approved', 'module:groups', 'cache.headers:private;no_store'])->name('api.v2.activities.')->group(function () {
    $c = GroupController::class;
    Route::get('groups', [$c, 'index'])->name('index');
    Route::post('groups', [$c, 'create'])->name('create');
    Route::get('groups/{group}', [$c, 'show'])->whereNumber('group')->name('show');
    Route::put('groups/{group}', [$c, 'save'])->whereNumber('group')->name('save');
    Route::get('groups/{group}/roster', [$c, 'roster'])->whereNumber('group')->name('roster');
    foreach (['memberships' => ['membership', 'allocation', 'membership-save'], 'staff' => ['staff', 'allocation', 'staff-save'], 'sessions' => ['session', 'session', 'session-save'], 'materials' => ['material', 'material', 'material-save']] as $path => [$method, $parameter, $action]) {
        Route::post('groups/{group}/'.$path, [$c, $method])->whereNumber('group')->name($path.'.'.$action);
        Route::put('groups/{group}/'.$path.'/{'.$parameter.'}', [$c, $method])->whereNumber('group')->whereNumber($parameter)->name($path.'.update.'.$action);
    }
    Route::put('sessions/{session}/attendance', [$c, 'attendance'])->whereNumber('session')->name('attendance-save');
});

// Keep fresh class permissions ahead of idempotent replay as well as validation.
Route::prefix('academics')->middleware(['auth:sanctum', 'school.context', 'role.any', 'learner.approved', 'module:academics', 'cache.headers:private;no_store'])->name('api.v2.academics.')->group(function () {
    Route::prefix('content')->middleware(PrivateAcademicContent::class)->name('content.')->group(function () {
        $controller = ContentController::class;
        Route::get('configuration', [$controller, 'configuration'])->name('configuration');
        Route::get('items', [$controller, 'index'])->name('index');
        Route::post('items', [$controller, 'create'])->name('create');
        Route::get('items/{item}', [$controller, 'show'])->whereNumber('item')->name('show');
        Route::post('items/{item}/source', [$controller, 'upload'])->whereNumber('item')->name('upload');
        Route::post('items/{item}/archive', [$controller, 'archive'])->whereNumber('item')->name('archive');
        Route::put('settings', [$controller, 'settings'])->name('settings');
        Route::get('versions/{version}', [$controller, 'version'])->whereNumber('version')->name('version');
        Route::put('versions/{version}', [$controller, 'save'])->whereNumber('version')->name('save');
        Route::get('versions/{version}/preview', [$controller, 'preview'])->whereNumber('version')->name('preview');
        foreach (['retry', 'publish'] as $action) {
            Route::post('versions/{version}/'.$action, [$controller, $action])->whereNumber('version')->name($action);
        }
        Route::get('learners/{learner}', [$controller, 'library'])->whereNumber('learner')->name('library');
        Route::post('learners/{learner}/saved-access', [$controller, 'savedAccess'])->whereNumber('learner')->name('saved-access');
        Route::get('learners/{learner}/versions/{version}', [$controller, 'reader'])->whereNumber('learner')->whereNumber('version')->name('reader');
        Route::post('learners/{learner}/versions/{version}/read', [$controller, 'read'])->whereNumber('learner')->whereNumber('version')->name('read');
    });
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
    Route::get('me/notification-quiet-hours', [QuietHoursController::class, 'show'])->middleware('cache.headers:private;no_store');
    Route::put('me/notification-quiet-hours', [QuietHoursController::class, 'update']);
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

Route::prefix('commerce')->name('api.v2.commerce.')->middleware([PrivateDomainResponse::class, 'auth:sanctum', 'school.context', 'role.any', 'module:vendors,read'])->group(function () {
    $c = StorefrontController::class;
    $t = TicketController::class;
    $payments = WalletPaymentController::class;
    Route::get('wallet-payments', [$payments, 'handle'])->name('wallet-payments.index');
    Route::get('wallet-payment-operations/{operation}', [$payments, 'handle'])->whereUuid('operation')->name('wallet-payments.outcome');
    Route::get('wallet-payments/{payment}', [$payments, 'handle'])->whereUuid('payment')->name('wallet-payments.show');
    Route::post('wallet-payments', [$payments, 'handle'])->middleware('throttle:money')->name('wallet-payments.issue');
    Route::post('wallet-payments/{payment}/cancel', [$payments, 'handle'])->whereUuid('payment')->middleware('throttle:money')->name('wallet-payments.cancel');
    Route::get('tickets', [$t, 'handle'])->name('tickets.index');
    Route::post('tickets/receive-code', [$t, 'handle'])->middleware('throttle:money')->name('tickets.receive-code');
    Route::get('tickets/{ticket}', [$t, 'handle'])->whereNumber('ticket')->name('tickets.show');
    Route::post('tickets/{ticket}/transfer-preview', [$t, 'handle'])->whereNumber('ticket')->middleware('throttle:money')->name('tickets.transfer-preview');
    Route::post('tickets/{ticket}/transfer', [$t, 'handle'])->whereNumber('ticket')->middleware([RequireTicketAuthority::class, 'throttle:money', 'idempotent:required'])->name('tickets.transfer');
    Route::get('vendors', [$c, 'index'])->name('index');
    Route::get('vendors/{vendor}', [$c, 'catalogue'])->whereNumber('vendor')->name('catalogue');
    Route::get('orders', [$c, 'orders'])->name('orders');
    Route::get('orders/{order}', [$c, 'show'])->whereNumber('order')->name('show');
    Route::post('quotes', [$c, 'quote'])->middleware('throttle:money')->name('quote');
    Route::post('orders', [$c, 'purchase'])->middleware([RequireCommerceAuthority::class, 'throttle:money', 'idempotent:required'])->name('purchase');
    Route::post('orders/{order}/cancel', [$c, 'cancel'])->whereNumber('order')->middleware([RequireCommerceAuthority::class, 'throttle:money', 'idempotent:required'])->name('cancel');
});

Route::prefix('wallets')->name('api.v2.wallets.')->middleware([PrivateDomainResponse::class, 'auth:sanctum', 'school.context', 'role.any', 'module:wallet,read'])->group(function () {
    $c = WalletController::class;
    Route::get('/', [$c, 'index'])->name('index');
    Route::get('withdrawals', [WalletWithdrawalController::class, 'index'])->name('withdrawals.index');
    Route::post('withdrawals', [WalletWithdrawalController::class, 'request'])->middleware([RequireWalletWithdrawalAuthority::class, 'throttle:money', 'idempotent:required'])->name('withdrawals.request');
    Route::post('withdrawals/{withdrawal}/cancel', [WalletWithdrawalController::class, 'cancel'])->whereNumber('withdrawal')->middleware([RequireWalletWithdrawalAuthority::class, 'throttle:money', 'idempotent:required'])->name('withdrawals.cancel');
    Route::get('deposits', [WalletDepositController::class, 'index'])->name('deposits.index');
    Route::post('deposits', [WalletDepositController::class, 'claim'])->middleware([RequireWalletDepositAuthority::class, 'throttle:money', 'idempotent:required'])->name('deposits.claim');
    Route::post('deposits/{claim}/cancel', [WalletDepositController::class, 'cancel'])->whereNumber('claim')->middleware([RequireWalletDepositAuthority::class, 'throttle:money', 'idempotent:required'])->name('deposits.cancel');
    Route::get('transfers', [WalletTransferController::class, 'index'])->name('transfers');
    Route::post('transfers', [WalletTransferController::class, 'transfer'])->middleware([RequireWalletAuthority::class.':transfer', 'throttle:money', 'idempotent:required'])->name('transfer');
    Route::post('operations/{operation}/resolve', [WalletTransferController::class, 'resolve'])->whereUuid('operation')->middleware('throttle:money')->name('resolve');
    Route::get('{wallet}/desk-topups', [$c, 'deskReceipts'])->whereNumber('wallet')->name('desk-receipts');
    Route::get('{wallet}/statement', [$c, 'statement'])->whereNumber('wallet')->name('statement');
    Route::post('/', [$c, 'open'])->middleware([RequireWalletAuthority::class.':open', 'throttle:money', 'idempotent:required'])->name('open');
    Route::put('{wallet}/limits', [$c, 'limits'])->whereNumber('wallet')->middleware([RequireWalletAuthority::class.':limits', 'throttle:money', 'idempotent:required'])->name('limits');
});

Route::prefix('parent/fees')->middleware([PrivateDomainResponse::class, 'auth:sanctum', 'school.context', 'role:Parent', 'parent.policy', 'module:fees,read'])->name('api.v2.parent.fees.')->group(function () {
    $c = ParentFeeController::class;
    Route::get('accounts', [$c, 'index'])->name('accounts');
    Route::get('accounts/{account}/statement', [$c, 'show'])->whereNumber('account')->name('statement');
    Route::get('accounts/{account}/optional-items', [ParentFeeItemConsentController::class, 'index'])->whereNumber('account')->name('optional-items');
    Route::post('accounts/{account}/optional-items/{item}/consents', [ParentFeeItemConsentController::class, 'change'])->whereNumber('account')->whereNumber('item')->middleware([RequireFeeItemConsentAuthority::class, 'throttle:money', 'idempotent:required'])->name('optional-item-consent');
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

Route::prefix('communication')->middleware(['auth:sanctum', 'school.context', 'role.any', 'learner.approved', 'module:communication', 'cache.headers:private;no_store'])->name('api.v2.communication.')->group(function () {
    $c = MessageController::class;
    foreach (['configuration', 'contacts', 'audiences', 'rules', 'drafts'] as $action) {
        Route::get($action, [$c, $action])->name($action);
    }
    Route::post('audiences', [$c, 'audienceCreate'])->name('audience-create');
    Route::put('audiences/{audience}', [$c, 'audienceSave'])->whereNumber('audience')->name('audience-save');
    Route::put('rules/{role}', [$c, 'ruleSave'])->whereNumber('role')->name('rule-save');
    Route::post('drafts', [$c, 'create'])->name('create');
    Route::get('drafts/{message}', [$c, 'editor'])->whereNumber('message')->name('editor');
    Route::put('drafts/{message}', [$c, 'save'])->whereNumber('message')->name('save');
    Route::get('drafts/{message}/preview', [$c, 'preview'])->whereNumber('message')->name('preview');
    foreach (['submit', 'decision', 'withdraw'] as $action) {
        Route::post('drafts/{message}/'.$action, [$c, $action])->whereNumber('message')->name($action);
    }
    Route::get('messages', [$c, 'inbox'])->name('inbox');
    Route::get('messages/{message}', [$c, 'reader'])->whereNumber('message')->name('reader');
    Route::post('messages/{message}/read', [$c, 'read'])->whereNumber('message')->name('read');
});

Route::prefix('growth/mastery')->middleware(['auth:sanctum', 'school.context', 'role.any', 'learner.approved', 'module:mastery', PrivateMastery::class])->name('api.v2.mastery.')->group(function () {
    $controller = MasteryController::class;
    $policy = AuthorizeMastery::class;
    Route::get('configuration', [$controller, 'configuration'])->middleware($policy.':configuration')->name('configuration');
    Route::get('groups/{group}/learners', [$controller, 'roster'])->whereNumber('group')->middleware($policy.':read')->name('roster');
    Route::get('groups/{group}/learners/{learner}', [$controller, 'staff'])->whereNumber('group')->whereNumber('learner')->middleware($policy.':read')->name('staff');
    Route::get('learners/{learner}/subjects', [$controller, 'subjects'])->whereNumber('learner')->middleware($policy.':family')->name('subjects');
    Route::get('learners/{learner}', [$controller, 'family'])->whereNumber('learner')->middleware($policy.':family')->name('family');
    Route::get('subjects/{subject}/graph', [$controller, 'graph'])->whereNumber('subject')->middleware($policy.':graph')->name('graph');
    Route::put('subjects/{subject}/graph', [$controller, 'saveGraph'])->whereNumber('subject')->middleware([$policy.':manage', 'idempotent'])->name('save-graph');
    Route::post('groups/{group}/rebuild', [$controller, 'rebuild'])->whereNumber('group')->middleware([$policy.':rebuild', 'idempotent'])->name('rebuild');
});

Route::prefix('pastoral/cover')->middleware(['auth:sanctum', 'school.context', 'staff', 'module:relief_cover', 'cache.headers:private;no_store'])->name('api.v2.cover.')->group(function () {
    $c = CoverController::class;
    $a = AuthorizeCover::class;
    foreach (['configuration', 'mine'] as $operation) {
        Route::get($operation, [$c, $operation])->middleware($a.':'.$operation)->name($operation);
    }
    Route::post('absences', [$c, 'request'])->middleware($a.':request')->name('request');
    Route::get('absences/{absence}', [$c, 'absence'])->whereNumber('absence')->middleware($a.':absence')->name('absence');
    foreach (['refresh', 'cancel'] as $operation) {
        Route::post('absences/{absence}/'.$operation, [$c, $operation])->whereNumber('absence')->middleware($a.':'.$operation)->name($operation);
    }
    Route::get('occurrences/{occurrence}', [$c, 'occurrence'])->whereNumber('occurrence')->middleware($a.':occurrence')->name('occurrence');
    Route::get('occurrences/{occurrence}/candidates', [$c, 'candidates'])->whereNumber('occurrence')->middleware($a.':candidates')->name('candidates');
    Route::put('occurrences/{occurrence}/assignment', [$c, 'assign'])->whereNumber('occurrence')->middleware($a.':assign')->name('assign');
});

Route::prefix('growth/progress')->middleware(['auth:sanctum', 'school.context', 'role.any', 'learner.approved', 'module:academics', PrivateMastery::class])->name('api.v2.progress.')->group(function () {
    $c = ProgressNotesController::class;
    Route::get('configuration', [$c, 'configuration'])->name('configuration');
    Route::put('settings', [$c, 'configure'])->name('configure');
    Route::get('caseload', [$c, 'caseload'])->name('caseload');
    Route::get('assignment-learners', [$c, 'assignmentLearners'])->name('assignment-learners');
    Route::get('groups/{group}/learners', [$c, 'roster'])->whereNumber('group')->name('roster');
    Route::get('groups/{group}/learners/{learner}', [$c, 'staff'])->whereNumber('group')->whereNumber('learner')->name('staff');
    Route::get('learners/{learner}/periods', [$c, 'periods'])->whereNumber('learner')->name('periods');
    Route::get('learners/{learner}/bands', [$c, 'family'])->whereNumber('learner')->name('family');
    Route::get('learners/{learner}/notes', [$c, 'notes'])->whereNumber('learner')->name('notes');
    Route::post('learners/{learner}/notes', [$c, 'create'])->whereNumber('learner')->name('create');
    Route::get('notes/{note}', [$c, 'note'])->whereNumber('note')->name('note');
    Route::put('notes/{note}', [$c, 'update'])->whereNumber('note')->name('update');
    Route::get('notes/{note}/history', [$c, 'history'])->whereNumber('note')->name('history');
    Route::get('learners/{learner}/assignments', [$c, 'assignments'])->whereNumber('learner')->name('assignments');
    Route::put('learners/{learner}/assignments/{staff}', [$c, 'assign'])->whereNumber('learner')->whereNumber('staff')->name('assign');
    Route::post('learners/{learner}/assignments/{staff}/revoke', [$c, 'revokeAssignment'])->whereNumber('learner')->whereNumber('staff')->name('revoke-assignment');
});

Route::prefix('pastoral/support')->middleware(['auth:sanctum', 'school.context', 'staff', 'module:support_plans'])->name('api.v2.support.')->group(function () {
    $c = SupportController::class;
    $a = AuthorizeSupport::class;
    $routes = [
        ['get', 'access', 'access', 'access'], ['get', 'configuration/{audience}', 'configuration', 'configuration'],
        ['get', 'cases', 'cases', 'cases'], ['get', 'plans/{plan}', 'plan', 'plan'],
        ['get', 'plans/{plan}/reviews', 'reviews', 'reviews'], ['get', 'plans/{plan}/versions', 'versions', 'versions'],
        ['get', 'plans/{plan}/disclosures', 'disclosures', 'disclosures'],
        ['get', 'assignments', 'assignments', 'assignments'], ['get', 'teacher', 'teacher', 'teacher'], ['get', 'teacher/{disclosure}', 'disclosure', 'disclosure'],
        ['post', 'assignments', 'assign', 'assign'], ['post', 'plans', 'create', 'create'], ['put', 'plans/{plan}', 'save', 'save'],
        ['post', 'plans/{plan}/reviews', 'review', 'review'], ['post', 'plans/{plan}/close', 'close', 'close'],
        ['post', 'plans/{plan}/disclosures', 'disclose', 'disclose'], ['post', 'assignments/{assignment}/revoke', 'revokeAssignment', 'revoke-assignment'],
        ['post', 'disclosures/{disclosure}/revoke', 'revokeDisclosure', 'revoke-disclosure'],
    ];
    foreach ($routes as [$verb, $path, $method, $name]) {
        Route::$verb($path, [$c, $method])->where(['audience' => 'principal|professional', 'plan' => '[0-9]+', 'assignment' => '[0-9]+', 'disclosure' => '[0-9]+'])->middleware($a.':'.$name)->name($name);
    }
});

Route::prefix('growth/risk')->middleware([PrivateMastery::class, 'auth:sanctum', 'school.context', 'staff', 'module:early_warning'])->name('api.v2.risk.')->group(function () {
    $c = RiskController::class;
    Route::get('configuration', [$c, 'configuration'])->name('configuration');
    Route::put('settings', [$c, 'configure'])->name('configure');
    Route::get('signals', [$c, 'signals'])->name('signals');
    Route::get('groups/{group}', [$c, 'classroom'])->whereNumber('group')->name('classroom');
    Route::get('learners', [$c, 'learners'])->name('learners');
    Route::get('learners/{learner}/owners', [$c, 'owners'])->whereNumber('learner')->name('owners');
    Route::get('learners/{learner}/professionals', [$c, 'professionals'])->whereNumber('learner')->name('professionals');
    Route::get('learners/{learner}/assignments', [$c, 'assignments'])->whereNumber('learner')->name('assignments');
    Route::put('learners/{learner}/assignments/{professional}', [$c, 'assign'])->whereNumber('learner')->whereNumber('professional')->name('assign');
    Route::post('learners/{learner}/assignments/{professional}/revoke', [$c, 'revoke'])->whereNumber('learner')->whereNumber('professional')->name('revoke');
    Route::get('interventions', [$c, 'interventions'])->name('interventions');
    Route::post('learners/{learner}/interventions', [$c, 'create'])->whereNumber('learner')->name('create');
    Route::get('interventions/{intervention}', [$c, 'detail'])->whereNumber('intervention')->name('detail');
    Route::get('interventions/{intervention}/history', [$c, 'history'])->whereNumber('intervention')->name('history');
    Route::put('interventions/{intervention}', [$c, 'update'])->whereNumber('intervention')->name('update');
    Route::post('interventions/{intervention}/owner', [$c, 'transfer'])->whereNumber('intervention')->name('transfer');
});

Route::prefix('transfers')->middleware(['auth:sanctum', 'school.context', 'role.any', AuthorizeTransfer::class])->name('api.v2.transfers.')->group(function () {
    $c = TransferController::class;
    Route::get('access', [$c, 'access'])->name('access');
    Route::get('choices/{kind}', [$c, 'choices'])->whereIn('kind', ['learners', 'destinations', 'guardians', 'reports', 'documents', 'years', 'rooms', 'categories'])->name('choices');
    Route::post('packages', [$c, 'prepare'])->name('prepare');
    foreach (['consent', 'withdraw', 'release', 'cancel', 'accept'] as $action) {
        Route::post('packages/{package}/'.$action, [$c, $action])->whereNumber('package')->name($action);
    }
    Route::get('{lane}', [$c, 'index'])->whereIn('lane', ['outgoing', 'incoming', 'consents', 'receipts'])->name('index');
    Route::get('{lane}/{record}', [$c, 'show'])->whereIn('lane', ['outgoing', 'incoming', 'consents', 'receipts'])->whereNumber('record')->name('show');
    Route::get('{lane}/{record}/archive', [$c, 'archive'])->whereIn('lane', ['outgoing', 'incoming', 'consents', 'receipts'])->whereNumber('record')->name('archive');
});

// Private encrypted receipts are feature-owned; no global plaintext replay cache.
Route::prefix('discipline')->middleware([PrivateMastery::class, 'auth:sanctum', 'school.context', 'role.any', 'module:discipline'])->name('api.v2.discipline.')->group(function () {
    $controller = DisciplineController::class;
    Route::get('configuration', [$controller, 'configuration'])->name('configuration');
    Route::get('framework', [$controller, 'framework'])->name('framework');
    Route::post('framework', [$controller, 'configure'])->name('configure');
    Route::get('learners', [$controller, 'learners'])->name('learners');
    Route::get('records', [$controller, 'records'])->name('records');
    Route::get('records/{record}', [$controller, 'detail'])->whereNumber('record')->name('detail');
    Route::get('records/{record}/history', [$controller, 'history'])->whereNumber('record')->name('history');
    Route::post('learners/{learner}/records', [$controller, 'create'])->whereNumber('learner')->name('create');
    Route::post('records/{record}/review', [$controller, 'review'])->whereNumber('record')->name('review');
    Route::post('records/{record}/hearing', [$controller, 'hearing'])->whereNumber('record')->name('hearing');
    Route::get('family/learners/{learner}', [$controller, 'family'])->whereNumber('learner')->name('family');
    Route::post('family/records/{record}/acknowledge', [$controller, 'acknowledge'])->whereNumber('record')->name('acknowledge');
    Route::get('detentions', [$controller, 'detentions'])->name('detentions');
    Route::get('supervisors', [$controller, 'supervisors'])->name('supervisors');
    Route::post('detentions', [$controller, 'detentionCreate'])->name('detention_create');
    Route::get('detentions/{detention}', [$controller, 'detentionDetail'])->whereNumber('detention')->name('detention_detail');
    Route::post('detentions/{detention}/entries', [$controller, 'enroll'])->whereNumber('detention')->name('enroll');
    Route::post('detentions/{detention}/entries/{entry}/attendance', [$controller, 'attendance'])->whereNumber('detention')->whereNumber('entry')->name('attendance');
    Route::post('detentions/{detention}/cancel', [$controller, 'cancel'])->whereNumber('detention')->name('cancel');
});
Route::prefix('recognition/{module}')->whereIn('module', ['house_points', 'awards'])->name('api.v2.recognition.')
    ->middleware(['auth:sanctum', 'school.context', 'role.any', 'learner.approved', AuthorizeRecognition::class])->group(function () {
        $c = RecognitionController::class;
        foreach (['access' => 'access', 'configuration' => 'configuration', 'learners' => 'learners', 'counters' => 'counters', 'learners/{learner}/points' => 'history', 'learners/{learner}/awards' => 'profile', 'nomination-catalogue' => 'catalogue', 'nominations' => 'nominations'] as $path => $method) {
            Route::get($path, [$c, $method])->whereNumber('learner')->name($method);
        }
        Route::post('settings', [$c, 'configure'])->name('configure');
        Route::post('catalogue/{kind}', [$c, 'catalogueWrite'])->whereIn('kind', array_keys(RecognitionQueries::CATALOGUES))->name('catalogue-create');
        Route::post('catalogue/{kind}/{record}', [$c, 'catalogueWrite'])->whereIn('kind', array_keys(RecognitionQueries::CATALOGUES))->whereNumber('record')->name('catalogue-save');
        foreach (['points' => 'issue', 'points/{record}/reverse' => 'reverse', 'nominations' => 'nominate', 'nominations/{record}/review' => 'review', 'nominations/{record}/withdraw' => 'withdraw', 'presentations' => 'present', 'outside-awards' => 'outside', 'presentations/{record}/void' => 'void'] as $path => $method) {
            Route::post($path, [$c, $method])->whereNumber('record')->name($method);
        }
    });

// Dedicated staff purpose: exact registered private types and source grants;
// this never opens the legacy Teacher/Firebase notification controller.
Route::prefix('staff/private-notices')->middleware([PrivateMastery::class, 'auth:sanctum', 'school.context', 'role.any', 'module:communication'])->name('api.v2.staff-private-notices.')->group(function () {
    Route::get('/', [PrivateStaffNoticeController::class, 'index'])->name('index');
    Route::post('{notice}/read', [PrivateStaffNoticeController::class, 'read'])->whereNumber('notice')->name('read');
});

Route::prefix('my-grade')->middleware([PrivateDomainResponse::class, 'auth:sanctum', 'school.context', 'role.any', 'module:my_grade'])->name('api.v2.my-grade.')->group(function () {
    Route::get('access', [MyGradeController::class, 'access'])->name('access');
    Route::get('grades/{grade}', [MyGradeController::class, 'dashboard'])->whereNumber('grade')->name('dashboard');
});

Route::prefix('learning/practice')->middleware([PrivateMastery::class, 'auth:sanctum', 'school.context', 'role.any', 'module:daily_practice'])->name('api.v2.practice.')->group(function () {
    $c = PracticeController::class;
    Route::get('access', [$c, 'access'])->name('access');
    Route::get('groups/{group}/choices', [$c, 'choices'])->whereNumber('group')->name('choices');
    Route::get('groups/{group}/bank', [$c, 'bank'])->whereNumber('group')->name('bank');
    Route::post('groups/{group}/bank', [$c, 'approve'])->whereNumber('group')->name('approve');
    Route::post('groups/{group}/bank/{item}', [$c, 'approve'])->whereNumber('group')->whereNumber('item')->name('revise');
    Route::post('learners/{learner}/groups/{group}/daily-sets', [$c, 'issue'])->whereNumber('learner')->whereNumber('group')->name('issue');
    Route::get('learners/{learner}/daily-sets/{set}', [$c, 'read'])->whereNumber('learner')->whereNumber('set')->name('read');
    Route::post('learners/{learner}/daily-sets/{set}/entries/{entry}/review', [$c, 'review'])->whereNumber('learner')->whereNumber('set')->whereNumber('entry')->name('review');
});

// Private family history and explicitly assigned current activity sources.
Route::prefix('gallery')->middleware([PrivateDomainResponse::class, 'auth:sanctum', 'school.context', 'role.any', 'module:gallery'])->name('api.v2.gallery.')->group(function () {
    $controller = GalleryController::class;
    Route::get('configuration', [$controller, 'configuration'])->name('configuration');
    Route::get('learners/{learner}/photos', [$controller, 'family'])->whereNumber('learner')->name('family');
    Route::get('groups/{group}/photos', [$controller, 'group'])->whereNumber('group')->name('group');
    Route::get('photos/{photo}/content', [$controller, 'content'])->whereNumber('photo')->name('content');
    Route::post('photos', [$controller, 'upload'])->name('upload');
    Route::post('photos/{photo}/withdraw', [$controller, 'withdraw'])->whereNumber('photo')->name('withdraw');
    Route::post('photos/{photo}/tags', [$controller, 'propose'])->whereNumber('photo')->name('propose');
    Route::post('tags/{tag}/decision', [$controller, 'decide'])->whereNumber('tag')->name('decide');
    Route::post('tags/{tag}/remove', [$controller, 'remove'])->whereNumber('tag')->name('remove');
    Route::post('settings', [$controller, 'settings'])->name('settings');
});

Route::prefix('live')->name('api.v2.live.')->middleware([PrivateDomainResponse::class, 'auth:sanctum', 'school.context', 'role.any', 'learner.approved', 'module:live_updates,read'])->group(function () {
    $controller = LiveController::class;
    Route::post('session', [$controller, 'session'])->name('session');
    Route::post('authorize', [$controller, 'authorizeChannel'])->name('authorize');
    Route::post('groups/{group}/presence', [$controller, 'presence'])->whereNumber('group')->name('presence');
    Route::delete('groups/{group}/presence', [$controller, 'presence'])->whereNumber('group')->name('leave');
});

Route::prefix('cards')->name('api.v2.cards.')->middleware([PrivateDomainResponse::class, 'auth:sanctum', 'school.context', 'role.any'])->group(function () {
    $controller = CardController::class;
    Route::get('configuration', [$controller, 'read'])->name('configuration');
    Route::get('mine', [$controller, 'read'])->name('mine');
    Route::get('managed', [$controller, 'read'])->name('managed');
    Route::get('people', [$controller, 'read'])->name('people');
    Route::post('/', [$controller, 'write'])->name('issue');
    Route::post('portraits', [$controller, 'upload'])->name('portrait.upload');
    Route::get('{card}', [$controller, 'read'])->whereUuid('card')->name('read');
    Route::get('{card}/portrait', [$controller, 'portrait'])->whereUuid('card')->name('portrait');
    foreach (['credential', 'freeze', 'replace', 'restore'] as $action) {
        Route::post('{card}/'.$action, [$controller, 'write'])->whereUuid('card')->name($action);
    }
});

// Dedicated credentials cannot authorize any normal user endpoint.
Route::prefix('card-device')->name('api.v2.cards.device.')->middleware([PrivateDomainResponse::class, CardDeviceContext::class, 'throttle:60,1'])->withoutMiddleware([EnsureFrontendRequestsAreStateful::class])->group(function () {
    $controller = CardController::class;
    Route::get('offline/snapshot', [CardOfflineController::class, 'snapshot'])->name('offline.snapshot');
    Route::post('offline/reconcile', [CardOfflineController::class, 'reconcile'])->name('offline.reconcile');
    Route::post('scan/qr', [$controller, 'scan'])->name('qr');
    Route::post('scan/sun', [$controller, 'scan'])->name('sun');
    Route::get('scans/{scan}/portrait', [$controller, 'portrait'])->whereUuid('scan')->name('portrait');
});

// Private per-child weekly summaries; every read refreshes actual guardian rights.
Route::prefix('digests')->middleware([PrivateDomainResponse::class, 'auth:sanctum', 'school.context', 'role.any', 'module:digest'])->name('api.v2.digests.')->group(function () {
    $c = DigestController::class;
    Route::get('access', [$c, 'access'])->name('access');
    Route::get('templates', [$c, 'templates'])->name('templates');
    Route::post('templates', [$c, 'createTemplate'])->name('create-template');
    Route::post('templates/{template}/review', [$c, 'reviewTemplate'])->whereNumber('template')->name('review-template');
    Route::post('preferences', [$c, 'preferences'])->name('preferences');
    Route::get('learners/{learner}/weeks', [$c, 'list'])->whereNumber('learner')->name('list');
    Route::get('{digest}', [$c, 'show'])->whereNumber('digest')->name('show');
});

Route::prefix('card-readers')->name('api.v2.cards.readers.')->middleware([PrivateDomainResponse::class, 'auth:sanctum', 'school.context', 'role.any'])->group(function () {
    Route::post('/', [CardController::class, 'write'])->name('register');
    Route::delete('{device}', [CardController::class, 'write'])->whereUuid('device')->name('revoke');
});

// Private learner tutoring; parent authority applies only to template summaries.
// The learner's private diary: only the signed-in learner, never a parent, teacher or staff member.
Route::prefix('diary')->middleware([PrivateDomainResponse::class, 'auth:sanctum', 'school.context', 'role.any', 'learner.approved', 'module:diary', 'cache.headers:private;no_store'])->name('api.v2.diary.')->group(function () {
    $c = DiaryController::class;
    Route::get('/', [$c, 'index'])->name('index');
    Route::get('reminders', [$c, 'reminders'])->name('reminders');
    Route::post('entries', [$c, 'store'])->middleware('throttle:60,1')->name('entries.store');
    Route::put('entries/{entry}', [$c, 'update'])->whereNumber('entry')->middleware('throttle:60,1')->name('entries.update');
    Route::delete('entries/{entry}', [$c, 'destroy'])->whereNumber('entry')->middleware('throttle:60,1')->name('entries.destroy');
    Route::put('items/{source}/{item}/state', [$c, 'state'])->whereIn('source', ['homework', 'holiday', 'event'])->whereNumber('item')->middleware('throttle:60,1')->name('items.state');
});

Route::prefix('tutor')->middleware([PrivateDomainResponse::class, 'auth:sanctum', 'school.context', 'role.any', 'module:tutor'])->name('api.v2.tutor.')->group(function () {
    $c = TutorController::class;
    Route::get('configuration', [$c, 'configuration'])->name('configuration');
    Route::get('sessions', [$c, 'index'])->name('index');
    Route::post('sessions', [$c, 'create'])->middleware('throttle:20,1')->name('create');
    Route::get('sessions/{session}', [$c, 'show'])->whereUuid('session')->name('show');
    Route::post('sessions/{session}/checkpoints', [$c, 'issueCheckpoint'])->whereUuid('session')->middleware('throttle:20,1')->name('checkpoints.issue');
    Route::get('sessions/{session}/checkpoints/{checkpoint}', [$c, 'checkpoint'])->whereUuid(['session', 'checkpoint'])->name('checkpoints.show');
    Route::post('sessions/{session}/checkpoints/{checkpoint}/answer', [$c, 'answerCheckpoint'])->whereUuid(['session', 'checkpoint'])->middleware('throttle:20,1')->name('checkpoints.answer');
    Route::post('sessions/{session}/close', [$c, 'close'])->whereUuid('session')->name('close');
    Route::post('sessions/{session}/messages', [$c, 'send'])->whereUuid('session')->middleware('throttle:20,1')->name('send');
    Route::get('sessions/{session}/turns/{operation}', [$c, 'turn'])->whereUuid('session')->whereUuid('operation')->name('turn');
    Route::post('sessions/{session}/photos', [$c, 'upload'])->whereUuid('session')->middleware('throttle:20,1')->name('upload');
    Route::get('sessions/{session}/photos/{photo}', [$c, 'preview'])->whereUuid('session')->whereUuid('photo')->name('preview');
    Route::post('sessions/{session}/photos/{photo}/revoke', [$c, 'revoke'])->whereUuid('session')->whereUuid('photo')->name('revoke');
    Route::get('parent/learners/{learner}/subjects/{subject}/summary', [$c, 'summary'])->whereNumber('learner')->whereNumber('subject')->name('summary');
});

// Private health categories and exact, authenticated activity/trip consent.
Route::prefix('family-forms')->name('api.v2.family-forms.')->middleware([PrivateDomainResponse::class, 'auth:sanctum', 'school.context', 'role.any'])->group(function () {
    $controller = FamilyFormController::class;
    Route::get('configuration', [$controller, 'read'])->name('configuration');
    Route::get('consents', [$controller, 'read'])->name('listing');
    Route::post('consents', [$controller, 'write'])->name('create');
    Route::get('consents/{consent}', [$controller, 'read'])->whereUuid('consent')->name('read');
    Route::get('consents/{consent}/managed', [$controller, 'read'])->whereUuid('consent')->name('managed');
    Route::post('consents/{consent}/cancel', [$controller, 'write'])->whereUuid('consent')->name('cancel');
    Route::post('consents/{consent}/learners/{learner}/response', [$controller, 'write'])->whereUuid('consent')->whereNumber('learner')->name('respond');
    Route::get('learners/{learner}/health', [$controller, 'read'])->whereNumber('learner')->name('health.read');
    Route::put('learners/{learner}/health', [$controller, 'write'])->whereNumber('learner')->name('health.save');
    Route::post('learners/{learner}/health/fields/{field}/file', [$controller, 'upload'])->whereNumber('learner')->where('field', '[a-z][a-z0-9_]{0,63}')->name('health.upload');
    Route::get('learners/{learner}/health/files/{file}', [$controller, 'file'])->whereNumber('learner')->where('file', '[0-9a-f-]{36}\\.(pdf|png|jpg)')->name('health.file');
});

// Licensed school-reviewed papers, staff-only memos and own learner study plans.
Route::prefix('exam-coach')->middleware([PrivateDomainResponse::class, 'auth:sanctum', 'school.context', 'role.any', 'module:exam_coach'])->name('api.v2.exam-coach.')->group(function () {
    $c = ExamCoachController::class;
    Route::get('configuration', [$c, 'configuration'])->name('configuration');
    Route::prefix('staff/groups/{group}')->whereNumber('group')->name('staff.')->group(function () use ($c) {
        Route::get('operations/{operation}', [$c, 'operation'])->whereUuid('operation')->name('operation');
        Route::get('answers', [$c, 'staffAnswers'])->name('answers');
        Route::get('answers/{answer}', [$c, 'staffAnswer'])->whereUuid('answer')->name('answer');
        Route::get('answers/{answer}/photo', [$c, 'staffAnswerPhoto'])->whereUuid('answer')->name('answer-photo');
        Route::post('answers/{answer}/review', [$c, 'reviewAnswer'])->whereUuid('answer')->name('answer-review');
        Route::get('choices', [$c, 'choices'])->name('choices');
        Route::get('papers', [$c, 'staffPapers'])->name('papers');
        Route::post('papers', [$c, 'draft'])->name('draft');
        Route::post('papers/{paper}/versions', [$c, 'draft'])->whereNumber('paper')->name('revise');
        Route::post('papers/{paper}/versions/{version}/publish', [$c, 'publish'])->whereNumber('paper')->whereUuid('version')->name('publish');
        Route::post('papers/{paper}/withdraw', [$c, 'withdraw'])->whereNumber('paper')->name('withdraw');
        Route::get('versions/{version}', [$c, 'staffVersion'])->whereUuid('version')->name('version');
        Route::get('versions/{version}/paper', [$c, 'staffPaper'])->whereUuid('version')->name('paper');
        Route::get('versions/{version}/memo', [$c, 'staffMemo'])->whereUuid('version')->name('memo');
    });
    Route::prefix('groups/{group}')->whereNumber('group')->group(function () use ($c) {
        Route::post('versions/{version}/answers', [$c, 'uploadAnswer'])->whereUuid('version')->middleware('throttle:20,1')->name('answer-upload');
        Route::get('papers', [$c, 'learnerPapers'])->name('papers');
        Route::get('versions/{version}', [$c, 'learnerVersion'])->whereUuid('version')->name('version');
        Route::get('versions/{version}/paper', [$c, 'learnerPaper'])->whereUuid('version')->name('paper');
        Route::get('exams', [$c, 'dates'])->name('exams');
        Route::post('plans', [$c, 'createPlan'])->name('create-plan');
    });
    Route::get('answer-operations/{operation}', [$c, 'answerOperation'])->whereUuid('operation')->name('answer-operation');
    Route::get('answers', [$c, 'answers'])->name('answers');
    Route::get('answers/{answer}', [$c, 'answer'])->whereUuid('answer')->name('answer');
    Route::get('answers/{answer}/photo', [$c, 'answerPhoto'])->whereUuid('answer')->name('answer-photo');
    Route::post('answers/{answer}/feedback', [$c, 'sendAnswer'])->whereUuid('answer')->middleware('throttle:20,1')->name('answer-feedback');
    Route::post('answers/{answer}/revoke', [$c, 'revokeAnswer'])->whereUuid('answer')->name('answer-revoke');
    Route::get('plans', [$c, 'plans'])->name('plans');
    Route::get('plans/{plan}', [$c, 'plan'])->whereUuid('plan')->name('plan');
});

// Teaching proposals remain private to the current allocated teacher.
Route::prefix('teaching-assistant')->name('api.v2.teaching-assistant.')->middleware([PrivateDomainResponse::class, 'auth:sanctum', 'school.context', 'role.any', 'module:teaching_assistant'])->group(function () {
    $c = TeachingDraftController::class;
    Route::get('configuration', [$c, 'configuration'])->name('configuration');
    Route::get('groups/{group}', [$c, 'choices'])->whereNumber('group')->name('choices');
    Route::get('groups/{group}/learners', [$c, 'learners'])->whereNumber('group')->name('learners');
    Route::get('drafts', [$c, 'index'])->name('index');
    Route::post('groups/{group}/drafts', [$c, 'generate'])->whereNumber('group')->middleware('throttle:20,1')->name('generate');
    Route::get('drafts/{draft}', [$c, 'show'])->whereUuid('draft')->name('show');
    Route::get('operations/{operation}', [$c, 'receipt'])->whereUuid('operation')->name('receipt');
    Route::post('drafts/{draft}/review', [$c, 'review'])->whereUuid('draft')->name('review');
    Route::get('drafts/{draft}/pdf', [$c, 'pdf'])->whereUuid('draft')->name('pdf');
    Route::post('groups/{group}/photos', [$c, 'upload'])->whereNumber('group')->middleware('throttle:20,1')->name('upload');
    Route::get('photos/{photo}', [$c, 'preview'])->whereUuid('photo')->name('preview');
    Route::post('photos/{photo}/revoke', [$c, 'revoke'])->whereUuid('photo')->name('revoke');
});
Route::prefix('test-insights')->name('api.v2.test-insights.')->middleware([PrivateDomainResponse::class, 'auth:sanctum', 'school.context', 'role.any', 'module:test_insights'])->group(function () {
    $c = TeachingDraftController::class;
    Route::get('configuration', [$c, 'insightConfiguration'])->name('configuration');
    Route::get('groups/{group}', [$c, 'insightChoices'])->whereNumber('group')->name('choices');
    Route::get('groups/{group}/tasks/{task}', [$c, 'insights'])->whereNumber('group')->whereNumber('task')->name('show');
});

// Family visits have a separate module; cancellation remains possible after shutdown.
Route::prefix('family-visits')->name('api.v2.family-visits.')->middleware([PrivateDomainResponse::class, 'auth:sanctum', 'school.context', 'role.any'])->group(function () {
    $controller = VisitController::class;
    Route::get('configuration', [$controller, 'read'])->name('configuration');
    Route::get('meetings', [$controller, 'read'])->name('meetings.list');
    Route::post('meetings', [$controller, 'write'])->name('meetings.create');
    Route::post('meetings/{slot}/book', [$controller, 'write'])->whereUuid('slot')->name('meetings.book');
    Route::post('meetings/{slot}/unbook', [$controller, 'write'])->whereUuid('slot')->name('meetings.unbook');
    Route::post('meetings/{slot}/cancel', [$controller, 'write'])->whereUuid('slot')->name('meetings.cancel');
    Route::get('passes', [$controller, 'read'])->name('passes.list');
    Route::post('passes', [$controller, 'write'])->name('passes.request');
    Route::post('passes/{pass}/approve', [$controller, 'write'])->whereUuid('pass')->name('passes.approve');
    Route::post('passes/{pass}/cancel', [$controller, 'write'])->whereUuid('pass')->name('passes.cancel');
    Route::put('rules', [$controller, 'write'])->name('rules.save');
});

// A user bearer token cannot exercise delegated gate-reader collection authority.
Route::prefix('card-device')->name('api.v2.family-visits.device.')->middleware([PrivateDomainResponse::class, CardDeviceContext::class, 'throttle:60,1'])->withoutMiddleware([EnsureFrontendRequestsAreStateful::class])->group(function () {
    $controller = VisitController::class;
    Route::get('scans/{scan}/gate-passes', [$controller, 'read'])->whereUuid('scan')->name('lookup');
    Route::post('gate-passes/{pass}/collect', [$controller, 'write'])->whereUuid('pass')->name('collect');
});

Route::prefix('school-assistance')->name('api.v2.school-assistance.')->middleware([PrivateDomainResponse::class, 'auth:sanctum', 'school.context', 'role.any', 'module:ask_school'])->group(function () {
    $controller = AssistanceController::class;
    $get = function (string $path, string $action) use ($controller): void {
        Route::get($path, [$controller, 'read'])->whereNumber('learner')->whereUuid(['document', 'thread', 'turn'])->defaults('assistance_action', $action)->name($action);
    };
    $post = function (string $path, string $action) use ($controller): void {
        Route::post($path, [$controller, 'write'])->whereNumber('learner')->whereUuid(['document', 'thread', 'turn'])->defaults('assistance_action', $action)->name($action);
    };
    $get('configuration', 'configuration');
    $get('family/learners/{learner}/documents', 'documents');
    $get('family/learners/{learner}/documents/{document}', 'document');
    $get('family/learners/{learner}/threads', 'threads');
    $post('family/learners/{learner}/threads', 'thread.open');
    $post('family/learners/{learner}/answers', 'answer.prepare');
    $post('family/learners/{learner}/translations', 'translation.prepare');
    $get('threads/{thread}', 'thread.read');
    $post('threads/{thread}/reply', 'thread.reply');
    $post('threads/{thread}/close', 'thread.close');
    $get('turns/{turn}', 'turn.read');
    $post('turns/{turn}/run', 'turn.run');
    $get('office/queue', 'office.queue');
    $get('office/staff', 'office.staff');
    $get('office/documents', 'office.documents');
    $get('office/documents/{document}', 'office.document');
    $post('office/documents', 'office.publish');
    $post('office/documents/{document}/withdraw', 'office.withdraw');
    $post('office/threads/{thread}/claim', 'office.claim');
    $post('office/threads/{thread}/reassign', 'office.reassign');
    $post('office/learners/{learner}/translations', 'office.translation');
});

// Source maintenance is a write; private hypothetical projections persist nothing.
Route::prefix('pathways')->name('api.v2.pathways.')->middleware([PrivateDomainResponse::class, 'auth:sanctum', 'school.context', 'role.any', 'module:pathway,read', 'throttle:60,1'])->group(function () {
    $controller = PathwayController::class;
    Route::get('configuration', [$controller, 'configuration'])->name('configuration');
    Route::get('references', [$controller, 'index'])->name('references.index');
    Route::get('references/{reference}', [$controller, 'show'])->whereUuid('reference')->name('references.show');
    Route::post('references', [$controller, 'store'])->middleware('module:pathway,write')->name('references.store');
    Route::put('references/{reference}', [$controller, 'update'])->whereUuid('reference')->middleware('module:pathway,write')->name('references.update');
    Route::post('references/{reference}/projection', [$controller, 'project'])->whereUuid('reference')->name('projection');
});

// Supervised staff writes and purpose-bound read-only family arrangements.
Route::prefix('peer-tutoring')->name('api.v2.peer.')->middleware([PrivateDomainResponse::class, 'auth:sanctum', 'school.context', 'role.any', 'module:peer_tutoring,read', 'throttle:60,1'])->group(function () {
    $controller = PeerController::class;
    Route::get('configuration', [$controller, 'configuration'])->name('configuration');
    Route::get('groups/{group}/learners', [$controller, 'learners'])->whereNumber('group')->name('learners');
    Route::get('groups/{group}/learners/{learner}/matches', [$controller, 'matches'])->whereNumber(['group', 'learner'])->name('matches');
    Route::get('sessions', [$controller, 'index'])->name('sessions.index');
    Route::get('sessions/{session}', [$controller, 'show'])->whereUuid('session')->name('sessions.show');
    Route::post('sessions', [$controller, 'store'])->middleware('module:peer_tutoring,write')->name('sessions.store');
    Route::post('sessions/{session}/complete', [$controller, 'complete'])->whereUuid('session')->middleware('module:peer_tutoring,write')->name('sessions.complete');
    Route::post('sessions/{session}/cancel', [$controller, 'cancel'])->whereUuid('session')->name('sessions.cancel');
    Route::post('sessions/{session}/reverse', [$controller, 'reverse'])->whereUuid('session')->middleware('module:peer_tutoring,write')->name('sessions.reverse');
});

// Classroom credentials are purpose-bound and never authenticate a User.
Route::prefix('foundation-classroom')->name('api.v2.foundation.')->middleware([PrivateDomainResponse::class, 'auth:sanctum', 'school.context', 'role.any', FoundationStaffContext::class])->group(function () {
    $c = FoundationController::class;
    Route::get('configuration', [$c, 'configuration'])->defaults('foundation_action', 'configuration')->name('configuration');
    Route::post('groups/{group}/eligibility', [$c, 'staffWrite'])->whereNumber('group')->defaults('foundation_action', 'eligibility')->name('eligibility');
    Route::post('devices', [$c, 'staffWrite'])->defaults('foundation_action', 'register')->name('register');
    Route::delete('devices/{device}', [$c, 'staffWrite'])->whereUuid('device')->defaults('foundation_action', 'device-revoke')->name('device-revoke');
    Route::post('authorizations', [$c, 'staffWrite'])->defaults('foundation_action', 'grant')->name('grant');
    Route::delete('authorizations/{lease}', [$c, 'staffWrite'])->whereUuid('lease')->defaults('foundation_action', 'revoke')->name('revoke');
});
Route::prefix('foundation-device')->name('api.v2.foundation.device.')->middleware([PrivateDomainResponse::class, FoundationCredentialContext::class.':device', 'throttle:60,1'])->withoutMiddleware([EnsureFrontendRequestsAreStateful::class])->group(function () {
    Route::post('select', [FoundationController::class, 'device'])->defaults('foundation_action', 'select')->name('select');
});
Route::prefix('foundation-learner')->name('api.v2.foundation.learner.')->middleware([PrivateDomainResponse::class, FoundationCredentialContext::class.':learner', 'throttle:60,1'])->withoutMiddleware([EnsureFrontendRequestsAreStateful::class])->group(function () {
    $c = FoundationController::class;
    Route::get('context', [$c, 'learner'])->defaults('foundation_action', 'context')->name('context');
    Route::post('practice', [$c, 'learner'])->defaults('foundation_action', 'issue')->name('issue');
    Route::get('practice/{set}', [$c, 'learner'])->whereNumber('set')->defaults('foundation_action', 'read')->name('read');
    Route::post('practice/{set}/entries/{entry}/review', [$c, 'learner'])->whereNumber(['set', 'entry'])->defaults('foundation_action', 'review')->name('review');
    Route::post('leave', [$c, 'learner'])->defaults('foundation_action', 'leave')->name('leave');
});

// Registered encoders have no gate, classroom or ordinary account authority.
Route::prefix('card-encoder')->name('api.v2.cards.encoder.')->middleware([PrivateDomainResponse::class, CardEncoderContext::class, 'throttle:60,1'])->withoutMiddleware([EnsureFrontendRequestsAreStateful::class])->group(function () {
    Route::get('plans/{plan}', [CardEncodingController::class, 'download'])->whereUuid('plan')->name('download');
    Route::post('plans/{plan}/confirm', [CardEncodingController::class, 'confirm'])->whereUuid('plan')->name('confirm');
});
