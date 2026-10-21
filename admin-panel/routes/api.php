<?php

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\Parent as ParentApi;
use App\Http\Controllers\Api\Student;
use App\Http\Controllers\Api\Teacher;
// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::post('logout', [ApiController::class, 'logout']);
});

/**
 * STUDENT APIs
 **/
Route::group(['prefix' => 'student'], function () {

    // Non Authenticated APIs
    Route::post('login', [Student\AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('forgot-password', [Student\AuthController::class, 'forgotPassword'])->middleware('throttle:login');

    // Authenticated APIs
    Route::group([
        'middleware' => [
            'auth:sanctum',
            'role:Student',
            'student.session.active',
        ],
    ], function () {
        Route::get('dashboard', [Student\DashboardController::class, 'dashboard']);
        Route::get('subjects', [Student\SubjectController::class, 'subjects']);
        Route::get('class-subjects', [Student\SubjectController::class, 'classSubjects']);
        Route::post('select-subjects', [Student\SubjectController::class, 'selectSubjects']);
        Route::get('parent-details', [Student\ProfileController::class, 'getParentDetails']);
        Route::get('timetable', [Student\TimetableController::class, 'getTimetable']);
        Route::get('lessons', [Student\LessonController::class, 'getLessons']);
        Route::get('lesson-topics', [Student\LessonController::class, 'getLessonTopics']);
        Route::get('assignments', [Student\AssignmentController::class, 'getAssignments']);
        Route::post('submit-assignment', [Student\AssignmentController::class, 'submitAssignment']);
        Route::post('delete-assignment-submission', [Student\AssignmentController::class, 'deleteAssignmentSubmission']);
        Route::get('attendance', [Student\AttendanceController::class, 'getAttendance']);
        Route::get('attendance-report', [Student\AttendanceController::class, 'attendanceReport']);
        Route::get('announcements', [Student\AnnouncementController::class, 'getAnnouncements']);
        Route::get('get-exam-list', [Student\ExamController::class, 'getExamList']); // Exam list Route
        Route::get('get-exam-details', [Student\ExamController::class, 'getExamDetails']); // Exam Details Route
        Route::get('exam-marks', [Student\ExamController::class, 'getExamMarks']); // Exam Details Route
        Route::get('offline-exam-report', [Student\ReportController::class, 'offlineExamReport']); // Offline Exam Report Statistics
        Route::get('online-exam-report', [Student\ReportController::class, 'onlineExamReport']); // Online Exam Report Statistics

        Route::get('id-card-pdf', [Student\ProfileController::class, 'getIdCardPdf']); // Student ID Card PDF Route

        // online exam routes
        Route::get('get-online-exam-list', [Student\OnlineExamController::class, 'getOnlineExamList']); // Get Online Exam List Route
        Route::get('get-online-exam-questions', [Student\OnlineExamController::class, 'getOnlineExamQuestions']); // Get Online Exam Questions Route
        Route::post('submit-online-exam-answers', [Student\OnlineExamController::class, 'submitOnlineExamAnswers']); // Submit Online Exam Answers Details Route
        Route::get('get-online-exam-result-list', [Student\OnlineExamController::class, 'getOnlineExamResultList']); // Online exam result list Route
        Route::get('get-online-exam-result', [Student\OnlineExamController::class, 'getOnlineExamResult']); // Online exam result  Route

        // profile data
        Route::get('get-profile-data', [Student\ProfileController::class, 'getProfileDetails']); // Get Profile Data
        Route::get('get-notification', [Student\NotificationController::class, 'getNotifications']); // Get Notification Data

        Route::get('get-user-list', [Student\ChatController::class, 'getChatUserList']);
        Route::post('send-message', [Student\ChatController::class, 'sendMessage']);
        Route::post('get-user-message', [Student\ChatController::class, 'getUserChatMessage']);
        Route::post('read-all-message', [Student\ChatController::class, 'readAllMessages']);

        // fees
        Route::get('fees-details', [Student\FeesController::class, 'getFeesDetails']); // Fees Details
        Route::post('add-fees-transaction', [Student\FeesController::class, 'storeFeesTransaction']); // Fees Details
        Route::post('store-fees', [Student\FeesController::class, 'storeFees']); // Store Fees
        Route::get('fees-paid-list', [Student\FeesController::class, 'feesPaidList']); // Fees Details
        Route::get('fees-paid-receipt-pdf', [Student\FeesController::class, 'feesPaidReceiptPDF']); // Fees Receipt
        Route::get('fees-transactions-list', [Student\FeesController::class, 'getFeesPaymentTransactions']); // Fees Payment Transaction Details
        Route::post('fail-payment-transaction', [Student\FeesController::class, 'failPaymentTransactionStatus']); // Make Payment Transaction Fail API
        Route::get('fees-report', [Student\ReportController::class, 'getFeesReport']); // Fees Report Statistics
        Route::get('leave-report', [Student\ReportController::class, 'leaveReport']); // Leave Report Statistics
        Route::get('assignment-report', [Student\ReportController::class, 'assignmentReport']); // Assignment Report Statistics

        // Academic calendar
        Route::get('academic-calendar-pdf', [Student\CalendarController::class, 'getAcademicCalendarPdf']);

        // fee notification
        Route::get('send-fee-notification', [Student\FeesController::class, 'sendFeeNotification']);

        Route::post('apply-leave', [Student\LeaveController::class, 'applyLeave']);
        Route::post('get-leave-list', [Student\LeaveController::class, 'getMyLeave']);
        Route::post('delete-leave', [Student\LeaveController::class, 'deleteLeave']);
        Route::post('get-payment-status', [ParentApi\FeesController::class, 'getPaymentStatus']); // Get Payment Status
    });
});

/**
 * PARENT APIs
 **/
Route::group(['prefix' => 'parent'], function () {
    // Non Authenticated APIs
    Route::post('login', [ParentApi\AuthController::class, 'login'])->middleware('throttle:login');
    // Authenticated APIs
    Route::group([
        'middleware' => [
            'auth:sanctum',
            'role:Parent',
            'parent.children.session.active',
        ],
    ], function () {

        // APIS Without Child ID
        Route::get('announcements', [ParentApi\AnnouncementController::class, 'getAnnouncements']); // Get Announcementes
        Route::get('fees-paid-receipt-pdf', [ParentApi\FeesController::class, 'feesPaidReceiptPDF']); // Fees Receipt
        Route::get('fees-transactions-list', [ParentApi\FeesController::class, 'getFeesPaymentTransactions']); // Fees Payment Transaction Details
        Route::get('get-profile-data', [ParentApi\ProfileController::class, 'getProfileDetails']); // Get Profile Data
        Route::post('fail-payment-transaction', [ParentApi\FeesController::class, 'failPaymentTransactionStatus']); // Make Payment Transaction Fail API
        Route::get('get-notification', [ParentApi\NotificationController::class, 'getNotifications']); // Get Notification Data
        Route::post('get-payment-status', [ParentApi\FeesController::class, 'getPaymentStatus']); // Get Payment Status
        Route::get('get-user-list', [ParentApi\ChatController::class, 'getChatUserList']);
        Route::post('send-message', [ParentApi\ChatController::class, 'sendMessage']);
        Route::post('get-user-message', [ParentApi\ChatController::class, 'getUserChatMessage']);
        Route::post('read-all-message', [ParentApi\ChatController::class, 'readAllMessages']);

        Route::group(['middleware' => ['auth:sanctum', \App\Http\Middleware\CheckChild::class]], function () {

            Route::get('subjects', [ParentApi\SubjectController::class, 'subjects']);
            Route::get('class-subjects', [ParentApi\SubjectController::class, 'classSubjects']);
            Route::get('timetable', [ParentApi\TimetableController::class, 'getTimetable']);
            Route::get('lessons', [ParentApi\LessonController::class, 'getLessons']);
            Route::get('lesson-topics', [ParentApi\LessonController::class, 'getLessonTopics']);
            Route::get('assignments', [ParentApi\AssignmentController::class, 'getAssignments']);
            Route::get('attendance', [ParentApi\AttendanceController::class, 'getAttendance']);
            Route::get('attendance-report', [ParentApi\AttendanceController::class, 'attendanceReport']);
            // Route::get('announcements', [ParentApi\AnnouncementController::class, 'getAnnouncements']);
            Route::get('teachers', [ParentApi\TeacherController::class, 'getTeachers']);
            Route::get('get-exam-list', [ParentApi\ExamController::class, 'getExamList']); // Exam list Route
            Route::get('get-exam-details', [ParentApi\ExamController::class, 'getExamDetails']); // Exam Details Route
            Route::get('exam-marks', [ParentApi\ExamController::class, 'getExamMarks']); // Exam Marks
            Route::get('offline-exam-report', [ParentApi\ReportController::class, 'offlineExamReport']); // Offline Exam Report Statistics
            Route::get('online-exam-report', [ParentApi\ReportController::class, 'onlineExamReport']); // Online Exam Report Statistics

            // fees
            Route::get('fees-details', [ParentApi\FeesController::class, 'getFeesDetails']); // Fees Details
            Route::post('add-fees-transaction', [ParentApi\FeesController::class, 'storeFeesTransaction']); // Fees Details
            Route::post('store-fees', [ParentApi\FeesController::class, 'storeFees']); // Store Fees
            Route::get('fees-paid-list', [ParentApi\FeesController::class, 'feesPaidList']); // Fees Details
            Route::get('fees-report', [ParentApi\ReportController::class, 'getFeesReport']); // Fees Report Statistics
            Route::get('leave-report', [ParentApi\ReportController::class, 'leaveReport']); // Leave Report Statistics
            Route::get('assignment-report', [ParentApi\ReportController::class, 'assignmentReport']); // Assignment Report Statistics

            // online exam routes
            Route::get('get-online-exam-list', [ParentApi\OnlineExamController::class, 'getOnlineExamList']); // Get Online Exam List Route
            Route::get('get-online-exam-result-list', [ParentApi\OnlineExamController::class, 'getOnlineExamResultList']); // Online exam result list Route
            Route::get('get-online-exam-result', [ParentApi\OnlineExamController::class, 'getOnlineExamResult']); // Online exam result  Route

            // Academic calendar
            Route::get('academic-calendar-pdf', [ParentApi\CalendarController::class, 'getAcademicCalendarPdf']);

            Route::get('id-card-pdf', [ParentApi\ProfileController::class, 'getIdCardPdf']); // Student ID Card PDF Route

            Route::post('apply-leave', [ParentApi\LeaveController::class, 'applyLeave']);
            Route::post('get-leave-list', [ParentApi\LeaveController::class, 'getMyLeave']);
        });

        // Checks that the leave belongs to one of the parent's children itself; the app sends no child_id.
        Route::post('delete-leave', [ParentApi\LeaveController::class, 'deleteLeave']);
    });
});

/**
 * TEACHER APIs
 **/
Route::group(['prefix' => 'teacher'], function () {
    // Non Authenticated APIs
    Route::post('login', [Teacher\AuthController::class, 'login'])->middleware('throttle:login');
    // Authenticated APIs
    Route::group(['middleware' => ['auth:sanctum', 'role:Teacher']], function () {
        Route::get('dashboard', [Teacher\DashboardController::class, 'dashboard']);
        Route::get('classes', [Teacher\ClassController::class, 'classes']);

        Route::get('subjects', [Teacher\ClassController::class, 'subjects']);

        // Assignment
        Route::get('get-assignment', [Teacher\AssignmentController::class, 'getAssignment']);
        Route::post('create-assignment', [Teacher\AssignmentController::class, 'createAssignment']);
        Route::post('update-assignment', [Teacher\AssignmentController::class, 'updateAssignment']);
        Route::post('delete-assignment', [Teacher\AssignmentController::class, 'deleteAssignment']);

        // Assignment Submission
        Route::get('get-assignment-submission', [Teacher\AssignmentController::class, 'getAssignmentSubmission']);
        Route::post('update-assignment-submission', [Teacher\AssignmentController::class, 'updateAssignmentSubmission']);

        // File
        Route::post('delete-file', [Teacher\LessonController::class, 'deleteFile']);
        Route::post('update-file', [Teacher\LessonController::class, 'updateFile']);

        // Lesson
        Route::get('get-lesson', [Teacher\LessonController::class, 'getLesson']);
        Route::post('create-lesson', [Teacher\LessonController::class, 'createLesson']);
        Route::post('update-lesson', [Teacher\LessonController::class, 'updateLesson']);
        Route::post('delete-lesson', [Teacher\LessonController::class, 'deleteLesson']);

        // Topic
        Route::get('get-topic', [Teacher\LessonController::class, 'getTopic']);
        Route::post('create-topic', [Teacher\LessonController::class, 'createTopic']);
        Route::post('update-topic', [Teacher\LessonController::class, 'updateTopic']);
        Route::post('delete-topic', [Teacher\LessonController::class, 'deleteTopic']);

        // Announcement
        Route::get('get-announcement', [Teacher\AnnouncementController::class, 'getAnnouncement']);
        Route::post('send-announcement', [Teacher\AnnouncementController::class, 'sendAnnouncement']);
        Route::post('update-announcement', [Teacher\AnnouncementController::class, 'updateAnnouncement']);
        Route::post('delete-announcement', [Teacher\AnnouncementController::class, 'deleteAnnouncement']);

        Route::get('get-attendance', [Teacher\AttendanceController::class, 'getAttendance']);
        Route::post('submit-attendance', [Teacher\AttendanceController::class, 'submitAttendance']);

        // Exam
        Route::get('get-exam-list', [Teacher\ExamController::class, 'getExamList']); // Exam list Route
        Route::get('get-exam-details', [Teacher\ExamController::class, 'getExamDetails']); // Exam Details Route
        Route::post('submit-exam-marks/subject', [Teacher\ExamController::class, 'submitExamMarksBySubjects']); // Submit Exam Marks By Subjects Route
        Route::post('submit-exam-marks/student', [Teacher\ExamController::class, 'submitExamMarksByStudent']); // Submit Exam Marks By Students Route

        Route::group(['middleware' => ['auth:sanctum', \App\Http\Middleware\CheckStudent::class]], function () {
            Route::get('get-student-result', [Teacher\ExamController::class, 'GetStudentExamResult']); // Student Exam Result
            Route::get('get-student-marks', [Teacher\ExamController::class, 'GetStudentExamMarks']); // Student Exam Marks
        });

        // Student List
        Route::get('student-list', [Teacher\ClassController::class, 'getStudentList']);
        Route::get('student-details', [Teacher\ClassController::class, 'getStudentDetails']);

        // Academic calendar
        Route::get('academic-calendar-pdf', [Teacher\CalendarController::class, 'getAcademicCalendarPdf']);

        // Schedule List
        Route::get('teacher_timetable', [Teacher\TimetableController::class, 'getTeacherTimetable']);

        // Profile Detials
        Route::get('get-profile-details', [Teacher\AuthController::class, 'getProfileDetails']);
        Route::get('get-notification', [Teacher\NotificationController::class, 'getNotifications']); // Get Notification Data

        Route::get('get-user-list', [Teacher\ChatController::class, 'getChatUserList']);
        Route::post('send-message', [Teacher\ChatController::class, 'sendMessage']);
        Route::post('get-user-message', [Teacher\ChatController::class, 'getUserChatMessage']);
        Route::post('read-all-message', [Teacher\ChatController::class, 'readAllMessages']);

        Route::get('get-student-result-pdf', [Teacher\ExamController::class, 'getStudentResultPdf']);

        Route::post('apply-leave', [Teacher\LeaveController::class, 'applyLeave']);
        Route::post('get-leave-list', [Teacher\LeaveController::class, 'getMyLeave']);
        Route::post('delete-leave', [Teacher\LeaveController::class, 'deleteLeave']);

        Route::post('get-student-leave-list', [Teacher\LeaveController::class, 'getStudentLeaveList']);
        Route::post('student-leave-status-update', [Teacher\LeaveController::class, 'studentLeaveStatusUpdate']);

        Route::post('update-timetable-link', [Teacher\TimetableController::class, 'updateTimetableLink']);

        Route::get('id-card-pdf', [Teacher\AuthController::class, 'getIdCardPdf']); // Teacher ID Card PDF Route

        Route::get('attendance-report', [Teacher\AttendanceController::class, 'getAttendanceReport']);
    });
});

/**
 * GENERAL APIs
 **/
Route::get('holidays', [ApiController::class, 'getHolidays']);
Route::get('sliders', [ApiController::class, 'getSliders']);
Route::get('current-session-year', [ApiController::class, 'getCurrentSessionYear']);
Route::get('settings', [ApiController::class, 'getSettings']);
Route::post('forgot-password', [ApiController::class, 'forgotPassword'])->middleware('throttle:login');
Route::get('get-events-list', [ApiController::class, 'getEvents']);
// Both apps ask with GET; POST is kept for any client that already uses it.
Route::match(['get', 'post'], 'get-events-details', [ApiController::class, 'getEventsDetails']);
Route::get('get-session-year', [ApiController::class, 'getSessionYear']);
Route::get('get-semesters', [ApiController::class, 'getSemesters']);
Route::get('get-gallery-albums', [ApiController::class, 'getGalleryAlbums']);
Route::get('get-gallery-images', [ApiController::class, 'getGalleryImages']);
Route::get('get-gallery-videos', [ApiController::class, 'getGalleryVideos']);
Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::post('change-password', [ApiController::class, 'changePassword']);
});
