<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\Classes;
use App\Http\Controllers\ClassTeacherController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\Exam;
use App\Http\Controllers\ExamTimetableController;
use App\Http\Controllers\FormFieldController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\Leave;
use App\Http\Controllers\LeaveMasterController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\LessonTopicController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\MediumController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OnlineExamController;
use App\Http\Controllers\OnlineExamQuestionController;
use App\Http\Controllers\Panel\Academics\ContentWorkbenchController;
use App\Http\Controllers\Panel\Academics\CurriculumController;
use App\Http\Controllers\Panel\Academics\ExamPeriodController;
use App\Http\Controllers\Panel\Academics\NewYearController;
use App\Http\Controllers\Panel\Academics\RegisterPanelController;
use App\Http\Controllers\Panel\Academics\ReportWorkbenchController;
use App\Http\Controllers\Panel\Academics\SasamsExportController;
use App\Http\Controllers\Panel\Academics\SubjectStandardController;
use App\Http\Controllers\Panel\Academics\TeachingGroupController;
use App\Http\Controllers\Panel\Academics\TimetableWorkbenchController;
use App\Http\Controllers\Panel\Academics\TransitionWorkbenchController;
use App\Http\Controllers\Panel\Activities\ActivityFilePanelController;
use App\Http\Controllers\Panel\Activities\ActivitySeasonController;
use App\Http\Controllers\Panel\Activities\ActivityWorkbenchController;
use App\Http\Controllers\Panel\Activities\GroupChatPanelController;
use App\Http\Controllers\Panel\Admissions\IntakePanelController;
use App\Http\Controllers\Panel\Ai\AiControlPanelController;
use App\Http\Controllers\Panel\Ai\AiReviewPanelController;
use App\Http\Controllers\Panel\Assistance\AssistancePanelController;
use App\Http\Controllers\Panel\Cards\CardEncodingPanelController;
use App\Http\Controllers\Panel\Cards\CardPanelController;
use App\Http\Controllers\Panel\Classroom\FoundationPanelController;
use App\Http\Controllers\Panel\Communication\FamilyReachController;
use App\Http\Controllers\Panel\Communication\MessageWorkbenchController;
use App\Http\Controllers\Panel\Consultations\ConsultationPanelController;
use App\Http\Controllers\Panel\Discipline\DisciplinePanelController;
use App\Http\Controllers\Panel\FamilyForms\FamilyPanelController;
use App\Http\Controllers\Panel\FamilyVisits\VisitPanelController;
use App\Http\Controllers\Panel\Gallery\GalleryPanelController;
use App\Http\Controllers\Panel\Growth\CaseloadRenewalController;
use App\Http\Controllers\Panel\Growth\MasteryWorkbenchController;
use App\Http\Controllers\Panel\Growth\ProgressPanelController;
use App\Http\Controllers\Panel\Growth\RiskPanelController;
use App\Http\Controllers\Panel\Leadership\DashboardController;
use App\Http\Controllers\Panel\Learning\Digests\DigestPanelController;
use App\Http\Controllers\Panel\Money\BankStatementPanelController;
use App\Http\Controllers\Panel\Money\BillRunPanelController;
use App\Http\Controllers\Panel\Money\CashHandoverPanelController;
use App\Http\Controllers\Panel\Money\CashupPanelController;
use App\Http\Controllers\Panel\Money\FeeAccountPanelController;
use App\Http\Controllers\Panel\Money\FeeCreditPanelController;
use App\Http\Controllers\Panel\Money\FeeDiscountPanelController;
use App\Http\Controllers\Panel\Money\FeeItemBatchPanelController;
use App\Http\Controllers\Panel\Money\FeeItemPanelController;
use App\Http\Controllers\Panel\Money\FeeItemRulePanelController;
use App\Http\Controllers\Panel\Money\FeeLateChargePanelController;
use App\Http\Controllers\Panel\Money\FeeLeaverPanelController;
use App\Http\Controllers\Panel\Money\FeeLifecyclePanelController;
use App\Http\Controllers\Panel\Money\FeePayerCorrectionPanelController;
use App\Http\Controllers\Panel\Money\FeePaymentPanelController;
use App\Http\Controllers\Panel\Money\FeePlanPanelController;
use App\Http\Controllers\Panel\Money\FeeRefundPanelController;
use App\Http\Controllers\Panel\Money\FeeReminderPanelController;
use App\Http\Controllers\Panel\Money\FeeReportPanelController;
use App\Http\Controllers\Panel\Money\FeeTaxPanelController;
use App\Http\Controllers\Panel\Money\TicketCataloguePanelController;
use App\Http\Controllers\Panel\Money\TicketCounterPortalController;
use App\Http\Controllers\Panel\Money\VendorCataloguePanelController;
use App\Http\Controllers\Panel\Money\VendorOperatorPanelController;
use App\Http\Controllers\Panel\Money\VendorOrderPanelController;
use App\Http\Controllers\Panel\Money\VendorPaymentReaderPanelController;
use App\Http\Controllers\Panel\Money\VendorPortalController;
use App\Http\Controllers\Panel\Money\VendorSettlementPanelController;
use App\Http\Controllers\Panel\Money\WalletDepositPanelController;
use App\Http\Controllers\Panel\Money\WalletDeskPanelController;
use App\Http\Controllers\Panel\Money\WalletFinancePanelController;
use App\Http\Controllers\Panel\Money\WalletFundingPanelController;
use App\Http\Controllers\Panel\Money\WalletPaymentPortalController;
use App\Http\Controllers\Panel\Money\WalletWithdrawalPanelController;
use App\Http\Controllers\Panel\Pastoral\CoverPanelController;
use App\Http\Controllers\Panel\Pastoral\SupportPanelController;
use App\Http\Controllers\Panel\Pathways\PathwayPanelController;
use App\Http\Controllers\Panel\PeerTutoring\PeerPanelController;
use App\Http\Controllers\Panel\People\GradePromotionController;
use App\Http\Controllers\Panel\People\LearnerInvitationController;
use App\Http\Controllers\Panel\People\LuritsOfficeController;
use App\Http\Controllers\Panel\People\SchoolImportController;
use App\Http\Controllers\Panel\Platform\SchoolSwitchController;
use App\Http\Controllers\Panel\Recognition\RecognitionPanelController;
use App\Http\Controllers\Panel\SearchController;
use App\Http\Controllers\Panel\Transfers\TransferPanelController;
use App\Http\Controllers\Panel\Wellbeing\WellbeingPanelController;
use App\Http\Controllers\ParentsController;
use App\Http\Controllers\People\AccessReviewController;
use App\Http\Controllers\People\LearnerGuardiansController;
use App\Http\Controllers\People\ProfileFieldController;
use App\Http\Controllers\People\ProfileFormController;
use App\Http\Controllers\Platform\CspReportController;
use App\Http\Controllers\Platform\LearnerSetupController;
use App\Http\Controllers\Platform\ModuleController;
use App\Http\Controllers\Platform\NotificationRuleController;
use App\Http\Controllers\Platform\PlatformAuditController;
use App\Http\Controllers\Platform\PlatformFeatureFlagController;
use App\Http\Controllers\Platform\PlatformIncidentController;
use App\Http\Controllers\Platform\PlatformInvitationController;
use App\Http\Controllers\Platform\PlatformLuritsController;
use App\Http\Controllers\Platform\PlatformOnboardingController;
use App\Http\Controllers\Platform\PlatformPackageController;
use App\Http\Controllers\Platform\PlatformSchoolHealthController;
use App\Http\Controllers\Platform\PlatformStaffController;
use App\Http\Controllers\Platform\ProductCatalogueController;
use App\Http\Controllers\Platform\SaasController;
use App\Http\Controllers\Platform\SchoolAccountController;
use App\Http\Controllers\Platform\StaffInvitationController;
use App\Http\Controllers\RoleAccessController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\School\SchoolInformationController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\SessionYearController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\SliderController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StreamController;
use App\Http\Controllers\Student;
use App\Http\Controllers\StudentSessionController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\SubjectTeacherController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TermController;
use App\Http\Controllers\TimetableController;
use App\Http\Controllers\WebController;
use App\Http\Controllers\Webhooks\MailWebhookController;
use App\Http\Controllers\WebSettingController;
use App\Http\Middleware\AuthorizeCardEncodingPanel;
use App\Http\Middleware\AuthorizeCardsPanel;
use App\Http\Middleware\AuthorizeConsultationPanel;
use App\Http\Middleware\AuthorizeFamilyFormsPanel;
use App\Http\Middleware\AuthorizeGalleryPanel;
use App\Http\Middleware\AuthorizeGroupChatPanel;
use App\Http\Middleware\AuthorizeIntake;
use App\Http\Middleware\AuthorizeRecognition;
use App\Http\Middleware\AuthorizeTransfer;
use App\Http\Middleware\EnforceReadOnlyYears;
use App\Http\Middleware\FoundationStaffContext;
use App\Http\Middleware\PrivateAcademicContent;
use App\Http\Middleware\PrivateDomainResponse;
use App\Http\Middleware\PrivateMastery;
use App\Http\Middleware\PrivateWellbeing;
use App\Services\Recognition\RecognitionQueries;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
// Manual Authentication Routes

// Pages anyone may open work in the default school (or the signed-in
// user's): school.context:public.

Route::middleware('school.context:public')->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->middleware('guest')->name('login');
    Route::post('login', [LoginController::class, 'login'])->middleware(['guest', 'throttle:login', 'school.context:sign-in']);
    Route::post('logout', [LoginController::class, 'logout'])->name('logout');

    // There is no public sign-up. Staff create accounts, and those accounts always
    // have a role. Students apply through the public online registration form.

    // Password Reset Routes (if needed)
    Route::get('password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->middleware(['throttle:login', 'school.context:sign-in'])->name('password.email');
    Route::get('password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('password/reset', [ResetPasswordController::class, 'reset'])->middleware(['throttle:login', 'school.context:sign-in'])->name('password.update');

    // What the report-only content security policy would have blocked (logged, ids only).
    Route::post('csp-report', CspReportController::class)->middleware('throttle:csp')->name('csp.report');

    // A learner's password is chosen here: from a guardian's emailed link (signed,
    // expiring, single use) or with a set-up code the school printed.
    Route::get('learner-setup', [LearnerSetupController::class, 'showCode'])->name('learner-setup.code');
    Route::post('learner-setup', [LearnerSetupController::class, 'storeCode'])->middleware('throttle:login')->name('learner-setup.code.store');
    Route::get('learner-setup/{invitation}/{token}', [LearnerSetupController::class, 'showLink'])
        ->whereNumber('invitation')->name('learner-setup.link');
    Route::post('learner-setup/{invitation}/{token}', [LearnerSetupController::class, 'storeLink'])
        ->whereNumber('invitation')->middleware('throttle:password')->name('learner-setup.link.store');
    Route::get('web-set-language/{lang}', [LanguageController::class, 'set_language'])->name('web.set.language');

    Route::group(['middleware' => 'language'], function () {
        Route::get('/', [WebController::class, 'index']);
        Route::get('about', [WebController::class, 'about'])->name('about.us');
        Route::get('contact', [WebController::class, 'contact_us'])->name('contact.us');
        Route::get('photo', [WebController::class, 'photo'])->name('photo');
        Route::get('photo-gallery/{id}', [WebController::class, 'photo_details'])->name('photo.gallery');
        Route::get('video', [WebController::class, 'video'])->name('video');
        // Public forms: limited per address so they can't be used to flood the school with messages or accounts.
        Route::post('contact-us/store', [WebController::class, 'contact_us_store'])->middleware('throttle:5,1')->name('contact_us.store');
        Route::get('registration', [WebController::class, 'registrationIndex'])->name('student-registration.index');
        Route::post('student-register', [WebController::class, 'studentRegistration'])->middleware('throttle:10,1')->name('student-registration-store');
        Route::get('error-page', [WebController::class, 'errorPage'])->name('error-page');
    });
});

Route::prefix('vendor-counter')->name('vendor.')->middleware([PrivateDomainResponse::class])->group(function (): void {
    $c = VendorPortalController::class;
    Route::get('sign-in', [$c, 'signInForm'])->name('sign-in-form');
    Route::post('sign-in', [$c, 'signIn'])->middleware('throttle:login')->name('sign-in');
    Route::post('sign-out', [$c, 'signOut'])->middleware('auth')->name('sign-out');
    Route::middleware(['auth', 'school.context', 'role:Vendor', 'module:vendors,read'])->group(function () use ($c): void {
        Route::get('/', [$c, 'index'])->name('index');
        $payments = WalletPaymentPortalController::class;
        Route::get('counters/{vendor}/payments', [$payments, 'handle'])->whereNumber('vendor')->name('payments.index');
        Route::post('counters/{vendor}/payments/lookup', [$payments, 'handle'])->whereNumber('vendor')->name('payments.lookup');
        Route::post('counters/{vendor}/payments/card', [$payments, 'handle'])->whereNumber('vendor')->name('payments.card');
        Route::post('counters/{vendor}/payments/pay', [$payments, 'handle'])->whereNumber('vendor')->name('payments.pay');
        Route::post('counters/{vendor}/payments/resolve', [$payments, 'handle'])->whereNumber('vendor')->name('payments.resolve');
        $t = TicketCounterPortalController::class;
        Route::get('counters/{vendor}/tickets', [$t, 'handle'])->whereNumber('vendor')->name('tickets.index');
        Route::post('counters/{vendor}/tickets/lookup', [$t, 'handle'])->whereNumber('vendor')->name('tickets.lookup');
        Route::post('counters/{vendor}/tickets/redeem', [$t, 'handle'])->whereNumber('vendor')->name('tickets.redeem');
        Route::get('counters/{vendor}/settlements', [$c, 'settlements'])->whereNumber('vendor')->name('settlements');
        Route::get('counters/{vendor}/settlements/{settlement}', [$c, 'settlements'])->whereNumber(['vendor', 'settlement'])->name('settlement');
        Route::post('counters/{vendor}/lookup', [$c, 'lookup'])->whereNumber('vendor')->name('lookup');
        Route::get('counters/{vendor}/orders/{order}', [$c, 'show'])->whereNumber(['vendor', 'order'])->name('order');
        Route::post('counters/{vendor}/collect', [$c, 'collect'])->whereNumber('vendor')->name('collect');
    });
});

// Staff invitations: the emailed one-use link chooses a password before any
// sign-in. The token is the only key, so both routes are rate limited.
Route::middleware([PrivateDomainResponse::class])->group(function () {
    Route::get('invite/staff/{token}', [StaffInvitationController::class, 'show'])->where('token', '[A-Za-z0-9]{64}')->middleware('throttle:30,1')->name('staff-invitation.show');
    Route::post('invite/staff/{token}', [StaffInvitationController::class, 'accept'])->where('token', '[A-Za-z0-9]{64}')->middleware('throttle:password')->name('staff-invitation.accept');
});

