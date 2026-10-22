import 'package:dio/dio.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool_teacher/app/routes.dart';
import 'package:eschool_teacher/cubits/authCubit.dart';
import 'package:eschool_teacher/utils/constants.dart';
import 'package:eschool_notifications/eschool_notifications.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

export 'package:eschool_core/eschool_core.dart' show ApiException;

// ignore: avoid_classes_with_only_static_members
class Api {
  /// Who is signed in on this device (Hive's authBox, as always). A web
  /// build keeps no token: the portal's session cookie identifies the user.
  static final SessionRepository session = HiveSessionRepository();

  /// Integration tests answer every call from fixtures by setting this
  /// before the app starts.
  @visibleForTesting
  static Dio Function()? debugCreateDio;

  static final ApiClient _client = ApiClient(
    authToken: () => session.token,
    onUnauthorized: logoutUser,
    createDio: () =>
        debugCreateDio?.call() ??
        createSessionDio(mode: session.mode, apiBaseUrl: baseUrl),
    sendsBearerToken: session.mode == AuthMode.bearer,
  );

  /// The client the shared packages call through (for example the
  /// `/me/context` repository), with the same token and 401 handling.
  static ApiClient get client => _client;

  static Map<String, dynamic> headers() => _client.headers();

  //
  //Teacher app apis
  //
  static String login = '${databaseUrl}teacher/login';
  static String profile = '${databaseUrl}teacher/get-profile-details';
  static String policyAcceptance = '${databaseUrl}teacher/policy-acceptance';
  static String forgotPassword = '${databaseUrl}forgot-password';
  static String logout = '${databaseUrl}logout';
  static String changePassword = '${databaseUrl}change-password';

  static String getAcademicCalendarPDF =
      '${databaseUrl}teacher/academic-calendar-pdf';

  static String dashboard = '${databaseUrl}teacher/dashboard';
  static String getSubjectByClassSection = '${databaseUrl}teacher/subjects';

  static String getAssignment = '${databaseUrl}teacher/get-assignment';
  static String uploadAssignment = '${databaseUrl}teacher/update-assignment';
  static String deleteAssignment = '${databaseUrl}teacher/delete-assignment';
  static String createAssignment = '${databaseUrl}teacher/create-assignment';
  static String createLesson = '${databaseUrl}teacher/create-lesson';
  static String getLessons = '${databaseUrl}teacher/get-lesson';
  static String deleteLesson = '${databaseUrl}teacher/delete-lesson';
  static String updateLesson = '${databaseUrl}teacher/update-lesson';

  static String getTopics = '${databaseUrl}teacher/get-topic';
  static String deleteStudyMaterial = '${databaseUrl}teacher/delete-file';
  static String deleteTopic = '${databaseUrl}teacher/delete-topic';
  static String updateStudyMaterial = '${databaseUrl}teacher/update-file';
  static String createTopic = '${databaseUrl}teacher/create-topic';
  static String updateTopic = '${databaseUrl}teacher/update-topic';
  static String getAnnouncement = '${databaseUrl}teacher/get-announcement';
  static String createAnnouncement = '${databaseUrl}teacher/send-announcement';
  static String deleteAnnouncement =
      '${databaseUrl}teacher/delete-announcement';
  static String updateAnnouncement =
      '${databaseUrl}teacher/update-announcement';
  static String getStudentsByClassSection =
      '${databaseUrl}teacher/student-list';

  static String getStudentsMoreDetails =
      '${databaseUrl}teacher/student-details';

  static String getAttendance = '${databaseUrl}teacher/get-attendance';
  static String submitAttendance = '${databaseUrl}teacher/submit-attendance';
  static String getTeacherAttendanceReport =
      '${databaseUrl}teacher/attendance-report';
  static String timeTable = '${databaseUrl}teacher/teacher_timetable';
  static String examList = '${databaseUrl}teacher/get-exam-list';
  static String examTimeTable = '${databaseUrl}teacher/get-exam-details';
  static String examResults = '${databaseUrl}teacher/exam-marks';
  static String downloadExamResultPdf =
      '${databaseUrl}teacher/get-student-result-pdf';
  static String submitExamMarksBySubjectId =
      '${databaseUrl}teacher/submit-exam-marks/subject';
  static String submitExamMarksByStudentId =
      '${databaseUrl}teacher/submit-exam-marks/student';
  static String getStudentResultList =
      '${databaseUrl}teacher/get-student-result';

