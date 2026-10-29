import 'package:dio/dio.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool/app/routes.dart';
import 'package:eschool/cubits/authCubit.dart';
import 'package:eschool/utils/constants.dart';
import 'package:eschool_notifications/eschool_notifications.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/material.dart';
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

  /// The school picked in the school switcher (and, for a parent, the
  /// school of the child on show): every call says which with
  /// `X-School-Id`.
  static final SelectionStore selection = HiveSelectionStore();

  static final ApiClient _client = ApiClient(
    authToken: () => session.token,
    onUnauthorized: logoutUser,
    sessionIdentity: () => (
      session.generation,
      session.token,
      selection.schoolId,
      session.mode == AuthMode.cookie ? browserSessionIdentity() : null,
    ),
    createDio: () => withSchoolHeader(
      debugCreateDio?.call() ??
          createSessionDio(mode: session.mode, apiBaseUrl: baseUrl),
      () => selection.schoolId,
    ),
    sendsBearerToken: session.mode == AuthMode.bearer,
  );

  /// The client the shared packages call through (for example the
  /// `/me/context` repository), with the same token and 401 handling.
  static ApiClient get client => _client;

  static Map<String, dynamic> headers() => _client.headers();

  //Student app apis
  //
  static String studentLogin = '${databaseUrl}student/login';
  static String studentProfile = '${databaseUrl}student/get-profile-data';
  static String studentDashboard = '${databaseUrl}student/dashboard';

  //get subjects of given class
  static String classSubjects = '${databaseUrl}student/class-subjects';
  static String studentTimeTable = '${databaseUrl}student/timetable';
  static String studentExamList = '${databaseUrl}student/get-exam-list';

  static String studentExamDetails = '${databaseUrl}student/get-exam-details';
  static String selectStudentElectiveSubjects =
      '${databaseUrl}student/select-subjects';
  static String getLessonsOfSubject = '${databaseUrl}student/lessons';
  static String getstudyMaterialsOfTopic =
      '${databaseUrl}student/lesson-topics';
  static String getStudentAttendance = '${databaseUrl}student/attendance';
  static String getAssignments = '${databaseUrl}student/assignments';
  static String submitAssignment = '${databaseUrl}student/submit-assignment';
  static String generalAnnouncements = '${databaseUrl}student/announcements';
  static String parentDetailsOfStudent = '${databaseUrl}student/parent-details';
  static String deleteAssignment =
      '${databaseUrl}student/delete-assignment-submission';

  static String studentResults = '${databaseUrl}student/exam-marks';
  static String requestResetPassword = '${databaseUrl}student/forgot-password';

  static String studentExamOnlineList =
      '${databaseUrl}student/get-online-exam-list';
  static String studentExamOnlineQuestions =
      '${databaseUrl}student/get-online-exam-questions';
  static String studentSubmitOnlineExamAnswers =
      '${databaseUrl}student/submit-online-exam-answers';

  static String studentOnlineExamResultList =
      '${databaseUrl}student/get-online-exam-result-list';

  static String studentOnlineExamResult =
      '${databaseUrl}student/get-online-exam-result';

  static String studentApplyLeave = '${databaseUrl}student/apply-leave';
  static String studentGetLeaveList = '${databaseUrl}student/get-leave-list';
  static String studentDeleteLeave = '${databaseUrl}student/delete-leave';

  //
  //Apis that will be use in both student and parent app
  //
  static String getSliders = '${databaseUrl}sliders';
  static String logout = '${databaseUrl}logout';
  static String settings = '${databaseUrl}settings';
  static String holidays = '${databaseUrl}holidays';
  static String events = '${databaseUrl}get-events-list';
  static String eventDetails = '${databaseUrl}get-events-details';
  static String terms = '${databaseUrl}get-terms';
  static String sessionYears = '${databaseUrl}get-session-year';
  static String changePassword = '${databaseUrl}change-password';

  //
  //gallery related apis
  static String getGalleryAlbums = '${databaseUrl}get-gallery-albums';
  static String getGalleryImages = '${databaseUrl}get-gallery-images';
  static String getGalleryVideos = '${databaseUrl}get-gallery-videos';
  //
  //
  static String getStudentAcademicCalendarPDF =
      '${databaseUrl}student/academic-calendar-pdf';
  static String getParentAcademicCalendarPDF =
      '${databaseUrl}parent/academic-calendar-pdf';
  //

  //
  static String getStudentIdCardPDF = '${databaseUrl}student/id-card-pdf';
  static String getParentIdCardPDF = '${databaseUrl}parent/id-card-pdf';
  //

  //
  //Parent app apis
  //
  static String subjectsByChildId = '${databaseUrl}parent/subjects';
  static String parentLogin = '${databaseUrl}parent/login';
  static String parentPolicyAcceptance =
      '${databaseUrl}parent/policy-acceptance';
  static String parentProfile = '${databaseUrl}parent/get-profile-data';
  static String lessonsOfSubjectParent = '${databaseUrl}parent/lessons';
  static String getstudyMaterialsOfTopicParent =
      '${databaseUrl}parent/lesson-topics';
  static String getAssignmentsParent = '${databaseUrl}parent/assignments';
  static String getStudentAttendanceParent = '${databaseUrl}parent/attendance';
  static String getStudentTimetableParent = '${databaseUrl}parent/timetable';
  static String getStudentExamListParent = '${databaseUrl}parent/get-exam-list';
  static String getStudentResultsParent = '${databaseUrl}parent/exam-marks';
  static String getStudentExamDetailsParent =
      '${databaseUrl}parent/get-exam-details';

  static String generalAnnouncementsParent =
      '${databaseUrl}parent/announcements';

  static String getStudentTeachersParent = '${databaseUrl}parent/teachers';
  static String forgotPassword = '${databaseUrl}forgot-password';

  static String parentExamOnlineList =
      '${databaseUrl}parent/get-online-exam-list';
  static String parentOnlineExamResultList =
      '${databaseUrl}parent/get-online-exam-result-list';
  static String parentOnlineExamResult =
      '${databaseUrl}parent/get-online-exam-result';

  static String getFeesTransactions =
      '${databaseUrl}parent/fees-transactions-list';
  static String verifyStripePayment = '${databaseUrl}parent/get-payment-status';

  static String getStudentFeesDetailParent =
      '${databaseUrl}parent/fees-details';
  static String addFeesTransaction =
      '${databaseUrl}parent/add-fees-transaction';
  static String failPaymentTransaction =
      '${databaseUrl}parent/fail-payment-transaction';
  static String storeFeesParent = '${databaseUrl}parent/store-fees';

  static String getPaidFeesListParent = '${databaseUrl}parent/fees-paid-list';
  static String downloadFeesPaidReceiptParent =
      '${databaseUrl}parent/fees-paid-receipt-pdf';

  static String getStudentFeesDetailStudent =
      '${databaseUrl}student/fees-details';
  static String addFeesTransactionStudent =
      '${databaseUrl}student/add-fees-transaction';
  static String failPaymentTransactionStudent =
      '${databaseUrl}student/fail-payment-transaction';
  static String storeFeesStudent = '${databaseUrl}student/store-fees';
  static String downloadFeesPaidReceiptStudent =
      '${databaseUrl}student/fees-paid-receipt-pdf';
  static String getFeesTransactionsStudent =
      '${databaseUrl}student/fees-transactions-list';
  static String verifyStripePaymentStudent =
      '${databaseUrl}student/get-payment-status';
  static String askParentsToPayFees =
      '${databaseUrl}student/send-fee-notification';

  static String getStudentNotifications =
      '${databaseUrl}student/get-notification';
  static String getParentNotifications =
      '${databaseUrl}parent/get-notification';
  static String parentAddLeave = '${databaseUrl}parent/apply-leave';
  static String parentGetLeaves = '${databaseUrl}parent/get-leave-list';
  static String parentDeleteLeave = '${databaseUrl}parent/delete-leave';

  //reports
  static String studentAttendanceReport =
      '${databaseUrl}student/attendance-report';
  static String parentAttendanceReport =
      '${databaseUrl}parent/attendance-report';
  static String studentAssignmentReport =
      '${databaseUrl}student/assignment-report';
  static String parentAssignmentReport =
      '${databaseUrl}parent/assignment-report';
  static String studentLeaveReport = '${databaseUrl}student/leave-report';
  static String parentLeaveReport = '${databaseUrl}parent/leave-report';
  static String studentFeesReport = '${databaseUrl}student/fees-report';
  static String parentFeesReport = '${databaseUrl}parent/fees-report';
  static String studentOfflineExamReport =
      '${databaseUrl}student/offline-exam-report';
  static String parentOfflineExamReport =
      '${databaseUrl}parent/offline-exam-report';
  static String studentOnlineExamReport =
      '${databaseUrl}student/online-exam-report';
  static String parentOnlineExamReport =
      '${databaseUrl}parent/online-exam-report';

  //chat related APIs
  //student
  static String getChatUsersStudent = '${databaseUrl}student/get-user-list';
  static String getChatMessagesStudent =
      '${databaseUrl}student/get-user-message';
  static String sendChatMessageStudent = '${databaseUrl}student/send-message';
  static String readAllMessagesStudent =
      '${databaseUrl}student/read-all-message';
  //parent
  static String getChatUsersParent = '${databaseUrl}parent/get-user-list';
  static String getChatMessagesParent = '${databaseUrl}parent/get-user-message';
  static String sendChatMessageParent = '${databaseUrl}parent/send-message';
  static String readAllMessagesParent = '${databaseUrl}parent/read-all-message';

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
    Navigator.of(context!)
        .pushNamedAndRemoveUntil(Routes.auth, (_) => false)
        .then((_) {
          isLoggingOut = false;
        });
  }
}