// An operator's own password has a separate purpose: pending accounts may
// settle it, but cannot enter /saas or any school through this route.
Route::middleware([PrivateDomainResponse::class, 'auth', 'platform.staff:password'])->group(function () {
    Route::view('saas/password', 'saas.password')->name('saas.password');
    Route::post('saas/password', [HomeController::class, 'changePassword'])->middleware('throttle:password')->name('saas.password.store');
});

// An invited platform staff member chooses their password (SA.1). No school, no sign-in.
Route::middleware([PrivateDomainResponse::class])->group(function () {
    Route::get('platform/invite/{token}', [PlatformInvitationController::class, 'show'])->middleware('throttle:30,1')->where('token', '[A-Za-z0-9]{64}')->name('platform-invitation.show');
    Route::post('platform/invite/{token}', [PlatformInvitationController::class, 'accept'])->middleware('throttle:password')->where('token', '[A-Za-z0-9]{64}')->name('platform-invitation.accept');
});

// The platform admin area: active platform staff identities only, in no school
// (no school.context). It lists schools and their counts, never their people.
Route::middleware([PrivateDomainResponse::class, 'auth', 'platform.staff'])->prefix('saas')->name('saas.')->group(function () {
    // Every route names the ability its role must hold (platform.can, SA.1).
    Route::get('/', [SaasController::class, 'index'])->middleware('platform.can:schools.view')->name('index');
    Route::get('/packages', ProductCatalogueController::class)->middleware('platform.can:packages.view')->name('packages');
    // The package and price editor (SA.7): packages.view reads, packages.manage changes.
    Route::get('/packages/{package}', [PlatformPackageController::class, 'show'])->middleware('platform.can:packages.view')->whereNumber('package')->name('packages.show');
    Route::prefix('/packages')->middleware(['platform.can:packages.manage', 'throttle:30,1'])->name('packages.')->group(function () {
        Route::post('/', [PlatformPackageController::class, 'store'])->name('store');
        Route::put('/{package}', [PlatformPackageController::class, 'update'])->whereNumber('package')->name('update');
        Route::put('/{package}/modules', [PlatformPackageController::class, 'modules'])->whereNumber('package')->name('modules');
        Route::put('/{package}/status', [PlatformPackageController::class, 'status'])->whereNumber('package')->name('status');
        Route::post('/{package}/prices', [PlatformPackageController::class, 'price'])->whereNumber('package')->name('prices.store');
        Route::post('/{package}/prices/{price}/withdraw', [PlatformPackageController::class, 'withdraw'])->whereNumber(['package', 'price'])->name('prices.withdraw');
        Route::post('/{package}/reapply', [PlatformPackageController::class, 'reapply'])->whereNumber('package')->name('reapply');
    });
    Route::get('/schools/create', [SaasController::class, 'create'])->middleware('platform.can:schools.create')->name('schools.create');
    Route::post('/schools', [SaasController::class, 'store'])->middleware(['platform.can:schools.create', 'throttle:20,1'])->name('schools.store');
    Route::get('/schools/{school}', [SaasController::class, 'show'])->middleware('platform.can:schools.view')->whereNumber('school')->name('schools.show');
    // The onboarding checklist (SA.6): counts from the school's live data, never a person.
    Route::get('/schools/{school}/onboarding', [PlatformOnboardingController::class, 'show'])->middleware('platform.can:schools.view')->whereNumber('school')->name('schools.onboarding');
    Route::post('/schools/{school}/training', [PlatformOnboardingController::class, 'training'])->middleware(['platform.can:accounts.manage', 'throttle:20,1'])->whereNumber('school')->name('schools.training');
    // School health and usage (SA.10): counts and shares only, never a person.
    Route::get('/schools/{school}/health', [PlatformSchoolHealthController::class, 'show'])->middleware('platform.can:health.view')->whereNumber('school')->name('schools.health');
    Route::get('/health', [PlatformSchoolHealthController::class, 'index'])->middleware('platform.can:health.view')->name('health');
    // HQ's own record of the school (contacts, stage, contracts, timeline): never the school's people.
    Route::prefix('/schools/{school}')->whereNumber('school')->middleware('platform.can:accounts.manage')->name('schools.')->group(function () {
        Route::get('account', [SchoolAccountController::class, 'show'])->name('account');
        Route::put('account', [SchoolAccountController::class, 'save'])->name('account.save');
        Route::post('contacts', [SchoolAccountController::class, 'contact'])->name('contacts.store');
        Route::put('contacts/{contact}', [SchoolAccountController::class, 'contact'])->whereNumber('contact')->name('contacts.update');
        Route::delete('contacts/{contact}', [SchoolAccountController::class, 'removeContact'])->whereNumber('contact')->name('contacts.destroy');
        Route::post('contracts', [SchoolAccountController::class, 'contract'])->name('contracts.store');
        Route::post('notes', [SchoolAccountController::class, 'note'])->name('notes.store');
    });
    Route::put('/schools/{school}/package', [SaasController::class, 'updatePackage'])->middleware('platform.can:schools.package')->whereNumber('school')->name('schools.package');
    Route::put('/schools/{school}/status', [SaasController::class, 'updateStatus'])->middleware('platform.can:schools.status')->whereNumber('school')->name('schools.status');
    Route::put('/schools/{school}/modules/{module}', [SaasController::class, 'updateModule'])->middleware('platform.can:schools.modules')->whereNumber('school')->name('schools.modules');
    Route::post('/schools/{school}/administrator-invitation', [SaasController::class, 'resendInvitation'])->middleware(['platform.can:schools.create', 'throttle:20,1'])->whereNumber('school')->name('schools.invitation');
    // LURITS claims for support (1.0f): schools and dates, never a learner.
    Route::get('/lurits', [PlatformLuritsController::class, 'index'])->middleware('platform.can:identity.review')->name('lurits');
    Route::post('/lurits/{conflict}/close', [PlatformLuritsController::class, 'resolve'])->middleware(['platform.can:identity.review', 'throttle:30,1'])->whereNumber('conflict')->name('lurits.resolve');
    // The breach and incident register (SA.12): incidents, the schools they affect and when each was told.
    Route::prefix('/incidents')->middleware('platform.can:incidents.manage')->name('incidents.')->group(function () {
        Route::get('/', [PlatformIncidentController::class, 'index'])->name('index');
        Route::post('/', [PlatformIncidentController::class, 'store'])->middleware('throttle:20,1')->name('store');
        Route::get('/{incident}', [PlatformIncidentController::class, 'show'])->whereNumber('incident')->name('show');
        Route::put('/{incident}', [PlatformIncidentController::class, 'update'])->whereNumber('incident')->name('update');
        Route::post('/{incident}/schools', [PlatformIncidentController::class, 'school'])->whereNumber('incident')->name('schools.store');
        Route::put('/{incident}/schools/{school}', [PlatformIncidentController::class, 'notified'])->whereNumber(['incident', 'school'])->name('schools.notified');
        Route::post('/{incident}/entries', [PlatformIncidentController::class, 'entry'])->middleware('throttle:60,1')->whereNumber('incident')->name('entries.store');
    });
    // Feature flags and staged rollout (SA.14): beta schools first, then everyone; a kill switch.
    Route::get('/flags', [PlatformFeatureFlagController::class, 'index'])->middleware('platform.can:flags.manage')->name('flags.index');
    Route::post('/flags', [PlatformFeatureFlagController::class, 'store'])->middleware(['platform.can:flags.manage', 'throttle:20,1'])->name('flags.store');
    Route::put('/flags/{flag}', [PlatformFeatureFlagController::class, 'update'])->middleware('platform.can:flags.manage')->whereNumber('flag')->name('flags.update');
    Route::post('/flags/{flag}/schools', [PlatformFeatureFlagController::class, 'school'])->middleware('platform.can:flags.manage')->whereNumber('flag')->name('flags.schools');
    Route::get('/audit', [PlatformAuditController::class, 'index'])->middleware('platform.can:audit.view')->name('audit');
    Route::get('/audit/export', [PlatformAuditController::class, 'export'])->middleware(['platform.can:audit.export', 'throttle:10,1'])->name('audit.export');
    Route::get('/staff', [PlatformStaffController::class, 'index'])->middleware('platform.can:staff.manage')->name('staff.index');
    Route::post('/staff', [PlatformStaffController::class, 'store'])->middleware(['platform.can:staff.manage', 'throttle:20,1'])->name('staff.store');
    Route::put('/staff/{user}', [PlatformStaffController::class, 'update'])->middleware('platform.can:staff.manage')->whereNumber('user')->name('staff.update');
    Route::post('/staff/{user}/revoke', [PlatformStaffController::class, 'revoke'])->middleware('platform.can:staff.manage')->whereNumber('user')->name('staff.revoke');
    Route::post('/staff/{user}/invitation', [PlatformStaffController::class, 'resend'])->middleware(['platform.can:staff.manage', 'throttle:20,1'])->whereNumber('user')->name('staff.invitation');
});

Route::get('platform/ai-costs', [AiControlPanelController::class, 'costs'])->middleware(['auth', PrivateDomainResponse::class])->name('ai-platform.costs');