  static String getReviewAssignment =
      '${databaseUrl}teacher/get-assignment-submission';

  static String updateReviewAssignment =
      '${databaseUrl}teacher/update-assignment-submission';

  static String settings = '${databaseUrl}settings';
  static String holidays = '${databaseUrl}holidays';
  static String events = '${databaseUrl}get-events-list';
  static String eventDetails = '${databaseUrl}get-events-details';
  static String sessionYears = '${databaseUrl}get-session-year';

  static String getNotifications = '${databaseUrl}teacher/get-notification';

  //chat related APIs
  static String getChatUsers = '${databaseUrl}teacher/get-user-list';
  static String getChatMessages = '${databaseUrl}teacher/get-user-message';
  static String sendChatMessage = '${databaseUrl}teacher/send-message';
  static String readAllMessages = '${databaseUrl}teacher/read-all-message';

  //leave related APIs
  static String addLeaveRequest = '${databaseUrl}teacher/apply-leave';
  static String getLeaves = '${databaseUrl}teacher/get-leave-list';
  static String deleteLeave = '${databaseUrl}teacher/delete-leave';

  //Student leave related APIs
  static String getStudentLeaveList =
      '${databaseUrl}teacher/get-student-leave-list';
  static String updateStudentLeaveStatus =
      '${databaseUrl}teacher/student-leave-status-update';

  //timetable link add/edit
  static String updateTimetableLink =
      '${databaseUrl}teacher/update-timetable-link';

  //
  //gallery related apis
  static String getGalleryAlbums = '${databaseUrl}get-gallery-albums';
  static String getGalleryImages = '${databaseUrl}get-gallery-images';
  static String getGalleryVideos = '${databaseUrl}get-gallery-videos';
  //

  static String getIdCardPDF = '${databaseUrl}teacher/id-card-pdf';

  //Api methods
  static Future<Map<String, dynamic>> post({
    required Map<String, dynamic> body,
    required String url,
    required bool useAuthToken,
    Map<String, dynamic>? queryParameters,
    CancelToken? cancelToken,
    Function(int, int)? onSendProgress,
    Function(int, int)? onReceiveProgress,
  }) => _client.post(
    body: body,
    url: url,
    useAuthToken: useAuthToken,
    queryParameters: queryParameters,
    cancelToken: cancelToken,
    onSendProgress: onSendProgress,
    onReceiveProgress: onReceiveProgress,
  );

  static Future<Map<String, dynamic>> get({
    required String url,
    required bool useAuthToken,
    Map<String, dynamic>? queryParameters,
  }) => _client.get(
    url: url,
    useAuthToken: useAuthToken,
    queryParameters: queryParameters,
  );

  static Future<void> download({
    required String url,
    required CancelToken cancelToken,
    required String savePath,
    required Function updateDownloadedPercentage,
  }) => _client.download(
    url: url,
    cancelToken: cancelToken,
    savePath: savePath,
    updateDownloadedPercentage: updateDownloadedPercentage,
  );

  /// [download] into memory, for a web build (which cannot write files).
  static Future<Uint8List> downloadBytes({
    required String url,
    required CancelToken cancelToken,
    required Function updateDownloadedPercentage,
  }) => _client.downloadBytes(
    url: url,
    cancelToken: cancelToken,
    updateDownloadedPercentage: updateDownloadedPercentage,
  );

  //logout the user on 401 error
  static bool isLoggingOut = false;
  static void logoutUser() {
    if (isLoggingOut) return;
    isLoggingOut = true;
    final context = UiUtils.rootNavigatorKey.currentContext;
    NotificationUtility.removeListener();
    context?.read<AuthCubit>().signOut();
    Navigator.of(
      context!,
    ).pushNamedAndRemoveUntil(Routes.login, (_) => false).then((_) {
      isLoggingOut = false;
    });
  }
}
