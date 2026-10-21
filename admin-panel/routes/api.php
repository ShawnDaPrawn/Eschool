<?php

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\ParentApiController;
use App\Http\Controllers\Api\StudentApiController;
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
    Route::post('login', [StudentApiController::class, 'login'])->middleware('throttle:login');
    Route::post('forgot-password', [StudentApiController::class, 'forgotPassword'])->middleware('throttle:login');

    // Authenticated APIs
    Route::group([
        'middleware' => [
            'auth:sanctum',
            'role:Student',
            'student.session.active',
        ],
    ], function () {
        Route::get('dashboard', [StudentApiController::class, 'dashboard']);
        Route::get('subjects', [StudentApiController::class, 'subjects']);
        Route::get('class-subjects', [StudentApiController::class, 'classSubjects']);
        Route::post('select-subjects', [StudentApiController::class, 'selectSubjects']);
        Route::get('parent-details', [StudentApiController::class, 'getParentDetails']);
        Route::get('timetable', [StudentApiController::class, 'getTimetable']);
        Route::get('lessons', [StudentApiController::class, 'getLessons']);
        Route::get('lesson-topics', [StudentApiController::class, 'getLessonTopics']);
        Route::get('assignments', [StudentApiController::class, 'getAssignments']);
        Route::post('submit-assignment', [StudentApiController::class, 'submitAssignment']);
        Route::post('delete-assignment-submission', [StudentApiController::class, 'deleteAssignmentSubmission']);
        Route::get('attendance', [StudentApiController::class, 'getAttendance']);
        Route::get('attendance-report', [StudentApiController::class, 'attendanceReport']);
        Route::get('announcements', [StudentApiController::class, 'getAnnouncements']);
        Route::get('get-exam-list', [StudentApiController::class, 'getExamList']); // Exam list Route
        Route::get('get-exam-details', [StudentApiController::class, 'getExamDetails']); // Exam Details Route
        Route::get('exam-marks', [StudentApiController::class, 'getExamMarks']); // Exam Details Route
        Route::get('offline-exam-report', [StudentApiController::class, 'offlineExamReport']); // Offline Exam Report Statistics
        Route::get('online-exam-report', [StudentApiController::class, 'onlineExamReport']); // Online Exam Report Statistics

        Route::get('id-card-pdf', [StudentApiController::class, 'getIdCardPdf']); // Student ID Card PDF Route

        // online exam routes
        Route::get('get-online-exam-list', [StudentApiController::class, 'getOnlineExamList']); // Get Online Exam List Route
        Route::get('get-online-exam-questions', [StudentApiController::class, 'getOnlineExamQuestions']); // Get Online Exam Questions Route
        Route::post('submit-online-exam-answers', [StudentApiController::class, 'submitOnlineExamAnswers']); // Submit Online Exam Answers Details Route
        Route::get('get-online-exam-result-list', [StudentApiController::class, 'getOnlineExamResultList']); // Online exam result list Route
        Route::get('get-online-exam-result', [StudentApiController::class, 'getOnlineExamResult']); // Online exam result  Route

        // profile data
        Route::get('get-profile-data', [StudentApiController::class, 'getProfileDetails']); // Get Profile Data
        Route::get('get-notification', [StudentApiController::class, 'getNotifications']); // Get Notification Data

        Route::get('get-user-list', [StudentApiController::class, 'getChatUserList']);
        Route::post('send-message', [StudentApiController::class, 'sendMessage']);
        Route::post('get-user-message', [StudentApiController::class, 'getUserChatMessage']);
        Route::post('read-all-message', [StudentApiController::class, 'readAllMessages']);

        // fees
        Route::get('fees-details', [StudentApiController::class, 'getFeesDetails']); // Fees Details
        Route::post('add-fees-transaction', [StudentApiController::class, 'storeFeesTransaction']); // Fees Details
        Route::post('store-fees', [StudentApiController::class, 'storeFees']); // Store Fees
        Route::get('fees-paid-list', [StudentApiController::class, 'feesPaidList']); // Fees Details
        Route::get('fees-paid-receipt-pdf', [StudentApiController::class, 'feesPaidReceiptPDF']); // Fees Receipt
        Route::get('fees-transactions-list', [StudentApiController::class, 'getFeesPaymentTransactions']); // Fees Payment Transaction Details
        Route::post('fail-payment-transaction', [StudentApiController::class, 'failPaymentTransactionStatus']); // Make Payment Transaction Fail API
        Route::get('fees-report', [StudentApiController::class, 'getFeesReport']); // Fees Report Statistics
        Route::get('leave-report', [StudentApiController::class, 'leaveReport']); // Leave Report Statistics
        Route::get('assignment-report', [StudentApiController::class, 'assignmentReport']); // Assignment Report Statistics

        // Academic calendar
        Route::get('academic-calendar-pdf', [StudentApiController::class, 'getAcademicCalendarPdf']);

        // fee notification
        Route::get('send-fee-notification', [StudentApiController::class, 'sendFeeNotification']);

        Route::post('apply-leave', [StudentApiController::class, 'applyLeave']);
        Route::post('get-leave-list', [StudentApiController::class, 'getMyLeave']);
        Route::post('delete-leave', [StudentApiController::class, 'deleteLeave']);
        Route::post('get-payment-status', [ParentApiController::class, 'getPaymentStatus']); // Get Payment Status
    });
});