// EnforceReadOnlyYears: a year other than the current one is read-only in the panel.
Route::group(['middleware' => ['Role', 'auth', 'school.context', EnforceReadOnlyYears::class]], function () {
    Route::post('/set-session-year', [SessionYearController::class, 'setSessionYearToSessionStorage'])->name('set.session.year');
    // The school switcher, for people at several schools.
    Route::post('/school/switch', SchoolSwitchController::class)->name('school.switch');
    Route::group(['middleware' => 'language'], function () {
        // Route::get('/home', [HomeController::class, 'index']);
        Route::get('home', [HomeController::class, 'index'])->name('home');
        // Route::get('home/data', [HomeController::class, 'dashboardData'])->name('home.data');

        // Super Admin dashboard widgets (AJAX, per-widget hydration)
        Route::get('home/widget/fees-collection', [HomeController::class, 'feesCollectionWidget'])->name('home.widget.fees');
        Route::get('home/widget/attendance', [HomeController::class, 'attendanceWidget'])->name('home.widget.attendance');
        Route::get('home/widget/assessment', [HomeController::class, 'assessmentWidget'])->name('home.widget.assessment');
        Route::get('home/widget/leaves', [HomeController::class, 'leavesWidget'])->name('home.widget.leaves');
        Route::get('home/widget/events', [HomeController::class, 'eventsWidget'])->name('home.widget.events');

        // Teacher dashboard widgets
        Route::get('home/widget/teacher-leaves', [HomeController::class, 'teacherLeavesWidget'])->name('home.widget.teacher-leaves');
        Route::get('home/widget/teacher-exams', [HomeController::class, 'teacherExamsWidget'])->name('home.widget.teacher-exams');
        Route::get('home/widget/teacher-assignments', [HomeController::class, 'teacherAssignmentsWidget'])->name('home.widget.teacher-assignments');

        Route::get('subject-by-class-section', [HomeController::class, 'getSubjectByClassSection'])->name('class-section.by.subject');
        Route::get('teacher-by-class-subject', [HomeController::class, 'getTeacherByClassSubject'])->name('teacher.by.class.subject');
        // new reset password controller
        Route::get('home/reset_password', [HomeController::class, 'resetPasswordView']);

        // Global search: JSON for the header box, a results page otherwise.
        Route::get('search', SearchController::class)->name('search');

        // New-year wizard: create next year, carry forward, year-end checks, preview, make it current.
        Route::get('new-year', [NewYearController::class, 'index'])->name('new-year.index');
        Route::post('new-year/year', [NewYearController::class, 'store'])->name('new-year.store');
        Route::get('new-year/preview', [NewYearController::class, 'preview'])->name('new-year.preview');
        Route::post('new-year/make-current', [NewYearController::class, 'makeCurrent'])->name('new-year.make-current');

        Route::get('roles-list/{id}', [RoleController::class, 'showList'])->name('roles-list');
        Route::get('roles-data', [RoleController::class, 'show'])->name('roles.data');
        // The role editor by module and level; the checkbox editor above stays for now.
        Route::get('roles-by-module/new', [RoleAccessController::class, 'create'])->name('role-access.create');
        Route::post('roles-by-module', [RoleAccessController::class, 'store'])->name('role-access.store');
        Route::get('roles-by-module/{role}', [RoleAccessController::class, 'edit'])->whereNumber('role')->name('role-access.edit');
        Route::put('roles-by-module/{role}', [RoleAccessController::class, 'update'])->whereNumber('role')->name('role-access.update');
        Route::resource('roles', RoleController::class);

        Route::get('settings', [SettingController::class, 'index']);
        Route::post('settings', [SettingController::class, 'update']);

        Route::get('fcm-settings', [SettingController::class, 'fcm_index'])->middleware('school.default');

        Route::resource('medium', MediumController::class)->except(['create']);
        Route::get('medium_list', [MediumController::class, 'show']);

        Route::resource('teachers', TeacherController::class)->except(['create']);
        Route::get('teacher_details', [TeacherController::class, 'teacherListIndex'])->name('teacher.details');
        Route::get('teacher_list', [TeacherController::class, 'show']);
        Route::post('teachers/{id}/status', [TeacherController::class, 'changeStatus'])->name('teachers.status');

        Route::resource('section', SectionController::class)->except(['create', 'edit']);
        Route::get('section_list', [SectionController::class, 'show']);

        Route::get('class/select-elective-subjects', [Classes\ElectiveController::class, 'selectElectiveSubjects'])->name('class.select-elective-subjects');
        Route::get('get-elective-subjects-by-class', [Classes\ElectiveController::class, 'getElectiveSubjects'])
            ->name('get.elective.subjects');
        Route::get('get-elective-groups-by-class', [Classes\ElectiveController::class, 'getElectiveGroups'])
            ->name('get.elective.groups');
        Route::get('students-subject-data', [Classes\ElectiveController::class, 'fetchStudentSubjects'])
            ->name('students-subject-data');
        Route::get('/get-student-assigned-subjects', [Classes\ElectiveController::class, 'getStudentAssignedSubjects'])->name('student.assigned.subjects');
        Route::post('assign-elective-subjects', [Classes\ElectiveController::class, 'assignElectiveSubject'])
            ->name('assign-elective-subjects');

        Route::get('get-class-sections/{class_id}', [Classes\ClassController::class, 'getClassSections']);

        Route::get('class/subject', [Classes\SubjectController::class, 'subject'])->name('class.subject');
        Route::post('class/subject', [Classes\SubjectController::class, 'update_subjects'])->name('class.subject.update');
        Route::delete('class/subject/{class_subject_id}', [Classes\SubjectController::class, 'subject_destroy'])->name('class.subject.delete');
        Route::delete('class/subject-group/{group_id}', [Classes\SubjectController::class, 'subject_group_destroy'])->name('class.subject-group.delete');
        Route::get('class/subject/list', [Classes\SubjectController::class, 'subject_list'])->name('class.subject.list');
        Route::get('class/subject/term-details/{class_id}', [Classes\SubjectController::class, 'subjectTermDetails'])->name('class.subject.term-details');
        Route::get('class-list', [Classes\ClassController::class, 'show']);
        Route::resource('class', Classes\ClassController::class)->except(['create', 'edit']);

        Route::get('class-subject-list/{medium_id}', [Classes\SubjectController::class, 'getSubjectsByMediumId']);

        Route::get('assign/class/teacher', [ClassTeacherController::class, 'teacher'])->name('class.teacher');
        Route::post('class/teacher/store', [ClassTeacherController::class, 'assign_teacher'])->name('class.teacher.store');
        Route::get('class-teacher-list', [ClassTeacherController::class, 'show']);
        Route::post('remove-class-teacher/{id}/{class_teacher_id}', [ClassTeacherController::class, 'removeClassTeacher']);

        Route::prefix('academic-sasams')->name('academic-sasams.')->middleware(['module:academics', 'cache.headers:private;no_store'])->group(function () {
            $c = SasamsExportController::class;
            Route::get('/', [$c, 'index'])->name('index');
            Route::post('download', [$c, 'download'])->name('download');
        });

        Route::prefix('growth-mastery')->name('mastery.')->middleware(['module:mastery', PrivateMastery::class])->group(function () {
            $controller = MasteryWorkbenchController::class;
            Route::get('/', [$controller, 'index'])->name('index');
            Route::post('subjects/{subject}', [$controller, 'save'])->whereNumber('subject')->name('save');
            Route::post('subjects/{subject}/refresh', [$controller, 'refresh'])->whereNumber('subject')->name('refresh');
            Route::post('groups/{group}/rebuild', [$controller, 'rebuild'])->whereNumber('group')->name('rebuild');
        });
        // This purpose resolves the material's actual year in ActivityFiles:
        // uploads require its current writable year; removal stays available.
        Route::prefix('activity-material-files/groups/{group}/materials/{material}')->name('activity-files.')
            ->whereNumber(['group', 'material'])
            ->withoutMiddleware([EnforceReadOnlyYears::class])
            ->middleware([PrivateDomainResponse::class, 'module:groups,read'])->group(function () {
                $c = ActivityFilePanelController::class;
                Route::get('/', [$c, 'index'])->name('listing');
                Route::post('/', [$c, 'write'])->name('upload');
                Route::get('{file}/content', [$c, 'read'])->whereUuid('file')->name('read');
                Route::post('{file}/remove', [$c, 'write'])->whereUuid('file')->name('remove');
            });
        // Season rollover (Action Plan 1.22): next season's groups, empty.
        Route::middleware(['module:groups', 'can:activity-groups-manage'])->group(function () {
            Route::get('activity-seasons', [ActivitySeasonController::class, 'index'])->name('activity-seasons.index');
            Route::post('activity-seasons', [ActivitySeasonController::class, 'store'])->middleware('throttle:20,1')->name('activity-seasons.store');
        });
        Route::prefix('activity-groups')->name('activity-groups.')->middleware(['module:groups', 'cache.headers:private;no_store'])->group(function () {
            $c = ActivityWorkbenchController::class;
            Route::get('/', [$c, 'index'])->name('index');
            Route::post('groups', [$c, 'write'])->name('create');
            Route::post('groups/{group}', [$c, 'write'])->whereNumber('group')->name('save');
            foreach (['membership', 'staff', 'session', 'material'] as $kind) {
                Route::post('groups/{group}/'.$kind.'/{record?}', [$c, 'write'])->whereNumber(['group', 'record'])->name($kind.'-save');
            }
            Route::post('groups/{group}/attendance/{session}', [$c, 'write'])->whereNumber(['group', 'session'])->name('attendance-save');
        });
        Route::prefix('communication-centre')->name('communication-centre.')->middleware(['module:communication', 'cache.headers:private;no_store'])->group(function () {
            $c = MessageWorkbenchController::class;
            Route::get('/', [$c, 'index'])->name('index');
            Route::post('drafts', [$c, 'create'])->name('create');
            foreach (['save', 'submit', 'decision', 'withdraw'] as $action) {
                Route::post('drafts/{message}/'.$action, [$c, $action])->whereNumber('message')->name($action);
            }
            Route::get('messages/{message}/report', [$c, 'report'])->whereNumber('message')->name('report');
            Route::post('audiences', [$c, 'audienceCreate'])->name('audience-create');
            Route::post('audiences/{audience}', [$c, 'audienceSave'])->whereNumber('audience')->name('audience-save');
            Route::post('rules/{role}', [$c, 'ruleSave'])->whereNumber('role')->name('rule-save');
        });
        Route::prefix('academic-content')->name('academic-content.')->middleware(['module:academics', PrivateAcademicContent::class])->group(function () {
            $controller = ContentWorkbenchController::class;
            Route::get('/', [$controller, 'index'])->name('index');
            Route::post('items', [$controller, 'create'])->name('create');
            Route::post('items/{item}/source', [$controller, 'upload'])->whereNumber('item')->name('upload');
            Route::post('items/{item}/archive', [$controller, 'archive'])->whereNumber('item')->name('archive');
            foreach (['save', 'retry', 'publish'] as $action) {
                Route::post('versions/{version}/'.$action, [$controller, $action])->whereNumber('version')->name($action);
            }
            Route::post('settings', [$controller, 'settings'])->name('settings');
        });
        Route::prefix('fee-items')->name('fee-items.')->middleware([PrivateDomainResponse::class, 'module:fees,read'])->group(function () {
            Route::get('{item}/rules', [FeeItemRulePanelController::class, 'form'])->whereNumber('item')->name('rules');
            Route::post('{item}/rules', [FeeItemRulePanelController::class, 'save'])->whereNumber('item')->name('rules-save');
            Route::get('tax-settings', [FeeTaxPanelController::class, 'index'])->name('tax');
            Route::post('tax-settings', [FeeTaxPanelController::class, 'save'])->name('tax-save');
            $c = FeeItemPanelController::class;
            Route::get('/', [$c, 'index'])->name('index');
            Route::get('new', [$c, 'form'])->name('new');
            Route::get('{item}/edit', [$c, 'form'])->whereNumber('item')->name('edit');
            Route::post('new', [$c, 'save'])->name('create');
            Route::post('{item}/save', [$c, 'save'])->whereNumber('item')->name('save');
        });
        Route::prefix('fee-item-batches')->name('fee-item-batches.')->middleware([PrivateDomainResponse::class, 'module:fees,read'])->group(function () {
            $c = FeeItemBatchPanelController::class;
            Route::get('/', [$c, 'index'])->name('index');
            Route::get('new', [$c, 'form'])->name('new');
            Route::post('review', [$c, 'review'])->name('review');
            Route::post('new', [$c, 'post'])->name('post');
            Route::get('{batch}', [$c, 'show'])->whereNumber('batch')->name('show');
        });
        Route::prefix('fee-item-charges')->name('fee-item-charges.')->middleware([PrivateDomainResponse::class, 'module:fees,read'])->group(function () {
            $c = FeeItemPanelController::class;
            Route::get('/', [$c, 'charges'])->name('index');
            Route::get('new', [$c, 'chargeForm'])->name('new');
            Route::post('review', [$c, 'review'])->name('review');
            Route::post('new', [$c, 'post'])->name('post');
            Route::get('{charge}', [$c, 'show'])->whereNumber('charge')->name('show');
            Route::get('{charge}/invoice', [$c, 'invoice'])->whereNumber('charge')->name('invoice');
        });
        Route::prefix('ticket-events')->name('ticket-events.')->middleware([PrivateDomainResponse::class, 'module:ticketing,read'])->withoutMiddleware([EnforceReadOnlyYears::class])->group(function (): void {
            $c = TicketCataloguePanelController::class;
            Route::get('/', [$c, 'index'])->name('index');
            Route::post('events', [$c, 'event'])->name('event');
            Route::post('types', [$c, 'type'])->name('type');
        });
        Route::prefix('cashups')->name('cashups.')->middleware([PrivateDomainResponse::class])->withoutMiddleware([EnforceReadOnlyYears::class])->group(function (): void {
            $c = CashupPanelController::class;
            Route::get('/', [$c, 'index'])->name('index');
            Route::get('export', [$c, 'export'])->name('export');
            Route::post('/', [$c, 'record'])->name('record');
            $h = CashHandoverPanelController::class;
            Route::get('handovers', [$h, 'index'])->name('handovers.index');
            Route::post('handovers', [$h, 'propose'])->name('handovers.propose');
            Route::post('handovers/{handover}/{status}', [$h, 'finish'])->whereNumber('handover')->whereIn('status', ['accepted', 'cancelled', 'rejected'])->name('handovers.finish');
        });
        Route::prefix('vendor-settlements')->name('vendor-settlements.')->middleware([PrivateDomainResponse::class, 'module:vendors,read'])->withoutMiddleware([EnforceReadOnlyYears::class])->group(function (): void {
            $c = VendorSettlementPanelController::class;
            Route::get('/', [$c, 'index'])->name('index');
            Route::get('export', [$c, 'export'])->name('export');
            Route::post('/', [$c, 'prepare'])->name('prepare');
            Route::get('{settlement}', [$c, 'show'])->whereNumber('settlement')->name('show');
            Route::post('{settlement}/approve', [$c, 'approve'])->whereNumber('settlement')->name('approve');
            Route::post('{settlement}/finish', [$c, 'finish'])->whereNumber('settlement')->name('finish');
        });
        Route::prefix('vendor-payment-readers')->name('vendor-payment-readers.')->middleware([PrivateDomainResponse::class, 'module:wallet_payments,read'])->withoutMiddleware([EnforceReadOnlyYears::class])->group(function (): void {
            $c = VendorPaymentReaderPanelController::class;
            Route::get('/', [$c, 'handle'])->name('index');
            Route::post('/', [$c, 'handle'])->name('register');
            Route::post('{reader}/revoke', [$c, 'handle'])->whereUuid('reader')->name('revoke');
        });
        Route::prefix('vendor-operators')->name('vendor-operators.')->middleware([PrivateDomainResponse::class, 'module:vendors,read'])->withoutMiddleware([EnforceReadOnlyYears::class])->group(function (): void {
            $c = VendorOperatorPanelController::class;
            Route::get('/', [$c, 'index'])->name('index');
            Route::post('provision', [$c, 'provision'])->name('provision');
            Route::post('bind', [$c, 'bind'])->name('bind');
        });
        Route::prefix('vendor-orders')->name('vendor-orders.')->middleware([PrivateDomainResponse::class, 'module:vendors,read'])->withoutMiddleware([EnforceReadOnlyYears::class])->group(function () {
            $c = VendorOrderPanelController::class;
            Route::get('/', [$c, 'index'])->name('index');
            Route::get('{order}', [$c, 'show'])->whereNumber('order')->name('show');
            Route::post('vendors/{vendor}/collect', [$c, 'collect'])->whereNumber('vendor')->name('collect');
            Route::post('{order}/cancel', [$c, 'cancel'])->whereNumber('order')->name('cancel');
        });
        Route::prefix('vendor-catalogues')->name('vendor-catalogues.')->middleware([PrivateDomainResponse::class, 'module:vendors,read'])->withoutMiddleware([EnforceReadOnlyYears::class])->group(function () {
            $c = VendorCataloguePanelController::class;
            Route::get('/', [$c, 'index'])->name('index');
            Route::post('{kind}', [$c, 'save'])->whereIn('kind', ['category', 'vendor', 'product', 'stock'])->name('save');
        });
        Route::prefix('wallet-topups')->name('wallet-desk.')->middleware([PrivateDomainResponse::class, 'module:wallet,read'])->withoutMiddleware([EnforceReadOnlyYears::class])->group(function () {
            Route::get('/', [WalletDeskPanelController::class, 'index'])->name('index');
            Route::post('policy', [WalletDeskPanelController::class, 'policy'])->name('policy');
            Route::post('grants', [WalletDeskPanelController::class, 'grant'])->name('grant');
            Route::get('{topup}/receipt', [WalletDeskPanelController::class, 'receipt'])->whereNumber('topup')->name('receipt');
            Route::post('{topup}/release', [WalletDeskPanelController::class, 'release'])->whereNumber('topup')->name('release');
            Route::post('{topup}/cancel', [WalletDeskPanelController::class, 'cancel'])->whereNumber('topup')->name('cancel');
        });
        Route::prefix('wallet-withdrawals')->name('wallet-withdrawals.')->middleware([PrivateDomainResponse::class, 'module:wallet,read'])->withoutMiddleware([EnforceReadOnlyYears::class])->group(function () {
            $c = WalletWithdrawalPanelController::class;
            Route::get('/', [$c, 'index'])->name('index');
            Route::post('policy', [$c, 'policy'])->name('policy');
            Route::post('slots', [$c, 'publishSlot'])->name('slots.publish');
            Route::post('slots/{slot}/close', [$c, 'closeSlot'])->whereNumber('slot')->name('slots.close');
            Route::post('offboarding', [$c, 'offboarding'])->name('offboarding');
            Route::get('{withdrawal}', [$c, 'show'])->whereNumber('withdrawal')->name('show');
            Route::post('{withdrawal}/approve', [$c, 'approve'])->whereNumber('withdrawal')->name('approve');
            Route::post('{withdrawal}/paid', [$c, 'finish'])->whereNumber('withdrawal')->name('paid');
            Route::post('{withdrawal}/reject', [$c, 'finish'])->whereNumber('withdrawal')->name('reject');
            Route::post('{withdrawal}/cancel', [$c, 'finish'])->whereNumber('withdrawal')->name('cancel');
            Route::get('{withdrawal}/receipt', [$c, 'receipt'])->whereNumber('withdrawal')->name('receipt');
        });
        Route::prefix('wallet-deposits')->name('wallet-deposits.')->middleware([PrivateDomainResponse::class, 'module:wallet,read'])->withoutMiddleware([EnforceReadOnlyYears::class])->group(function () {
            $c = WalletDepositPanelController::class;
            Route::get('/', [$c, 'index'])->name('index');
            Route::post('policy', [$c, 'policy'])->name('policy');
            Route::get('{claim}', [$c, 'show'])->whereNumber('claim')->name('show');
            Route::post('{claim}/verify', [$c, 'verify'])->whereNumber('claim')->name('verify');
            Route::post('{claim}/reject', [$c, 'reject'])->whereNumber('claim')->name('reject');
            Route::get('{claim}/receipt', [$c, 'receipt'])->whereNumber('claim')->name('receipt');
        });
        Route::prefix('wallet-funding')->name('wallet-funding.')->middleware([PrivateDomainResponse::class, 'module:wallet,read'])->withoutMiddleware([EnforceReadOnlyYears::class])->group(function () {
            Route::get('/', [WalletFundingPanelController::class, 'index'])->name('index');
            Route::post('policy', [WalletFundingPanelController::class, 'policy'])->name('policy');
            Route::post('receipts', [WalletFundingPanelController::class, 'record'])->name('record');
            Route::get('{funding}/receipt', [WalletFundingPanelController::class, 'receipt'])->whereNumber('funding')->name('receipt');
        });
        Route::prefix('wallet-settings')->name('wallet-finance.')->middleware([PrivateDomainResponse::class, 'module:wallet,read'])->withoutMiddleware([EnforceReadOnlyYears::class])->group(function () {
            $c = WalletFinancePanelController::class;
            Route::get('/', [$c, 'index'])->name('index');
            Route::post('/', [$c, 'save'])->name('save');
            Route::post('child-return-policy', [$c, 'returnPolicy'])->name('return-policy');
            Route::get('{wallet}/statement', [$c, 'show'])->whereNumber('wallet')->name('show');
        });
        Route::prefix('fee-refunds')->name('fee-refunds.')->middleware([PrivateDomainResponse::class, 'module:fees,read'])->group(function () {
            $c = FeeRefundPanelController::class;
            Route::get('/', [$c, 'index'])->name('index');
            Route::post('policy', [$c, 'policy'])->name('policy');
            Route::get('new', [$c, 'form'])->name('new');
            Route::post('prepare', [$c, 'prepare'])->name('prepare');
            Route::get('{refund}', [$c, 'show'])->whereNumber('refund')->name('show');
            Route::post('{refund}/reserve', [$c, 'reserve'])->whereNumber('refund')->name('reserve');
            Route::post('{refund}/payout', [$c, 'payout'])->whereNumber('refund')->name('payout');
            Route::post('{refund}/cancel', [$c, 'cancel'])->whereNumber('refund')->name('cancel');
        });
        Route::prefix('fee-leavers')->name('fee-leavers.')->middleware([PrivateDomainResponse::class, 'module:fees,read'])->group(function () {
            $c = FeeLeaverPanelController::class;
            Route::get('/', [$c, 'index'])->name('index');
            Route::post('policy', [$c, 'policy'])->name('policy');
            Route::get('new', [$c, 'form'])->name('new');
            Route::post('prepare', [$c, 'prepare'])->name('prepare');
            Route::get('{run}', [$c, 'show'])->whereNumber('run')->name('show');
            Route::post('{run}/approve', [$c, 'approve'])->whereNumber('run')->name('approve');
            Route::post('{run}/post', [$c, 'post'])->whereNumber('run')->name('post');
        });
        Route::prefix('fee-lifecycle')->name('fee-lifecycle.')->middleware([PrivateDomainResponse::class, 'module:fees,read'])->group(function () {
            $c = FeeLifecyclePanelController::class;
            Route::get('/', [$c, 'index'])->name('index');
            Route::post('/', [$c, 'save'])->name('save');
        });
        Route::prefix('fee-discounts')->name('fee-discounts.')->middleware([PrivateDomainResponse::class, 'module:fees,read'])->group(function () {
            $c = FeeDiscountPanelController::class;
            Route::get('/', [$c, 'index'])->name('index');
            Route::get('new', [$c, 'form'])->name('new');
            Route::post('new', [$c, 'save'])->name('create');
            Route::get('{rule}/edit', [$c, 'form'])->whereNumber('rule')->name('edit');
            Route::post('{rule}/save', [$c, 'save'])->whereNumber('rule')->name('save');
            Route::post('{rule}/transition', [$c, 'transition'])->whereNumber('rule')->name('transition');
            Route::get('apply', [$c, 'application'])->name('apply');
            Route::post('prepare', [$c, 'prepare'])->name('prepare');
            Route::get('runs/{run}', [$c, 'run'])->whereNumber('run')->name('run');
            Route::post('runs/{run}/approve', [$c, 'approve'])->whereNumber('run')->name('approve');
            Route::post('runs/{run}/post', [$c, 'post'])->whereNumber('run')->name('post');
        });
        Route::prefix('fee-late-charges')->name('fee-late-charges.')->middleware([PrivateDomainResponse::class, 'module:fees,read'])->group(function () {
            $c = FeeLateChargePanelController::class;
            Route::get('/', [$c, 'index'])->name('index');
            Route::get('policy', [$c, 'policy'])->name('policy');
            Route::post('policy', [$c, 'savePolicy'])->name('save-policy');
            Route::post('review', [$c, 'review'])->name('review');
            Route::post('post', [$c, 'post'])->name('post');
        });
        Route::prefix('fee-payer-corrections')->name('fee-payer-corrections.')->middleware([PrivateDomainResponse::class, 'module:fees,read'])->group(function () {
            $c = FeePayerCorrectionPanelController::class;
            Route::get('/', [$c, 'index'])->name('index');
            Route::post('review', [$c, 'review'])->name('review');
            Route::post('post', [$c, 'post'])->name('post');
        });
        Route::prefix('fee-credit-notes')->name('fee-credit-notes.')->middleware([PrivateDomainResponse::class, 'module:fees,read'])->group(function () {
            $c = FeeCreditPanelController::class;
            Route::get('/', [$c, 'index'])->name('index');
            Route::get('new', [$c, 'form'])->name('new');
            Route::post('review', [$c, 'review'])->name('review');
            Route::post('new', [$c, 'post'])->name('post');
            Route::get('{note}', [$c, 'show'])->whereNumber('note')->name('show');
        });
        Route::prefix('fee-reminders')->name('fee-reminders.')->middleware([PrivateDomainResponse::class, 'module:fees,read'])->group(function () {
            $c = FeeReminderPanelController::class;
            Route::get('/', [$c, 'index'])->name('index');
            Route::post('/', [$c, 'save'])->name('save');
        });
        Route::prefix('fee-reporting')->name('fee-reporting.')->middleware([PrivateDomainResponse::class, 'module:fees,read'])->group(function () {
            $c = FeeReportPanelController::class;
            Route::get('/', [$c, 'index'])->name('index');
            Route::get('settings', [$c, 'settings'])->name('settings');
            Route::post('settings', [$c, 'saveSettings'])->name('settings-save');
            Route::post('export', [$c, 'export'])->name('export');
        });
        Route::prefix('bank-statements')->name('bank-statements.')->middleware([PrivateDomainResponse::class, 'module:fees,read'])->group(function () {
            $c = BankStatementPanelController::class;
            Route::get('/', [$c, 'index'])->name('index');
            Route::get('new', [$c, 'form'])->name('new');
            Route::get('template', [$c, 'template'])->name('template');
            Route::post('review-import', [$c, 'upload'])->name('upload');
            Route::post('import', [$c, 'import'])->name('import');
            Route::get('{line}', [$c, 'show'])->whereNumber('line')->name('show');
            Route::post('{line}/review', [$c, 'review'])->whereNumber('line')->name('review');
            Route::post('{line}/resolve', [$c, 'resolve'])->whereNumber('line')->name('resolve');
        });
        Route::prefix('fee-payments')->name('fee-payments.')->middleware([PrivateDomainResponse::class, 'module:fees,read'])->group(function () {
            $c = FeePaymentPanelController::class;
            Route::get('/', [$c, 'index'])->name('index');
            Route::get('new', [$c, 'form'])->name('new');
            Route::post('new', [$c, 'record'])->name('create');
            Route::post('policy', [$c, 'policy'])->name('policy');
            Route::get('accounts/{account}/statement', [$c, 'statement'])->whereNumber('account')->name('statement');
            Route::get('{payment}', [$c, 'show'])->whereNumber('payment')->name('show');
            Route::get('{payment}/allocation', [$c, 'preview'])->whereNumber('payment')->name('preview');
            Route::post('{payment}/allocation', [$c, 'allocate'])->whereNumber('payment')->name('allocate');
        });
        Route::prefix('fee-accounts')->name('fee-accounts.')->middleware([PrivateDomainResponse::class, 'module:fees,read'])->group(function () {
            Route::post('/{account}/payment-choice', [FeeAccountPanelController::class, 'paymentChoice'])->whereNumber('account')->name('payment-choice');
            $c = FeeAccountPanelController::class;
            Route::get('/', [$c, 'index'])->name('index');
            Route::get('new', [$c, 'form'])->name('new');
            Route::post('new', [$c, 'save'])->name('create');
            Route::get('{account}', [$c, 'show'])->whereNumber('account')->name('show');
            Route::get('{account}/edit', [$c, 'form'])->whereNumber('account')->name('edit');
            Route::post('{account}', [$c, 'save'])->whereNumber('account')->name('save');
            Route::post('{account}/archive', [$c, 'archive'])->whereNumber('account')->name('archive');
        });
        Route::prefix('fee-bill-runs')->name('fee-bill-runs.')->middleware([PrivateDomainResponse::class, 'module:fees,read'])->group(function () {
            $c = BillRunPanelController::class;
            Route::get('/', [$c, 'index'])->name('index');
            Route::get('new', [$c, 'form'])->name('new');
            Route::post('new', [$c, 'prepare'])->name('prepare');
            Route::get('{run}', [$c, 'show'])->whereNumber('run')->name('show');
            Route::post('{run}/approve', [$c, 'approve'])->whereNumber('run')->name('approve');
            Route::post('{run}/post', [$c, 'post'])->whereNumber('run')->name('post');
        });
        Route::get('fee-invoices/{invoice}', [BillRunPanelController::class, 'invoice'])->whereNumber('invoice')
            ->middleware([PrivateDomainResponse::class, 'module:fees,read'])->name('fee-invoices.show');

        Route::prefix('fee-plans')->name('fee-plans.')->middleware([PrivateDomainResponse::class, 'module:fees,read'])->group(function () {
            $c = FeePlanPanelController::class;
            Route::get('/', [$c, 'index'])->name('index');
            Route::get('new', [$c, 'form'])->name('new');
            Route::get('years/{year}/preview', [$c, 'preview'])->whereNumber('year')->name('preview');
            Route::post('new', [$c, 'save'])->defaults('operation', 'save')->name('create');
            Route::get('{plan}/edit', [$c, 'form'])->whereNumber('plan')->name('edit');
            Route::post('{plan}', [$c, 'save'])->whereNumber('plan')->defaults('operation', 'save')->name('save');
            Route::post('{plan}/copy', [$c, 'copy'])->whereNumber('plan')->defaults('operation', 'copy')->name('copy');
        });

        Route::get('ai-settings', [AiControlPanelController::class, 'index'])->middleware(PrivateDomainResponse::class)->name('ai-controls.index');
        Route::post('ai-settings', [AiControlPanelController::class, 'save'])->middleware(PrivateDomainResponse::class)->name('ai-controls.save');

        Route::prefix('ai-flags')->name('ai-review.')->middleware([PrivateDomainResponse::class])->group(function () {
            $c = AiReviewPanelController::class;
            Route::get('/', [$c, 'index'])->name('index');
            Route::get('{flag}', [$c, 'show'])->whereNumber('flag')->name('show');
            Route::get('{flag}/media/{reference}', [$c, 'media'])->whereNumber('flag')->where('reference', '[A-Za-z0-9_-]{1,100}')->name('media');
            Route::post('{flag}/review', [$c, 'review'])->whereNumber('flag')->name('review');
        });

        Route::prefix('weekly-digest-templates')->name('digest-panel.')->middleware([PrivateDomainResponse::class, 'module:digest,read'])->group(function () {
            $c = DigestPanelController::class;
            Route::get('/', [$c, 'index'])->name('index');
            Route::post('versions', [$c, 'write'])->defaults('operation', 'createTemplate')->name('create');
            Route::post('versions/{template}/review', [$c, 'write'])->whereNumber('template')->defaults('operation', 'reviewTemplate')->name('review');
        });

        Route::prefix('school-gallery')->name('gallery-panel.')->middleware([PrivateDomainResponse::class, 'module:gallery,read', AuthorizeGalleryPanel::class])->group(function () {
            $controller = GalleryPanelController::class;
            Route::get('/', [$controller, 'index'])->defaults('operation', 'index')->name('index');
            Route::get('photos/{photo}', [$controller, 'image'])->whereNumber('photo')->defaults('operation', 'image')->name('image');
            foreach (['upload', 'settings'] as $operation) {
                Route::post($operation, [$controller, 'write'])->defaults('operation', $operation)->name($operation);
            }
            Route::post('photos/{photo}/tags', [$controller, 'write'])->whereNumber('photo')->defaults('operation', 'propose')->name('propose');
            Route::post('photos/{photo}/withdraw', [$controller, 'write'])->whereNumber('photo')->defaults('operation', 'withdraw')->name('withdraw');
        });

        Route::prefix('school-consultations')->name('consultations-panel.')->middleware([PrivateDomainResponse::class, 'module:consultations,read', AuthorizeConsultationPanel::class])->group(function () {
            $c = ConsultationPanelController::class;
            Route::get('/', [$c, 'index'])->defaults('operation', 'index')->name('index');
            Route::get('new', [$c, 'form'])->defaults('operation', 'new')->name('new');
            Route::post('new', [$c, 'write'])->defaults('operation', 'create')->name('create');
            Route::get('{consultation}/edit', [$c, 'form'])->whereNumber('consultation')->defaults('operation', 'edit')->name('edit');
            Route::get('{consultation}/results', [$c, 'results'])->whereNumber('consultation')->defaults('operation', 'results')->name('results');
            Route::get('{consultation}/history', [$c, 'history'])->whereNumber('consultation')->defaults('operation', 'history')->name('history');
            Route::get('{consultation}', [$c, 'ballot'])->whereNumber('consultation')->defaults('operation', 'ballot')->name('ballot');
            foreach (['save', 'publish', 'close', 'cancel', 'vote'] as $operation) {
                Route::post('{consultation}/'.$operation, [$c, 'write'])->whereNumber('consultation')->defaults('operation', $operation)->name($operation);
            }
        });
        Route::prefix('school-transfers')->name('school-transfers.')->middleware(AuthorizeTransfer::class)->group(function () {
            $c = TransferPanelController::class;
            Route::get('/', [$c, 'index'])->name('index');
            Route::get('{lane}/{record}/archive', [$c, 'archive'])->whereIn('lane', ['outgoing', 'incoming', 'receipts'])->whereNumber('record')->name('archive');
            Route::post('packages', [$c, 'write'])->name('prepare');
            foreach (['release', 'cancel', 'accept'] as $action) {
                Route::post('packages/{package}/'.$action, [$c, 'write'])->whereNumber('package')->name($action);
            }
        });
        Route::prefix('admissions')->name('admissions-panel.')->middleware([AuthorizeIntake::class])->group(function () {
            $c = IntakePanelController::class;
            Route::get('/', [$c, 'index'])->name('index');
            Route::get('/new', [$c, 'configuration'])->name('new');
            Route::get('/intakes/{intake}/edit', [$c, 'configuration'])->whereNumber('intake')->name('edit');
            Route::post('/intakes/new', [$c, 'configure'])->defaults('operation', 'configure')->name('create');
            Route::post('/intakes/{intake}', [$c, 'configure'])->whereNumber('intake')->defaults('operation', 'configure')->name('configure');
            Route::post('/bulk/{operation}', [$c, 'write'])->whereIn('operation', ['offers', 'bulk-enrol'])->name('bulk');
            Route::get('/applications/{application}', [$c, 'detail'])->whereNumber('application')->name('detail');
            Route::post('/applications/{application}/{operation}', [$c, 'write'])->whereNumber('application')->whereIn('operation', ['review', 'fee', 'enrol'])->name('write');
            Route::get('/applications/{application}/documents/{handle}', [$c, 'document'])->whereNumber('application')->whereUuid('handle')->name('document');
        });

        Route::prefix('recognition/{module}')->whereIn('module', ['house_points', 'awards'])->name('recognition.')->middleware([AuthorizeRecognition::class])->group(function () {
            $c = RecognitionPanelController::class;
            Route::get('/', [$c, 'index'])->name('index');
            Route::post('catalogue/{kind}', [$c, 'write'])->defaults('operation', 'catalogue')->whereIn('kind', array_keys(RecognitionQueries::CATALOGUES))->name('catalogue-create');
            Route::post('catalogue/{kind}/{record}', [$c, 'write'])->defaults('operation', 'catalogue')->whereIn('kind', array_keys(RecognitionQueries::CATALOGUES))->whereNumber('record')->name('catalogue-save');
            Route::post('actions/{operation}', [$c, 'write'])->whereIn('operation', ['configure', 'issue', 'nominate', 'present', 'outside'])->name('create');
            Route::post('actions/{operation}/{record}', [$c, 'write'])->whereIn('operation', ['reverse', 'review', 'withdraw', 'void'])->whereNumber('record')->name('change');
        });
        Route::get('leadership-dashboard', [DashboardController::class, 'index'])->middleware(['module:leadership_dashboard', 'cache.headers:private;no_store'])->name('leadership-dashboard.index');
        Route::prefix('academic-transitions')->name('academic-transitions.')->middleware(['module:academics', 'cache.headers:private;no_store'])->group(function () {
            $controller = TransitionWorkbenchController::class;
            Route::get('/', [$controller, 'index'])->name('index');
            foreach (['preview' => 'preview', 'commit' => 'commit', 'export-preview' => 'exportPreview', 'export-prepare' => 'exportPrepare', 'retention' => 'retention'] as $path => $action) {
                Route::post($path, [$controller, $action])->name($path);
            }
            Route::post('policies/{grade}', [$controller, 'policy'])->whereNumber('grade')->name('policy');
            Route::post('exports/{package}/retry', [$controller, 'exportRetry'])->whereNumber('package')->name('export-retry');
            Route::get('exports/{package}/download', [$controller, 'exportDownload'])->whereNumber('package')->name('export-download');
        });
        Route::prefix('academic-reports')->name('academic-reports.')->middleware(['module:academics', 'cache.headers:private;no_store'])->group(function () {
            $controller = ReportWorkbenchController::class;
            Route::get('/', [$controller, 'index'])->name('index');
            foreach (['templates', 'banks', 'preview', 'prepare'] as $action) {
                Route::post($action, [$controller, $action])->name($action);
            }
            Route::get('cards/{card}/pdf', [$controller, 'pdf'])->whereNumber('card')->name('pdf');
            Route::post('cards/{card}/comment', [$controller, 'comment'])->whereNumber('card')->name('comment');
            Route::post('cards/{card}/review-comment', [$controller, 'reviewComment'])->whereNumber('card')->name('review-comment');
            Route::post('batches/{batch}/{action}', [$controller, 'action'])->whereNumber('batch')->where('action', 'source-preview|release-preview|refresh|grade-sign|principal-sign|release|retry')->name('action');
        });
        Route::prefix('academic-registers')->name('academic-registers.')->middleware('cache.headers:private;no_store')->group(function () {
            $controller = RegisterPanelController::class;
            Route::get('/', [$controller, 'index'])->name('index');
            foreach (['open', 'sync', 'review', 'settings'] as $action) {
                Route::post($action, [$controller, $action])->name($action);
            }
        });
        Route::prefix('wellbeing')->name('wellbeing.')->middleware([PrivateWellbeing::class, 'module:wellbeing'])->group(function () {
            $controller = WellbeingPanelController::class;
            Route::get('/', [$controller, 'index'])->name('index');
            Route::get('reports/{report}/photos/{photo}', [$controller, 'photoDownload'])->whereNumber('report')->whereUuid('photo')->name('photo-download');
            foreach (['categories' => 'category-create', 'categories/{category}' => 'category-save', 'categories/{category}/team' => 'team-create', 'categories/{category}/team/{assignment}' => 'team-save',
                'cases' => 'case-create', 'cases/{case}/assignments' => 'case-assignment-create', 'cases/{case}/assignments/{assignment}' => 'case-assignment-save',
                'reports/{report}/replies' => 'report-respond', 'cases/{case}/entries' => 'case-entry', 'cases/{case}/incident-link' => 'case-link', 'cases/{case}/status' => 'case-status',
                'cases/{case}/appointments' => 'appointment-create', 'cases/{case}/appointments/{appointment}' => 'appointment-save', 'cases/{case}/professional-choices' => 'case-choices'] as $path => $operation) {
                Route::post($path, [$controller, 'write'])->whereNumber(['category', 'case', 'report', 'assignment', 'appointment'])->name($operation);
            }
        });
        Route::prefix('support-plans')->name('support-plans.')->middleware('module:support_plans')->group(function () {
            $c = SupportPanelController::class;
            Route::get('/', [$c, 'index'])->name('index');
            foreach (['assignments' => 'assign', 'plans' => 'create', 'plans/{plan}/save' => 'save', 'plans/{plan}/reviews' => 'review', 'plans/{plan}/close' => 'close', 'plans/{plan}/disclosures' => 'disclose', 'assignments/{assignment}/revoke' => 'revoke-assignment', 'disclosures/{disclosure}/revoke' => 'revoke-disclosure'] as $path => $action) {
                Route::post($path, [$c, 'write'])->whereNumber(['plan', 'assignment', 'disclosure'])->name($action);
            }
        });
        Route::prefix('relief-cover')->name('relief-cover.')->middleware('module:relief_cover')->group(function () {
            $c = CoverPanelController::class;
            Route::get('/', [$c, 'index'])->name('index');
            Route::get('{absence}', [$c, 'index'])->whereNumber('absence')->name('show');
            Route::post('requests', [$c, 'write'])->name('request');
            foreach (['refresh', 'cancel'] as $operation) {
                Route::post('{absence}/'.$operation, [$c, 'write'])->whereNumber('absence')->name($operation);
            }
            Route::post('occurrences/{occurrence}/assignment', [$c, 'write'])->whereNumber('occurrence')->name('assign');
        });
        Route::get('academic-timetable', [TimetableWorkbenchController::class, 'index'])->name('academic-timetable.index');
        foreach (['room' => 'room', 'bells' => 'bells', 'preview' => 'preview', 'commit' => 'commit', 'import-map' => 'mapImport', 'import-preview' => 'previewImport', 'short-day' => 'shortDay', 'short-day-remove' => 'removeShortDay'] as $path => $action) {
            Route::post('academic-timetable/'.$path, [TimetableWorkbenchController::class, $action])->name('academic-timetable.'.$path);
        }
        Route::get('teaching-groups', [TeachingGroupController::class, 'index'])->name('teaching-groups.index');
        Route::get('teaching-groups/new', [TeachingGroupController::class, 'edit'])->name('teaching-groups.create');
        Route::post('teaching-groups', [TeachingGroupController::class, 'save'])->name('teaching-groups.store');
        Route::get('teaching-groups/{group}', [TeachingGroupController::class, 'show'])->whereNumber('group')->name('teaching-groups.show');
        Route::get('teaching-groups/{group}/edit', [TeachingGroupController::class, 'edit'])->whereNumber('group')->name('teaching-groups.edit');
        Route::put('teaching-groups/{group}', [TeachingGroupController::class, 'save'])->whereNumber('group')->name('teaching-groups.update');
        // Fill a homeroom's group with the homeroom (Action Plan 1.20).
        Route::post('teaching-groups/{group}/fill', [TeachingGroupController::class, 'fill'])->whereNumber('group')->middleware('throttle:20,1')->name('teaching-groups.fill');
        Route::post('teaching-groups/{group}/{kind}', [TeachingGroupController::class, 'row'])->whereNumber('group')->whereIn('kind', ['memberships', 'teachers'])->name('teaching-groups.rows.store');
        Route::put('teaching-groups/{group}/{kind}/{entry}', [TeachingGroupController::class, 'row'])->whereNumber('group')->whereNumber('entry')->whereIn('kind', ['memberships', 'teachers'])->name('teaching-groups.rows.update');
        Route::get('academic-allocations', [TeachingGroupController::class, 'allocations'])->name('academic-allocations.index');
        // Staff drafts from last year's allocations (Action Plan 1.20).
        Route::get('academic-allocations/draft', [TeachingGroupController::class, 'draft'])->name('academic-allocations.draft');
        Route::post('academic-allocations/draft', [TeachingGroupController::class, 'applyDraft'])->middleware('throttle:20,1')->name('academic-allocations.draft.apply');
        Route::post('academic-allocations/{kind}', [TeachingGroupController::class, 'row'])->whereIn('kind', ['homerooms', 'leadership'])->name('academic-allocations.store');
        Route::put('academic-allocations/{kind}/{entry}', [TeachingGroupController::class, 'row'])->whereNumber('entry')->whereIn('kind', ['homerooms', 'leadership'])->name('academic-allocations.update');
        Route::put('academic-structure/classes/{class}', [TeachingGroupController::class, 'map'])->whereNumber('class')->name('academic-structure.map');
        Route::get('curriculum-plans', [CurriculumController::class, 'index'])->name('curriculum.index');
        Route::get('curriculum-plans/new', [CurriculumController::class, 'edit'])->name('curriculum.create');
        Route::post('curriculum-plans', [CurriculumController::class, 'store'])->name('curriculum.store');
        Route::get('curriculum-plans/{plan}', [CurriculumController::class, 'edit'])->whereNumber('plan')->name('curriculum.edit');
        Route::put('curriculum-plans/{plan}', [CurriculumController::class, 'update'])->whereNumber('plan')->name('curriculum.update');
        Route::post('curriculum-plans/{plan}/copy', [CurriculumController::class, 'copy'])->whereNumber('plan')->name('curriculum.copy');
        Route::post('curriculum-plans/{plan}/assessments/{assessment}/publish', [CurriculumController::class, 'publish'])->whereNumber('plan')->whereNumber('assessment')->name('curriculum.publish');
        Route::get('subject-standards', [SubjectStandardController::class, 'index'])->name('subject-standards.index');
        Route::post('subject-standards/subjects', [SubjectStandardController::class, 'store'])->name('subject-standards.store');
        Route::put('subject-standards/subjects/{subject}', [SubjectStandardController::class, 'update'])->whereNumber('subject')->name('subject-standards.update');
        Route::resource('subject', SubjectController::class)->except(['create', 'edit']);
        Route::get('subject-list', [SubjectController::class, 'show']);

        Route::get('/parent/search', [ParentsController::class, 'search']);
        Route::resource('parents', ParentsController::class)->except(['create', 'store', 'edit', 'destroy']);
        Route::get('parents_list', [ParentsController::class, 'show']);

        Route::resource('session-years', SessionYearController::class)->except(['create']);
        Route::post('/session-years/{sessionYear}/clear-specific', [SessionYearController::class, 'deleteSpecificData']);
        Route::post('session-years/{id}/make-current', [SessionYearController::class, 'makeCurrent'])->whereNumber('id')->name('session-years.make-current');
        Route::get('session_years_list', [SessionYearController::class, 'show']);
        Route::get('session-year/details/{id}', [SessionYearController::class, 'details']);
        Route::get('session-year/{id}/terms', [SessionYearController::class, 'terms']);

        Route::get('attendance-monthly', [AttendanceController::class, 'monthlyView'])->name('attendance.monthly');
        Route::get('attendance/monthly-ajax', [AttendanceController::class, 'monthlyAjax'])->name('attendance.monthly.ajax');

        Route::get('email-tamplate', [EmailController::class, 'index'])->name('email.Template');
        Route::post('email/store', [EmailController::class, 'store'])->name('email.store');

        Route::get('students-report', [Student\ReportController::class, 'detail'])->name('students.report');

        Route::get('staff-attendance/check-leave', [AttendanceController::class, 'checkStaffLeave'])->name('staff-attendance.check-leave');
        Route::get('get-session-year-months', [AttendanceController::class, 'getSessionYearMonths']);

        Route::post('student-result-details', [Student\ResultController::class, 'getStudentResultDetails']);
        Route::get('students-list', [Student\StudentController::class, 'index'])->name('students.list');
        Route::get('students-list-data', [Student\StudentController::class, 'show'])->name('students.list.data');
        Route::get('student-class-sections', [Student\ClassAssignmentController::class, 'getClassSectionsBySessionYear'])->name('student.class-sections');
        Route::get('students/assign-class', [Student\ClassAssignmentController::class, 'assignClass'])->name('students.assign-class');
        Route::post('students/assign-class', [Student\ClassAssignmentController::class, 'assignClass_store'])->name('students.assign-class.store');
        Route::get('students/new-student-list', [Student\ClassAssignmentController::class, 'newStudentList'])->name('students.new-student-list');
        Route::get('students/create_bulk', [Student\BulkImportController::class, 'createBulkData'])->name('students.create-bulk-data');
        Route::get('students/download-sample', [Student\BulkImportController::class, 'downloadSampleFile'])->name('students.download-sample');
        Route::post('students/store_bulk', [Student\BulkImportController::class, 'storeBulkData'])->middleware('throttle:heavy')->name('students.store-bulk-data');
        Route::resource('students', Student\StudentController::class)->except(['edit']);
        // A learner's sign-in set-up: email the guardians a link, or print a one-time code.
        // The office's LURITS numbers (Action Plan 1.0e).
        Route::get('learners/lurits', [LuritsOfficeController::class, 'index'])->name('lurits-office.index');
        Route::post('learners/lurits', [LuritsOfficeController::class, 'store'])->middleware('throttle:60,1')->name('lurits-office.store');
        // School onboarding imports (Action Plan 0.11): homerooms, learners and guardians from CSV.
        Route::get('school-imports', [SchoolImportController::class, 'index'])->name('school-imports.index');
        Route::get('school-imports/templates/{kind}', [SchoolImportController::class, 'template'])->where('kind', 'homerooms|learners|staff')->name('school-imports.template');
        Route::post('school-imports/check', [SchoolImportController::class, 'check'])->middleware('throttle:30,1')->name('school-imports.check');
        Route::post('school-imports', [SchoolImportController::class, 'store'])->middleware('throttle:10,1')->name('school-imports.store');
        Route::post('students/{id}/invite-guardians', [LearnerInvitationController::class, 'invite'])->whereNumber('id')->name('students.invite-guardians');
        Route::post('students/{id}/sign-in-sheet', [LearnerInvitationController::class, 'sheet'])->whereNumber('id')->name('students.sign-in-sheet');

        // student generate roll number
        Route::get('student/assign-roll-number', [Student\RollNumberController::class, 'indexStudentRollNumber'])->name('students.index-students-roll-number');
        Route::get('student/list-assign-roll-number/{class_section_id}', [Student\RollNumberController::class, 'listStudentRollNumber'])->name('students.list-students-roll-number');
        Route::post('student/store-roll-number', [Student\RollNumberController::class, 'storeStudentRollNumber'])->name('students.store-roll-number');

        // Route::get('students-list', [Student\ReportController::class, 'detail'])->name('students.detail');
        Route::get('student-report/{id}', [Student\ReportController::class, 'showStudentReport'])->name('student.report');
        Route::get('student-attendance/{id}', [Student\ReportController::class, 'studentAttendanceReport'])->name('student.attendance.report');

        Route::resource('category', CategoryController::class)->except(['create']);
        Route::get('category_list', [CategoryController::class, 'show']);

        Route::resource('subject-teachers', SubjectTeacherController::class)->except(['create']);
        Route::get('subject-teachers-list', [SubjectTeacherController::class, 'show']);

        Route::resource('timetable', TimetableController::class)->except(['create', 'show', 'edit', 'update']);
        Route::get('checkTimetable', [TimetableController::class, 'checkTimetable']);

        Route::get('get-subject-by-class-section', [TimetableController::class, 'getSubjectByClassSection']);
        Route::get('get-class-terms', [TimetableController::class, 'getClassTerms']);
        Route::get('get-class-sections-with-terms', [TimetableController::class, 'getClassSectionsWithTerms']);
        Route::get('getteacherbysubject', [TimetableController::class, 'getteacherbysubject']);

        Route::get('gettimetablebyclass', [TimetableController::class, 'gettimetablebyclass'])->name('get.timetable.class');
        Route::get('gettimetablebyteacher', [TimetableController::class, 'gettimetablebyteacher']);
        Route::get('get-timetable-by-subject-teacher-class', [TimetableController::class, 'getTimetableBySubjectTeacherClass']);

        Route::get('class-timetable', [TimetableController::class, 'class_timetable']);
        Route::get('teacher-timetable', [TimetableController::class, 'teacher_timetable']);
        Route::get('get-timetable-data/{id}', [TimetableController::class, 'getTimeatableDetails']);
        Route::post('link-url-update', [TimetableController::class, 'linkUpdate']);

        Route::resource('attendance', AttendanceController::class)->except(['create', 'edit', 'update', 'destroy']);
        Route::get('view-attendance', [AttendanceController::class, 'view'])->name('attendance.view');
        Route::get('student-attendance-list', [AttendanceController::class, 'attendance_show']);
        Route::get('getAttendanceData', [AttendanceController::class, 'getAttendanceData']);
        Route::get('student-list', [AttendanceController::class, 'show']);
        Route::get('add-bulk-attendance', [AttendanceController::class, 'createBulkData'])->name('attendance.add-bulk-data');
        Route::post('attendance/store_bulk', [AttendanceController::class, 'storeBulkData'])->middleware('throttle:heavy')->name('attendance.store-bulk-data');
        Route::post('student/export', [AttendanceController::class, 'studentExport'])->middleware('throttle:heavy')->name('student-export');

        Route::resource('lesson', LessonController::class)->except(['create', 'edit']);
        Route::get('search-lesson', [LessonController::class, 'search']);
        Route::delete('file/delete/{id}', [LessonController::class, 'deleteFile'])->name('file.delete');
        Route::get('teacher-term-subjects', [LessonController::class, 'getTeacherTermSubjects'])->name('teacher.term.subjects');
        Route::resource('lesson-topic', LessonTopicController::class)->except(['create', 'edit']);

        Route::resource('announcement', AnnouncementController::class)->except(['create', 'edit']);
        Route::get('announcement-list', [AnnouncementController::class, 'show']);
        Route::get('getAssignData', [AnnouncementController::class, 'getAssignData']);
        Route::get('announcement-class-sections', [AnnouncementController::class, 'getClassSectionsForAnnouncement']);
        Route::post('announcement/update', [AnnouncementController::class, 'update'])->name('announcement.custom-update');

        Route::resource('holiday', HolidayController::class)->except(['create', 'edit']);
        Route::get('holiday-list', [HolidayController::class, 'show']);
        Route::get('holiday-view', [HolidayController::class, 'holiday_view']);

        Route::resource('assignment', AssignmentController::class)->except(['create', 'edit']);
        Route::get('assignment-class-sections', [AssignmentController::class, 'getClassSectionsBySessionYear'])->name('assignment.class-sections');
        Route::get('assignment/{id}/copy-targets', [AssignmentController::class, 'copyTargets'])->whereNumber('id')->name('assignment.copy-targets');
        Route::post('assignment/{id}/copy', [AssignmentController::class, 'copy'])->whereNumber('id')->name('assignment.copy');
        Route::get('assignment-submission', [AssignmentController::class, 'viewAssignmentSubmission'])->name('assignment.submission');
        Route::put('assignment-submission/{id}', [AssignmentController::class, 'updateAssignmentSubmission'])->name('assignment.submission.update');
        Route::get('assignment-submission-list', [AssignmentController::class, 'assignmentSubmissionList'])->name('assignment.submission.list');

        Route::resource('sliders', SliderController::class)->except(['create', 'edit']);

        Route::get('exams/exam-result', [Exam\ResultController::class, 'getExamResultIndex'])->name('exams.get-result');
        Route::post('exams/fetch-exam-result-list', [Exam\ResultController::class, 'fetchExamResultList'])->name('exams.fetch-exam-result-list');
        Route::get('exams/show-result', [Exam\ResultController::class, 'showExamResult'])->name('exams.show-result');
        Route::get('exams/class-options', [Exam\ExamController::class, 'getClassOptionsForSession'])->name('exams.class-options');

        Route::get('exams/view-result', [Exam\ResultController::class, 'viewExamResult'])->name('exams.view-result');
        Route::post('exams/update-result-marks', [Exam\MarksController::class, 'updateExamResultMarks'])->name('exams.update-result-marks');

        Route::post('exams/submit-marks', [Exam\MarksController::class, 'submitMarks'])->name('exams.submit-marks');

        Route::get('exams/upload-marks', [Exam\MarksController::class, 'uploadMarks'])->name('exams.upload-marks');
        Route::get('exams/marks-list', [Exam\MarksController::class, 'marksList'])->name('exams.marks-list');

        Route::get('exams/get-exams/{class_id}', [Exam\ExamController::class, 'getExamByClass'])->name('exams.classes');
        Route::delete('/delete-exam-class/{exam_id}/{class_id}', [Exam\ExamController::class, 'deleteExamClass']);
        Route::get('exams/get-subjects/{class_id}/{exam_id}', [Exam\ExamController::class, 'getSubjectByExam'])->name('exams.subject');
        Route::post('exams/publish/{id}', [Exam\PublishController::class, 'publishExamResult'])->name('exams.publish');
        Route::get('exams/get-publish-exam/{class_id}', [Exam\PublishController::class, 'getPublishExam'])->name('exam.publish.list');
        Route::resource('exams', Exam\ExamController::class)->except(['create', 'edit']);

        Route::post('exams/update-timetable', [ExamTimetableController::class, 'updateTimetable'])->name('exams.update-timetable');
        Route::delete('exams/delete-timetable/{id}', [ExamTimetableController::class, 'deleteTimetable'])->name('exams.delete-timetable');
        Route::get('grades', [Exam\GradeController::class, 'indexGrades'])->name('grades');
        Route::get('/grades/by-session-year/{sessionYear}', [Exam\GradeController::class, 'getGradesBySessionYear'])
            ->name('grades.bySessionYear');

        Route::get('exams/get-exam-subjects/{exam_id}', [Exam\ExamController::class, 'getExamSubjects'])->name('exams.subjects');

        Route::post('create-grades', [Exam\GradeController::class, 'createGrades'])->name('create-grades');
        Route::delete('destroy-grades/{grade_id}', [Exam\GradeController::class, 'destroyGrades'])->name('destroy-grades');

        Route::resource('exam-timetable', ExamTimetableController::class);
        // Exam mode (Action Plan 1.8): the days each grade writes exams.
        Route::get('exam-periods', [ExamPeriodController::class, 'index'])->name('exam-periods.index');
        Route::post('exam-periods', [ExamPeriodController::class, 'store'])->name('exam-periods.store');
        Route::put('exam-periods/{period}', [ExamPeriodController::class, 'update'])->whereNumber('period')->name('exam-periods.update');
        Route::delete('exam-periods/{period}', [ExamPeriodController::class, 'destroy'])->whereNumber('period')->name('exam-periods.destroy');
        Route::get('exam/get-classes/{exam_id}', [ExamTimetableController::class, 'getClassesByExam'])->name('exam-timetable.classes');
        Route::get('exam/get-subjects/{class_id}', [ExamTimetableController::class, 'getSubjectsByClass'])->name('exams.class-subjects');
        Route::get('exam/get-exams-by-session-year/{session_year_id}', [ExamTimetableController::class, 'getExamsBySessionYear'])->name('exam-timetable.exams-by-session-year');

        Route::get('email-settings', [SettingController::class, 'email_index'])->middleware('school.default')->name('setting.email-config-index');
        Route::post('email-settings', [SettingController::class, 'email_update'])->middleware('school.default');
        Route::post('verify-email-settings', [SettingController::class, 'verifyEmailConfigration'])->middleware('school.default')->name('setting.varify-email-config');

        Route::get('privacy-policy/index', [SettingController::class, 'privacy_policy_index'])->name('privacy.index');
        Route::get('terms-condition', [SettingController::class, 'terms_condition_index']);
        Route::get('contact-us', [SettingController::class, 'contact_us_index']);
        Route::get('about-us', [SettingController::class, 'about_us_index']);

        Route::post('setting-update', [SettingController::class, 'setting_page_update']);
        Route::post('notification-setting', [SettingController::class, 'notification_setting'])->middleware('school.default');

        Route::get('reset-password', function () {
            if (! auth()->user()->can('student-reset-password')) {
                return redirect(route('home'))->withErrors(['message' => trans('no_permission_message')]);
            }

            return view('students.reset_password');
        })->name('students.reset_password');
        Route::get('reset-password-list', [Student\PasswordController::class, 'reset_password']);
        Route::post('student-change-password', [Student\PasswordController::class, 'change_password']);

        Route::resource('promote-student', StudentSessionController::class)->except(['create', 'edit', 'update', 'destroy']);
        Route::get('getPromoteData', [StudentSessionController::class, 'getPromoteData']);
        Route::get('promote-student-list', [StudentSessionController::class, 'show']);
        // Bulk promotion by grade (Action Plan 1.18), written to dated enrolments.
        Route::get('promotions/by-grade', [GradePromotionController::class, 'index'])->name('promotions.by-grade');
        Route::post('promotions/by-grade/check', [GradePromotionController::class, 'preview'])->name('promotions.by-grade.preview');
        Route::post('promotions/by-grade', [GradePromotionController::class, 'store'])->name('promotions.by-grade.store');

        Route::get('resetpassword', [HomeController::class, 'resetpassword'])->name('resetpassword');
        // POST so the password isn't written to access logs with the URL.
        Route::post('checkPassword', [HomeController::class, 'checkPassword'])->middleware('throttle:password');
        Route::post('changePassword', [HomeController::class, 'changePassword'])->middleware('throttle:password');

        Route::get('edit-profile', [HomeController::class, 'editProfile'])->name('edit-profile');
        Route::post('update-profile', [HomeController::class, 'updateProfile'])->name('update-profile');

        Route::resource('language', LanguageController::class)->except(['create', 'edit'])->middleware('school.default');
        Route::get('language-sample', [LanguageController::class, 'language_sample']);
        Route::get('language-list', [LanguageController::class, 'show']);
        Route::get('language-download/{id}', [LanguageController::class, 'download']);

        Route::get('set-language/{lang}', [LanguageController::class, 'set_language']);

        // Bookmarked legacy lists lead to the canonical workflows. Old receipt
        // IDs and writes are deliberately not mapped into unrelated ledger IDs.
        Route::redirect('fees-type', '/fee-items')->name('fees-type.index');
        Route::redirect('fees/classes', '/fee-plans')->name('fees.class.index');
        Route::redirect('fees/paid', '/fee-payments')->name('fees.paid.index');
        Route::redirect('fees/transaction-logs', '/fee-payments')->name('fees.transactions.log.index');
        Route::redirect('fees-config', '/fee-plans')->name('fees.config.index');

        // Online Exam
        Route::get('online-exam/terms-conditions', [OnlineExamController::class, 'onlineExamTermsConditionIndex'])->name('online-exam.terms-conditions');
        Route::post('online-exam/store-terms-conditions', [OnlineExamController::class, 'storeOnlineExamTermsCondition'])->name('online-exam.store-terms-conditions');

        Route::resource('online-exam', OnlineExamController::class)->except(['create', 'edit']);
        Route::post('online-exam/add-new-question', [OnlineExamController::class, 'storeExamQuestionChoices'])->name('online-exam.add-new-question');
        Route::get('online-exam/get-class-subject-questions/{online_exam_id}', [OnlineExamController::class, 'getClassSubjectQuestions'])->name('online-exam-question.get-class-subject-questions');
        Route::get('get-subject-online-exam', [OnlineExamController::class, 'getSubjects']);
        Route::get('get-class-sections-by-session-year', [OnlineExamController::class, 'getClassSectionsBySessionYear']);
        Route::get('get-class-sections-by-term', [OnlineExamController::class, 'getClassSectionsByTerm']);
        Route::get('get-exam-question-index', [OnlineExamController::class, 'examQuestionsIndex'])->name('exam.questions.index');
        Route::post('online-exam/store-questions-choices', [OnlineExamController::class, 'storeQuestionsChoices'])->name('online-exam.store-choice-question');
        Route::delete('online-exam/remove-choiced-question/{id}', [OnlineExamController::class, 'removeQuestionsChoices'])->name('online-exam.remove-choice-question');
        Route::get('online-exam/result/{id}', [OnlineExamController::class, 'onlineExamResultIndex'])->name('online-exam.result.index');
        Route::get('online-exam/result-show/{id}', [OnlineExamController::class, 'showOnlineExamResult'])->name('online-exam.result.show');

        Route::get('online-exam-question/create-bulk', [OnlineExamQuestionController::class, 'createBulkData'])->name('online-exam-question.create-bulk');
        Route::get('online-exam-question/download-sample', [OnlineExamQuestionController::class, 'downloadSampleFile'])->name('online-exam-question.download-sample');
        Route::post('online-exam-question/store-bulk', [OnlineExamQuestionController::class, 'storeBulkData'])->middleware('throttle:heavy')->name('online-exam-question.store-bulk');
        Route::resource('online-exam-question', OnlineExamQuestionController::class)->except(['create']);
        Route::delete('online-exam-question/remove-option/{id}', [OnlineExamQuestionController::class, 'removeOptions']);
        Route::delete('online-exam-question/remove-answer/{id}', [OnlineExamQuestionController::class, 'removeAnswers']);
        // End Online Exam Routes

        Route::get('app-settings', [SettingController::class, 'app_index'])->middleware('school.default');
        Route::post('app-settings', [SettingController::class, 'app_update'])->middleware('school.default');

        Route::resource('stream', StreamController::class);

        Route::resource('shifts', ShiftController::class);
        Route::get('/get-class-teacher/{class_section_id}/{session_year_id}', [ClassTeacherController::class, 'getClassTeacherlist'])
            ->whereNumber(['class_section_id', 'session_year_id']);
        Route::get('get-all-class-teacher/{class_section_id}/{session_year_id}', [ClassTeacherController::class, 'getNotClassTeacherList']);
        Route::get('update-warning-modal', [HomeController::class, 'updateWarningModal'])->name('update-warning-modal');
        Route::get('attendance/create_bulk', [AttendanceController::class, 'createBulkData'])->name('attendance.create-bulk-data');

        Route::post('form-fields/change-rank', [FormFieldController::class, 'changeRank']);
        Route::resource('form-fields', FormFieldController::class);

        Route::get('notifications/recipients/list', [NotificationController::class, 'getRecipients'])->name('notifications.recipients');
        Route::resource('notifications', NotificationController::class)->except(['create', 'edit', 'update']);

        Route::delete('multiple-event/{id}', [EventController::class, 'deleteMultipleEvent'])->name('multiple.event.delete');
        Route::resource('events', EventController::class);

        Route::get('content', [WebSettingController::class, 'content_index'])->name('content.index');
        Route::post('content-bulk-update', [WebSettingController::class, 'content_update_bulk'])->name('content.bulk_update');
        Route::post('content/{id}', [WebSettingController::class, 'content_update'])->name('content.edit');
        Route::get('educational-program', [WebSettingController::class, 'educational_index'])->name('educational.index');
        Route::post('educational-program/store', [WebSettingController::class, 'educational_store'])->name('educational.store');
        Route::get('educational-program-list', [WebSettingController::class, 'educational_show'])->name('educational.show');
        Route::post('educational-program/update', [WebSettingController::class, 'educational_update'])->name('educational.update');
        Route::delete('educational-program/delete{id}', [WebSettingController::class, 'educational_delete'])->name('educational.delete');

        Route::get('faq', [WebSettingController::class, 'faq_index'])->name('faq.index');
        Route::post('faq/store', [WebSettingController::class, 'faq_store'])->name('faq.store');
        Route::get('faq-list', [WebSettingController::class, 'faq_show'])->name('faq.show');
        Route::post('faq/update/{id}', [WebSettingController::class, 'faq_update'])->name('faq.update');
        Route::delete('faq/delete/{id}', [WebSettingController::class, 'faq_delete'])->name('faq.delete');

        Route::get('contact-us/web', [WebSettingController::class, 'contact_us_index'])->name('contact_us.index');
        Route::get('contact-us-list', [WebSettingController::class, 'contact_us_show'])->name('contact_us.show');
        Route::post('contact-us/reply/{id}', [WebSettingController::class, 'reply'])->name('contact_us.reply');
        Route::delete('contact-us/delete/{id}', [WebSettingController::class, 'contact_us_delete'])->name('contact_us.delete');

        Route::get('photos', [MediaController::class, 'photo_index'])->name('photo.index');
        Route::post('photos/store', [MediaController::class, 'photo_store'])->name('photos.store');
        Route::get('photos-list', [MediaController::class, 'photo_show'])->name('photos.show');
        Route::get('photos/edit/{id}', [MediaController::class, 'edit_index'])->name('edit.index');
        Route::post('photos/update', [MediaController::class, 'photo_update'])->name('photo.update');
        Route::delete('photos/delete/{id}', [MediaController::class, 'photo_delete'])->name('photo.delete');
        Route::post('image/update', [MediaController::class, 'image_update'])->name('image.update');
        Route::delete('image/delete/{id}', [MediaController::class, 'image_delete'])->name('image.delete');

        Route::get('/videos-index', [MediaController::class, 'video_index'])->name('video.index');
        Route::post('videos/store', [MediaController::class, 'video_store'])->name('video.store');
        Route::get('videos-list', [MediaController::class, 'video_show'])->name('video.show');
        Route::post('videos/update/{id}', [MediaController::class, 'video_update'])->name('video.update');
        Route::delete('videos/delete/{id}', [MediaController::class, 'video_delete'])->name('video.delete');

        Route::resource('staff', StaffController::class);
        Route::get('generate_teacher_id', [StaffController::class, 'generateStaffId'])->name('generate_teacher_id.index');
        Route::get('generate_staff_id', [StaffController::class, 'generateStaffId'])->name('generate_staff_id.index');
        Route::get('generate-staff-id/list', [StaffController::class, 'staffIdCardList'])->name('generate_staff_id.list');
        Route::post('generate-staff-id-card', [StaffController::class, 'generateStaffIdCard'])->middleware('throttle:heavy')->name('generate_staff_id.card');

        Route::get('staff-attendance', [StaffController::class, 'stafflist'])->name('attendance.staff_attendance');
        Route::get('/staff-attendance/list', [StaffController::class, 'staffListData'])->name('staff.attendance.list');

        // Route::post('staff-attendance/get', [StaffController::class, 'getStaffAttendance'])->name('get.attendance');
        Route::post('/staff-attendance/store', [StaffController::class, 'storeAttendance'])->name('staff-attendance.store');
        Route::post('/staff-attendance/bulk-store', [StaffController::class, 'storeAttendance'])->name('staff-attendance.bulk-store');
        Route::get('/staff-attendance/get-attendance', [StaffController::class, 'staffattendanceData'])->name('staff-attendance.list');

        Route::get('generate-id', [Student\IdCardController::class, 'generateIdCardIndex'])->name('generate_id.index');
        Route::get('id-card-settings', [Student\IdCardController::class, 'idCardSettingIndex'])->name('id_card_setting.index');
        Route::post('id-card-settings/update', [Student\IdCardController::class, 'updateIdCardSetting'])->name('id_card_settings.update');
        Route::post('generate-id-card', [Student\IdCardController::class, 'generateIdCard'])->middleware('throttle:heavy');
        Route::delete('remove-image/{type}', [Student\IdCardController::class, 'deleteImage']);

        Route::get('generate-bonafide-certificate/{id}', [Student\CertificateController::class, 'bonafideCertificateIndex'])->name('bonafide.certificate.index');
        Route::post('bonafide-certificate', [Student\CertificateController::class, 'generateBonafideCertificate'])->middleware('throttle:heavy')->name('bonafide.certificate.pdf');

        Route::get('generate-leaving-certificate/{id}', [Student\CertificateController::class, 'leavingCertificateIndex'])->name('leaving.certificate.index');
        Route::post('leaving-certificate', [Student\CertificateController::class, 'generateLeavingCertificate'])->middleware('throttle:heavy')->name('leaving.certificate.pdf');

        Route::get('student-generate-result', [Student\ResultController::class, 'resultIndex'])->name('student.result.index');

        Route::get('get-student-list', [Student\ResultController::class, 'studentList'])->name('get.student.list');
        Route::get('generate-result/{id}', [Student\ResultController::class, 'generateResult'])->middleware('throttle:heavy')->name('generate.result');
        Route::get('generate-exam-result/{id}', [Student\ResultController::class, 'generateExamResult'])->middleware('throttle:heavy')->name('generate.exam.result');

        Route::resource('leave-master', LeaveMasterController::class);

        Route::get('leave-report', [Leave\ReportController::class, 'leaveReportIndex'])->name('leave-report.index');
        Route::get('leave-details', [Leave\ReportController::class, 'leaveDetails'])->name('leave-details-list');
        Route::get('leave-request', [Leave\RequestController::class, 'leaveRequestIndex'])->name('leave-request.index');
        Route::get('leave-request-show', [Leave\RequestController::class, 'leaveRequestShow'])->name('leave-request.show');
        Route::post('leave-request-update', [Leave\RequestController::class, 'leaveStatusUpdate'])->name('leave-status.update');
        Route::resource('leave', Leave\LeaveController::class)->except(['create', 'edit', 'update']);
        Route::post('leave-add-admin', [Leave\RequestController::class, 'adminLeaveStore'])->name('leave.store.admin');

        Route::get('chat-settings', [SettingController::class, 'chat_setting_index'])->name('chat_setting.index');
        Route::post('chat-settings-update', [SettingController::class, 'chat_setting_update'])->name('chat_setting.update');
        Route::post('delete-chat-messages', [SettingController::class, 'delete_chat_messages'])->name('chat_message.delete');

        Route::get('attendance-report', [AttendanceController::class, 'attendaceReportIndex'])->name('attendace_report.index');
        Route::get('student-attendance-report', [AttendanceController::class, 'attendanceReportShow']);

        Route::get('view-schedule/{id}', [EventController::class, 'viewScheduleIndex'])->name('view_schedule.index');
        Route::post('events-update', [EventController::class, 'updateEvents'])->name('events.update-schedules');

        Route::get('online-registration', [Student\OnlineRegistrationController::class, 'onlineRegistrationIndex'])->name('online-registration.index');
        Route::get('online-registration-list', [Student\OnlineRegistrationController::class, 'onlineRegistrationList'])->name('online-registration.list');
        Route::delete('permanent-delete/{id}', [Student\StudentController::class, 'permanentDelete'])->name('permanent-delete');
        Route::post('update-active-status', [Student\OnlineRegistrationController::class, 'updateStatus'])->name('update-active-status');
        Route::get('get-class-section-by-class/{class_id}', [Student\ClassAssignmentController::class, 'getClassSectionByClass']);
        Route::get('term/classes', [TermController::class, 'classSettings'])->name('term.classes.index');
        Route::post('term/classes/enable', [TermController::class, 'enableClass'])->name('term.classes.enable');
        Route::post('term-templates', [TermController::class, 'saveTemplate'])->name('term.templates.store');
        Route::post('term-templates/apply', [TermController::class, 'applyTemplate'])->name('term.templates.apply');
        Route::resource('term', TermController::class)->except(['create', 'edit', 'show']);

        Route::get('class-subject-edit/{id}', [Classes\SubjectController::class, 'classSubjectsEdit'])->name('class-subject-edit.index');
        Route::get('student-leave-request', [Leave\StudentLeaveController::class, 'studentLeaveRequestIndex'])->name('student-leave-request.index');
        Route::get('student-leave-request-list', [Leave\StudentLeaveController::class, 'studentLeaveRequestList'])->name('student-leave-request.show');
        Route::post('student-leave-request-update', [Leave\StudentLeaveController::class, 'studentLeaveStatusUpdate'])->name('student-leave-status.update');
        Route::get('student-leave-class-sections', [Leave\StudentLeaveController::class, 'getStudentLeaveClassSections'])->name('student-leave-class-sections');

        // route and method is not used
        Route::get('staff-leave', [Leave\StaffLeaveController::class, 'staffLeaveIndex'])->name('staff-leave.index');
        Route::get('staff-leave-list', [Leave\StaffLeaveController::class, 'staffLeaveList'])->name('staff-leave.show');
    });
});

