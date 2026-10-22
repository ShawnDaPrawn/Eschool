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
use App\Http\Controllers\Fees;
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
use App\Http\Controllers\ParentsController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\SemesterController;
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
use App\Http\Controllers\TimetableController;
use App\Http\Controllers\WebController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\Webhooks\MailWebhookController;
use App\Http\Controllers\WebSettingController;
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

Route::get('login', [LoginController::class, 'showLoginForm'])->middleware('guest')->name('login');
Route::post('login', [LoginController::class, 'login'])->middleware(['guest', 'throttle:login']);
Route::post('logout', [LoginController::class, 'logout'])->name('logout');

// There is no public sign-up. Staff create accounts, and those accounts always
// have a role. Students apply through the public online registration form.

// Password Reset Routes (if needed)
Route::get('password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->middleware('throttle:login')->name('password.email');
Route::get('password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('password/reset', [ResetPasswordController::class, 'reset'])->name('password.update');
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

Route::group(['middleware' => ['Role', 'auth']], function () {
    Route::post('/set-session-year', [SessionYearController::class, 'setSessionYearToSessionStorage'])->name('set.session.year');
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

        Route::get('roles-list/{id}', [RoleController::class, 'showList'])->name('roles-list');
        Route::get('roles-data', [RoleController::class, 'show'])->name('roles.data');
        Route::resource('roles', RoleController::class);

        Route::get('settings', [SettingController::class, 'index']);
        Route::post('settings', [SettingController::class, 'update']);

        Route::get('fcm-settings', [SettingController::class, 'fcm_index']);

        Route::resource('medium', MediumController::class)->except(['create']);
        Route::get('medium_list', [MediumController::class, 'show']);

        Route::resource('teachers', TeacherController::class)->except(['create']);
        Route::get('teacher_details', [TeacherController::class, 'teacherListIndex'])->name('teacher.details');
        Route::get('teacher_list', [TeacherController::class, 'show']);

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
        Route::get('class/subject/semester-details/{class_id}', [Classes\SubjectController::class, 'subjectSemesterDetails'])->name('class.subject.semester-details');
        Route::get('class-list', [Classes\ClassController::class, 'show']);
        Route::resource('class', Classes\ClassController::class)->except(['create', 'edit']);

        Route::get('class-subject-list/{medium_id}', [Classes\SubjectController::class, 'getSubjectsByMediumId']);

        Route::get('assign/class/teacher', [ClassTeacherController::class, 'teacher'])->name('class.teacher');
        Route::post('class/teacher/store', [ClassTeacherController::class, 'assign_teacher'])->name('class.teacher.store');
        Route::get('class-teacher-list', [ClassTeacherController::class, 'show']);
        Route::post('remove-class-teacher/{id}/{class_teacher_id}', [ClassTeacherController::class, 'removeClassTeacher']);

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
        Route::get('session-year/{id}/semesters', [SessionYearController::class, 'semesters']);
        Route::delete('remove-installment-data/{id}', [SessionYearController::class, 'deleteInstallmentData']);

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
        Route::get('get-class-semesters', [TimetableController::class, 'getClassSemesters']);
        Route::get('get-class-sections-with-semesters', [TimetableController::class, 'getClassSectionsWithSemesters']);
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
        Route::get('teacher-semester-subjects', [LessonController::class, 'getTeacherSemesterSubjects'])->name('teacher.semester.subjects');
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
        Route::get('exam/get-classes/{exam_id}', [ExamTimetableController::class, 'getClassesByExam'])->name('exam-timetable.classes');
        Route::get('exam/get-subjects/{class_id}', [ExamTimetableController::class, 'getSubjectsByClass'])->name('exams.class-subjects');
        Route::get('exam/get-exams-by-session-year/{session_year_id}', [ExamTimetableController::class, 'getExamsBySessionYear'])->name('exam-timetable.exams-by-session-year');

        Route::get('email-settings', [SettingController::class, 'email_index'])->name('setting.email-config-index');
        Route::post('email-settings', [SettingController::class, 'email_update']);
        Route::post('verify-email-settings', [SettingController::class, 'verifyEmailConfigration'])->name('setting.varify-email-config');

        Route::get('privacy-policy/index', [SettingController::class, 'privacy_policy_index'])->name('privacy.index');
        Route::get('terms-condition', [SettingController::class, 'terms_condition_index']);
        Route::get('contact-us', [SettingController::class, 'contact_us_index']);
        Route::get('about-us', [SettingController::class, 'about_us_index']);

        Route::post('setting-update', [SettingController::class, 'setting_page_update']);
        Route::post('notification-setting', [SettingController::class, 'notification_setting']);

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

        Route::get('resetpassword', [HomeController::class, 'resetpassword'])->name('resetpassword');
        // POST so the password isn't written to access logs with the URL.
        Route::post('checkPassword', [HomeController::class, 'checkPassword'])->middleware('throttle:password');
        Route::post('changePassword', [HomeController::class, 'changePassword'])->middleware('throttle:password');

        Route::get('edit-profile', [HomeController::class, 'editProfile'])->name('edit-profile');
        Route::post('update-profile', [HomeController::class, 'updateProfile'])->name('update-profile');

        Route::resource('language', LanguageController::class)->except(['create', 'edit']);
        Route::get('language-sample', [LanguageController::class, 'language_sample']);
        Route::get('language-list', [LanguageController::class, 'show']);
        Route::get('language-download/{id}', [LanguageController::class, 'download']);

        Route::get('set-language/{lang}', [LanguageController::class, 'set_language']);

        // fees
        Route::resource('fees-type', Fees\TypeController::class)->except(['create', 'edit']);

        Route::get('fees/classes', [Fees\ClassController::class, 'feesClassListIndex'])->name('fees.class.index');
        // Route::post('fees/classes/update', [Fees\ClassController::class, 'updateFeesClass'])->name('fees.class.update');
        Route::get('fees/classes/list', [Fees\ClassController::class, 'feesClassList'])->name('fees.class.list');

        Route::post('class/fees-type', [Fees\ClassController::class, 'updateFeesClass'])->name('class.fees.type.update');
        Route::delete('class/fees-type/{fees_class_id}', [Fees\ClassController::class, 'removeFeesClass'])->name('class.fees.type.delete');

        Route::get('fees/paid', [Fees\PaidController::class, 'feesPaidListIndex'])->name('fees.paid.index');
        Route::get('fees/paid/list', [Fees\PaidController::class, 'feesPaidList'])->name('fees.paid.list');

        Route::get('fees-config', [Fees\ConfigController::class, 'feesConfigIndex'])->name('fees.config.index');
        Route::post('fees-config/update', [Fees\ConfigController::class, 'feesConfigUpdate'])->name('fees.config.udpate');
        Route::delete('fees/paid/remove-choiceable-fees/{id}', [Fees\PaymentController::class, 'feesPaidRemoveChoiceableFees'])->name('fees.paid.remove.choiceable.fees');
        Route::delete('fees/paid/remove-installment-fees/{id}', [Fees\PaymentController::class, 'feesPaidRemoveInstallmentFees'])->name('fees.paid.remove.installment.fees');
        Route::delete('fees/paid/clear-data/{id}', [Fees\PaidController::class, 'clearFeesPaidData'])->name('fees.paid.clear.data');

        // Installment Fees Management

        Route::post('fees/optional-paid/store', [Fees\PaymentController::class, 'optionalFeesPaidStore'])->name('fees.optional-paid.store');
        Route::post('fees/compulsory-paid/store', [Fees\PaymentController::class, 'compulsoryFeesPaidStore'])->name('fees.compulsory-paid.store');

        Route::get('fees/transaction-logs', [Fees\TransactionLogController::class, 'feesTransactionsLogsIndex'])->name('fees.transactions.log.index');
        Route::get('fees/transaction-logs/list', [Fees\TransactionLogController::class, 'feesTransactionsLogsList'])->name('fees.transactions.log.list');

        Route::get('fees/paid/receipt-pdf/{id}', [Fees\PaidController::class, 'feesPaidReceiptPDF'])->middleware('throttle:heavy')->name('fees.paid.receipt.pdf');

        // Online Exam
        Route::get('online-exam/terms-conditions', [OnlineExamController::class, 'onlineExamTermsConditionIndex'])->name('online-exam.terms-conditions');
        Route::post('online-exam/store-terms-conditions', [OnlineExamController::class, 'storeOnlineExamTermsCondition'])->name('online-exam.store-terms-conditions');

        Route::resource('online-exam', OnlineExamController::class)->except(['create', 'edit']);
        Route::post('online-exam/add-new-question', [OnlineExamController::class, 'storeExamQuestionChoices'])->name('online-exam.add-new-question');
        Route::get('online-exam/get-class-subject-questions/{online_exam_id}', [OnlineExamController::class, 'getClassSubjectQuestions'])->name('online-exam-question.get-class-subject-questions');
        Route::get('get-subject-online-exam', [OnlineExamController::class, 'getSubjects']);
        Route::get('get-class-sections-by-session-year', [OnlineExamController::class, 'getClassSectionsBySessionYear']);
        Route::get('get-class-sections-by-semester', [OnlineExamController::class, 'getClassSectionsBySemester']);
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

        Route::get('app-settings', [SettingController::class, 'app_index']);
        Route::post('app-settings', [SettingController::class, 'app_update']);

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
        Route::get('semester/class-config', [SemesterController::class, 'getClassConfig']);
        Route::post('semester/class-config', [SemesterController::class, 'saveClassConfig']);
        Route::get('semester/show', [SemesterController::class, 'show']);
        Route::resource('semester', SemesterController::class)->except(['create', 'edit']);

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
Route::post('webhook/stripe', [WebhookController::class, 'stripe']);

// Amazon SES bounce, complaint and delivery notices, posted by SNS (signed;
// only the topics in services.ses_notifications.topic_arns are accepted).
Route::post('webhook/mail/ses', [MailWebhookController::class, 'ses'])->middleware('throttle:mail-webhook')->name('webhook.mail.ses');

// Linked from the public website footer; the school may not have written them yet.
Route::get('/privacy-policy', function () {
    $settings = getSettings('privacy_policy');
    echo ! empty($settings['privacy_policy']) ? $settings['privacy_policy'] : e(trans('page_not_published_yet'));
});

Route::get('/terms-conditions', function () {
    $settings = getSettings('terms_condition');
    echo ! empty($settings['terms_condition']) ? $settings['terms_condition'] : e(trans('page_not_published_yet'));
});

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
})->middleware(['auth', 'role:Super Admin']);

Route::group(['prefix' => 'common'], static function () {
    Route::get('/js/lang', [Controller::class, 'readLanguageFile'])->name('common.language.read');
});