/**
 * PARENT APIs
 **/
Route::group(['prefix' => 'parent'], function () {
    // Non Authenticated APIs
    Route::post('login', [ParentApiController::class, 'login'])->middleware('throttle:login');
    // Authenticated APIs
    Route::group([
        'middleware' => [
            'auth:sanctum',
            'role:Parent',
            'parent.children.session.active',
        ],
    ], function () {

        // APIS Without Child ID
        Route::get('announcements', [ParentApiController::class, 'getAnnouncements']); // Get Announcementes
        Route::get('fees-paid-receipt-pdf', [ParentApiController::class, 'feesPaidReceiptPDF']); // Fees Receipt
        Route::get('fees-transactions-list', [ParentApiController::class, 'getFeesPaymentTransactions']); // Fees Payment Transaction Details
        Route::get('get-profile-data', [ParentApiController::class, 'getProfileDetails']); // Get Profile Data
        Route::post('fail-payment-transaction', [ParentApiController::class, 'failPaymentTransactionStatus']); // Make Payment Transaction Fail API
        Route::get('get-notification', [ParentApiController::class, 'getNotifications']); // Get Notification Data
        Route::post('get-payment-status', [ParentApiController::class, 'getPaymentStatus']); // Get Payment Status
        Route::get('get-user-list', [ParentApiController::class, 'getChatUserList']);
        Route::post('send-message', [ParentApiController::class, 'sendMessage']);
        Route::post('get-user-message', [ParentApiController::class, 'getUserChatMessage']);
        Route::post('read-all-message', [ParentApiController::class, 'readAllMessages']);

        Route::group(['middleware' => ['auth:sanctum', \App\Http\Middleware\CheckChild::class]], function () {

            Route::get('subjects', [ParentApiController::class, 'subjects']);
            Route::get('class-subjects', [ParentApiController::class, 'classSubjects']);
            Route::get('timetable', [ParentApiController::class, 'getTimetable']);
            Route::get('lessons', [ParentApiController::class, 'getLessons']);
            Route::get('lesson-topics', [ParentApiController::class, 'getLessonTopics']);
            Route::get('assignments', [ParentApiController::class, 'getAssignments']);
            Route::get('attendance', [ParentApiController::class, 'getAttendance']);
            Route::get('attendance-report', [ParentApiController::class, 'attendanceReport']);
            // Route::get('announcements', [ParentApiController::class, 'getAnnouncements']);
            Route::get('teachers', [ParentApiController::class, 'getTeachers']);
            Route::get('get-exam-list', [ParentApiController::class, 'getExamList']); // Exam list Route
            Route::get('get-exam-details', [ParentApiController::class, 'getExamDetails']); // Exam Details Route
            Route::get('exam-marks', [ParentApiController::class, 'getExamMarks']); // Exam Marks
            Route::get('offline-exam-report', [ParentApiController::class, 'offlineExamReport']); // Offline Exam Report Statistics
            Route::get('online-exam-report', [ParentApiController::class, 'onlineExamReport']); // Online Exam Report Statistics

            // fees
            Route::get('fees-details', [ParentApiController::class, 'getFeesDetails']); // Fees Details
            Route::post('add-fees-transaction', [ParentApiController::class, 'storeFeesTransaction']); // Fees Details
            Route::post('store-fees', [ParentApiController::class, 'storeFees']); // Store Fees
            Route::get('fees-paid-list', [ParentApiController::class, 'feesPaidList']); // Fees Details
            Route::get('fees-report', [ParentApiController::class, 'getFeesReport']); // Fees Report Statistics
            Route::get('leave-report', [ParentApiController::class, 'leaveReport']); // Leave Report Statistics
            Route::get('assignment-report', [ParentApiController::class, 'assignmentReport']); // Assignment Report Statistics

            // online exam routes
            Route::get('get-online-exam-list', [ParentApiController::class, 'getOnlineExamList']); // Get Online Exam List Route
            Route::get('get-online-exam-result-list', [ParentApiController::class, 'getOnlineExamResultList']); // Online exam result list Route
            Route::get('get-online-exam-result', [ParentApiController::class, 'getOnlineExamResult']); // Online exam result  Route

            // Academic calendar
            Route::get('academic-calendar-pdf', [ParentApiController::class, 'getAcademicCalendarPdf']);

            Route::get('id-card-pdf', [ParentApiController::class, 'getIdCardPdf']); // Student ID Card PDF Route

            Route::post('apply-leave', [ParentApiController::class, 'applyLeave']);
            Route::post('get-leave-list', [ParentApiController::class, 'getMyLeave']);
        });

        // Checks that the leave belongs to one of the parent's children itself; the app sends no child_id.
        Route::post('delete-leave', [ParentApiController::class, 'deleteLeave']);
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
Route::post('get-events-details', [ApiController::class, 'getEventsDetails']);
Route::get('get-session-year', [ApiController::class, 'getSessionYear']);
Route::get('get-semesters', [ApiController::class, 'getSemesters']);
Route::get('get-gallery-albums', [ApiController::class, 'getGalleryAlbums']);
Route::get('get-gallery-images', [ApiController::class, 'getGalleryImages']);
Route::get('get-gallery-videos', [ApiController::class, 'getGalleryVideos']);
Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::post('change-password', [ApiController::class, 'changePassword']);
});