// Old chat messages are deleted daily by the scheduler (routes/console.php), not by a public URL.

// Payment gateway webhooks. Stripe is the only gateway (Razorpay, Paystack and
// Flutterwave were removed in Phase 0).

// Amazon SES bounce, complaint and delivery notices, posted by SNS (signed;
// only the topics in services.ses_notifications.topic_arns are accepted).
Route::post('webhook/mail/ses', [MailWebhookController::class, 'ses'])->middleware('throttle:mail-webhook')->name('webhook.mail.ses');

// Linked from the public website footer; the school may not have written them yet.
Route::get('/privacy-policy', function () {
    $settings = getSettings('privacy_policy');
    echo ! empty($settings['privacy_policy']) ? $settings['privacy_policy'] : e(trans('page_not_published_yet'));
})->middleware('school.context:public');

Route::get('/terms-conditions', function () {
    $settings = getSettings('terms_condition');
    echo ! empty($settings['terms_condition']) ? $settings['terms_condition'] : e(trans('page_not_published_yet'));
})->middleware('school.context:public');

// Cache Clear, Super Admin only. It is a form post: as a link, another site could
// send a signed-in user to it, and clearing the cache also resets rate limits when
// they share the default cache store. The storage link, migrations and the
// installer seeder run at deploy time (docker/entrypoint.sh), not from a URL.
Route::post('clear', function () {
    Artisan::call('view:clear');
    Artisan::call('route:clear');
    Artisan::call('config:clear');
    Artisan::call('cache:clear');

    return redirect()->back();
})->middleware(['auth', 'school.context', 'role:Super Admin']);

Route::group(['prefix' => 'common'], static function () {
    Route::get('/js/lang', [Controller::class, 'readLanguageFile'])->name('common.language.read');
});

// Module settings use their FormRequest's office-role check.
Route::middleware(['auth', 'school.context', EnforceReadOnlyYears::class])->group(function () {
    Route::get('/school-information', [SchoolInformationController::class, 'index'])->name('school-information.index');
    Route::put('/school-information', [SchoolInformationController::class, 'update'])->name('school-information.update');
    Route::post('/school-information/documents', [SchoolInformationController::class, 'publish'])->name('school-information.documents.store');
    Route::delete('/school-information/documents/{document}', [SchoolInformationController::class, 'unpublish'])->whereUuid('document')->name('school-information.documents.destroy');
    Route::get('/profile-configuration', [ProfileFieldController::class, 'index'])->name('profile-configuration.index');
    Route::post('/profile-configuration/categories', [ProfileFieldController::class, 'category'])->name('profile-configuration.categories.store');
    Route::put('/profile-configuration/categories/{category}', [ProfileFieldController::class, 'category'])->whereNumber('category')->name('profile-configuration.categories.update');
    Route::delete('/profile-configuration/categories/{category}', [ProfileFieldController::class, 'removeCategory'])->whereNumber('category')->name('profile-configuration.categories.destroy');
    Route::post('/profile-configuration/definitions', [ProfileFieldController::class, 'field'])->name('profile-configuration.definitions.store');
    Route::put('/profile-configuration/definitions/{definition}', [ProfileFieldController::class, 'field'])->whereNumber('definition')->name('profile-configuration.definitions.update');
    Route::delete('/profile-configuration/definitions/{definition}', [ProfileFieldController::class, 'removeField'])->whereNumber('definition')->name('profile-configuration.definitions.destroy');
    // A learner's guardians, managed by the office (GuardianLinks checks the right).
    Route::get('/learners/{learner}/guardians', [LearnerGuardiansController::class, 'index'])->whereNumber('learner')->name('learner-guardians.index');
    Route::post('/learners/{learner}/guardians', [LearnerGuardiansController::class, 'store'])->whereNumber('learner')->middleware('throttle:30,1')->name('learner-guardians.store');
    Route::put('/learners/{learner}/guardians/{link}', [LearnerGuardiansController::class, 'update'])->whereNumber(['learner', 'link'])->name('learner-guardians.update');
    Route::post('/learners/{learner}/guardians/{link}/end', [LearnerGuardiansController::class, 'end'])->whereNumber(['learner', 'link'])->name('learner-guardians.end');
    Route::get('/access-review', AccessReviewController::class)->name('access-review.index');
    Route::get('/family-reach', FamilyReachController::class)->name('family-reach.index');
    Route::get('/people-profiles/{person}/export', [ProfileFormController::class, 'export'])->whereNumber('person')->name('people-profiles.export');
    Route::post('/people-profiles/{person}/invitation', [ProfileFormController::class, 'invite'])->whereNumber('person')->middleware('throttle:20,1')->name('people-profiles.invitation');
    Route::get('/people-profiles/{person?}', [ProfileFormController::class, 'index'])->whereNumber('person')->name('people-profiles.index');
    Route::put('/people-profiles/{person?}', [ProfileFormController::class, 'update'])->whereNumber('person')->name('people-profiles.update');
    Route::get('/notification-rules', [NotificationRuleController::class, 'index'])->name('notification-rules.index');
    Route::post('/notification-rules', [NotificationRuleController::class, 'update'])->name('notification-rules.update');
    Route::get('/school-modules', [ModuleController::class, 'index'])->name('school-modules.index');
    Route::put('/school-modules/{module}', [ModuleController::class, 'update'])->name('school-modules.update');
});

Route::middleware(['auth', 'school.context', 'staff', 'module:academics', EnforceReadOnlyYears::class])->prefix('learner-notes')->name('learner-notes.')->group(function () {
    Route::get('/', [ProgressPanelController::class, 'index'])->name('index');
    Route::post('learners/{learner}/notes', [ProgressPanelController::class, 'write'])->whereNumber('learner')->name('create');
    Route::post('notes/{note}', [ProgressPanelController::class, 'write'])->whereNumber('note')->name('update');
    Route::post('learners/{learner}/assignments', [ProgressPanelController::class, 'write'])->whereNumber('learner')->name('assign');
    Route::post('learners/{learner}/assignments/revoke', [ProgressPanelController::class, 'write'])->whereNumber('learner')->name('revoke-assignment');
    Route::post('settings', [ProgressPanelController::class, 'write'])->name('configure');
});

Route::middleware([PrivateMastery::class, 'auth', 'school.context', 'staff', 'module:early_warning', EnforceReadOnlyYears::class])->prefix('risk-interventions')->name('risk-interventions.')->group(function () {
    Route::get('/', [RiskPanelController::class, 'index'])->name('index');
    Route::post('learners/{learner}/interventions', [RiskPanelController::class, 'write'])->whereNumber('learner')->name('create');
    Route::post('interventions/{intervention}', [RiskPanelController::class, 'write'])->whereNumber('intervention')->name('update');
    Route::post('interventions/{intervention}/owner', [RiskPanelController::class, 'write'])->whereNumber('intervention')->name('transfer');
    Route::post('learners/{learner}/assignments', [RiskPanelController::class, 'write'])->whereNumber('learner')->name('assign');
    Route::post('learners/{learner}/assignments/revoke', [RiskPanelController::class, 'write'])->whereNumber('learner')->name('revoke');
    // Caseload renewals into the new year (Action Plan 1.20).
    Route::get('renewals', [CaseloadRenewalController::class, 'index'])->name('renewals');
    Route::post('renewals', [CaseloadRenewalController::class, 'store'])->middleware('throttle:20,1')->name('renewals.store');
    Route::post('settings', [RiskPanelController::class, 'write'])->name('configure');
});

Route::middleware([PrivateMastery::class, 'auth', 'school.context', 'staff', 'module:discipline', EnforceReadOnlyYears::class])->prefix('school-discipline')->name('discipline-panel.')->group(function () {
    $c = DisciplinePanelController::class;
    Route::get('/', [$c, 'index'])->name('index');
    Route::post('framework', [$c, 'write'])->name('configure');
    Route::post('learners/{learner}/records', [$c, 'write'])->whereNumber('learner')->name('create');
    Route::post('records/{record}/review', [$c, 'write'])->whereNumber('record')->name('review');
    Route::post('records/{record}/hearing', [$c, 'write'])->whereNumber('record')->name('hearing');
    Route::post('detentions', [$c, 'write'])->name('detention_create');
    Route::post('detentions/{detention}/entries', [$c, 'write'])->whereNumber('detention')->name('enroll');
    Route::post('detentions/{detention}/entries/{entry}/attendance', [$c, 'write'])->whereNumber('detention')->whereNumber('entry')->name('attendance');
    Route::post('detentions/{detention}/cancel', [$c, 'write'])->whereNumber('detention')->name('cancel');
});

// Cards are permanent identities, not edits to the panel's selected academic year.
// Each operation resolves the current subject, membership and approval afresh.
Route::middleware([PrivateDomainResponse::class, 'auth', 'school.context', AuthorizeCardsPanel::class])->prefix('school-cards')->name('cards-panel.')->group(function () {
    $c = CardPanelController::class;
    Route::get('/', [$c, 'index'])->name('index');
    Route::post('actions/{operation}', [$c, 'write'])->whereIn('operation', ['issue', 'portrait'])->name('create');
    Route::post('cards/{card}/{operation}', [$c, 'write'])->whereUuid('card')->whereIn('operation', ['freeze', 'replace', 'restore'])->name('change');
    Route::get('cards/{card}/portrait', [$c, 'portrait'])->whereUuid('card')->defaults('operation', 'portrait')->name('portrait');
    Route::post('print', [$c, 'print'])->defaults('operation', 'print')->name('print');
});
Route::middleware([PrivateDomainResponse::class, 'auth', 'school.context', AuthorizeCardsPanel::class])->prefix('school-card-readers')->name('card-readers-panel.')->group(function () {
    $c = CardPanelController::class;
    Route::get('/', [$c, 'index'])->name('index');
    Route::post('/', [$c, 'write'])->defaults('operation', 'register')->name('register');
    Route::post('{device}/revoke', [$c, 'write'])->whereUuid('device')->defaults('operation', 'revoke')->name('write');
});

Route::view('card-reader', 'cards.scanner')->middleware([PrivateDomainResponse::class, 'throttle:60,1'])->name('card-reader.index');
Route::view('card-reader/offline', 'cards.offline-scanner')->middleware([PrivateDomainResponse::class, 'throttle:60,1'])->name('card-reader.offline');

// Every staff form resolves its actual group/year before private fields.
Route::middleware([PrivateDomainResponse::class, 'auth', 'school.context', AuthorizeFamilyFormsPanel::class])->prefix('family-forms')->name('family-forms-panel.')->group(function () {
    $controller = FamilyPanelController::class;
    Route::get('/', [$controller, 'index'])->defaults('operation', 'index')->name('index');
    Route::get('new', [$controller, 'form'])->defaults('operation', 'new')->name('new');
    Route::post('prepare', [$controller, 'write'])->defaults('operation', 'prepare')->name('prepare');
    Route::post('consents', [$controller, 'write'])->defaults('operation', 'create')->name('create');
    Route::get('consents/{consent}/managed', [$controller, 'show'])->whereUuid('consent')->defaults('operation', 'managed')->name('managed');
    Route::post('consents/{consent}/cancel', [$controller, 'write'])->whereUuid('consent')->defaults('operation', 'cancel')->name('cancel');
});

Route::middleware([PrivateDomainResponse::class, 'auth', 'school.context'])->prefix('family-visits')->name('family-visits-panel.')->group(function () {
    $controller = VisitPanelController::class;
    Route::get('/', [$controller, 'index'])->name('index');
    Route::post('{operation}', [$controller, 'write'])->whereIn('operation', ['meeting-create', 'meeting-cancel', 'pass-approve', 'pass-cancel', 'rules'])->name('write');
});

Route::middleware([PrivateDomainResponse::class, 'auth', 'school.context', 'module:pathway,read'])->prefix('pathways')->name('pathways-panel.')->group(function () {
    $controller = PathwayPanelController::class;
    Route::get('/', [$controller, 'index'])->name('index');
    Route::get('new/{kind}', [$controller, 'form'])->whereIn('kind', ['programme', 'subject_choice_guide'])->name('new');
    Route::get('references/{reference}/edit', [$controller, 'form'])->whereUuid('reference')->name('edit');
    Route::post('references', [$controller, 'save'])->middleware('module:pathway,write')->name('store');
    Route::post('references/{reference}', [$controller, 'save'])->whereUuid('reference')->middleware('module:pathway,write')->name('update');
});

Route::middleware([PrivateDomainResponse::class, 'auth', 'school.context', 'module:ask_school,read', EnforceReadOnlyYears::class])->prefix('ask-school')->name('assistance-panel.')->group(function () {
    $controller = AssistancePanelController::class;
    Route::get('/', [$controller, 'index'])->name('index');
    Route::get('threads/{thread}', [$controller, 'index'])->whereUuid('thread')->name('thread');
    Route::post('threads/{thread}/{operation}', [$controller, 'write'])->whereUuid('thread')->whereIn('operation', ['claim', 'reply', 'close', 'reassign', 'translate'])->name('thread-write');
    Route::post('documents', [$controller, 'write'])->defaults('operation', 'publish')->name('publish');
    Route::post('documents/{document}/withdraw', [$controller, 'write'])->whereUuid('document')->defaults('operation', 'withdraw')->name('withdraw');
    Route::post('turns/{turn}/run', [$controller, 'write'])->whereUuid('turn')->defaults('operation', 'run')->name('run');
});

Route::middleware([PrivateDomainResponse::class, 'auth', 'school.context', 'module:peer_tutoring,read'])->prefix('peer-tutoring')->name('peer-tutoring-panel.')->group(function () {
    $controller = PeerPanelController::class;
    Route::get('/', [$controller, 'index'])->name('index');
    Route::post('sessions', [$controller, 'write'])->defaults('action', 'create')->name('create');
    Route::post('sessions/{session}/{action}', [$controller, 'write'])->whereUuid('session')->whereIn('action', ['complete', 'cancel', 'reverse'])->name('change');
});

Route::middleware([PrivateDomainResponse::class, 'auth', 'school.context', AuthorizeGroupChatPanel::class])
    ->prefix('group-conversations')->name('group-chat-panel.')->group(function () {
        $controller = GroupChatPanelController::class;
        Route::get('/', [$controller, 'index'])->name('index');
        Route::get('groups/{group}', [$controller, 'index'])->whereNumber('group')->name('group');
        Route::get('groups/{group}/threads/{thread}', [$controller, 'index'])->whereNumber('group')->whereUuid('thread')->name('show');
        Route::get('groups/{group}/lease/{thread?}', [$controller, 'lease'])->whereNumber('group')->whereUuid('thread')->name('lease');
        Route::post('groups/{group}/create', [$controller, 'write'])->whereNumber('group')->defaults('operation', 'create')->name('create');
        Route::post('groups/{group}/threads/{thread}/{operation}', [$controller, 'write'])->whereNumber('group')->whereUuid('thread')->whereIn('operation', ['send', 'state'])->name('write');
        Route::post('groups/{group}/threads/{thread}/messages/{message}/remove', [$controller, 'write'])->whereNumber('group')->whereUuid('thread')->whereNumber('message')->defaults('operation', 'remove')->name('remove');
    });

Route::middleware([PrivateDomainResponse::class, 'auth', 'school.context', FoundationStaffContext::class])->prefix('foundation-classrooms')->name('foundation-panel.')->group(function () {
    $c = FoundationPanelController::class;
    Route::get('/', [$c, 'index'])->defaults('foundation_action', 'configuration')->name('index');
    Route::post('groups/{group}/eligibility', [$c, 'write'])->whereNumber('group')->defaults('foundation_action', 'eligibility')->name('eligibility');
    Route::post('devices', [$c, 'write'])->defaults('foundation_action', 'register')->name('register');
    Route::post('devices/{device}/revoke', [$c, 'write'])->whereUuid('device')->defaults('foundation_action', 'device-revoke')->name('device-revoke');
    Route::post('authorizations', [$c, 'write'])->defaults('foundation_action', 'grant')->name('grant');
    Route::post('authorizations/{lease}/revoke', [$c, 'write'])->whereUuid('lease')->defaults('foundation_action', 'revoke')->name('revoke');
});
Route::view('foundation-classroom', 'classroom.device')->middleware([PrivateDomainResponse::class, 'throttle:60,1'])->name('foundation-classroom.device');

Route::middleware([PrivateDomainResponse::class, 'auth', 'school.context', AuthorizeCardEncodingPanel::class])->prefix('school-cards/cards/{card}/encoding')->whereUuid('card')->name('cards-encoding.')->group(function () {
    Route::get('/', [CardEncodingPanelController::class, 'index'])->name('index');
    Route::post('/', [CardEncodingPanelController::class, 'prepare'])->name('prepare');
});

Route::get('school-cards/encoder-runtime', [CardEncodingPanelController::class, 'runtime'])->middleware([PrivateDomainResponse::class, 'auth', 'school.context', AuthorizeCardEncodingPanel::class])->name('cards-encoding.runtime');
Route::view('card-tap', 'cards.tap')->middleware([PrivateDomainResponse::class, 'throttle:60,1'])->name('cards.tap');
