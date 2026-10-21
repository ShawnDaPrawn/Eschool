import 'dart:io';

import 'package:dio/dio.dart';
import 'package:eschool/app/routes.dart';
import 'package:eschool/cubits/authCubit.dart';
import 'package:eschool/utils/constants.dart';
import 'package:eschool/utils/errorMessageKeysAndCodes.dart';
import 'package:eschool/utils/hiveBoxKeys.dart';
import 'package:eschool/utils/notificationUtils/generalNotificationUtility.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:hive_flutter/hive_flutter.dart';

class ApiException implements Exception {
  ApiException(this.errorMessage, {this.isApiMessage = false});
  String errorMessage;
  bool
  isApiMessage; //if it's a message from api and not a code to be translated

  @override
  String toString() {
    return errorMessage;
  }

  factory ApiException.fromException(Object exception) {
    if (exception is ApiException) {
      return exception;
    } else {
      return ApiException(exception.toString());
    }
  }
}

// ignore: avoid_classes_with_only_static_members
class Api {
  static Map<String, dynamic> headers() {
    final String jwtToken = Hive.box(authBoxKey).get(jwtTokenKey) ?? '';

    return {
      'Authorization': 'Bearer $jwtToken',
      // Without it the server may answer an expired token with a redirect instead of 401.
      'Accept': 'application/json',
    };
  }

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
  static String semesters = '${databaseUrl}get-semesters';
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
  static String studentAttendanceReport = '${databaseUrl}student/attendance-report';
  static String parentAttendanceReport = '${databaseUrl}parent/attendance-report';
  static String studentAssignmentReport = '${databaseUrl}student/assignment-report';
  static String parentAssignmentReport = '${databaseUrl}parent/assignment-report';
  static String studentLeaveReport = '${databaseUrl}student/leave-report';
  static String parentLeaveReport = '${databaseUrl}parent/leave-report';
  static String studentFeesReport = '${databaseUrl}student/fees-report';
  static String parentFeesReport = '${databaseUrl}parent/fees-report';
  static String studentOfflineExamReport = '${databaseUrl}student/offline-exam-report';
  static String parentOfflineExamReport = '${databaseUrl}parent/offline-exam-report';
  static String studentOnlineExamReport = '${databaseUrl}student/online-exam-report';
  static String parentOnlineExamReport = '${databaseUrl}parent/online-exam-report';

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
  }) async {
    try {
      final Dio dio = Dio();
      final FormData formData = FormData.fromMap(
        body,
        ListFormat.multiCompatible,
      );

      debugPrint('API Called POST: $url');

      final response = await dio.post(
        url,
        data: formData,
        queryParameters: queryParameters,
        cancelToken: cancelToken,
        onReceiveProgress: onReceiveProgress,
        onSendProgress: onSendProgress,
        options: useAuthToken ? Options(headers: headers()) : null,
      );

      if (response.data['error']) {
        if (response.data['code'] == null && response.data['message'] != null) {
          throw ApiException(
            response.data['message'].toString(),
            isApiMessage: true,
          );
        } else {
          throw ApiException(response.data['code'].toString());
        }
      }
      return Map.from(response.data);
    } on DioException catch (e) {
      if (e.response?.statusCode == 401) {
        logoutUser();
        throw ApiException(ErrorMessageKeysAndCode.unauthorizedAccessCode);
      }
      if (e.response?.statusCode == 503 || e.response?.statusCode == 500) {
        throw ApiException(ErrorMessageKeysAndCode.internetServerErrorCode);
      }
      throw ApiException(
        e.error is SocketException
            ? ErrorMessageKeysAndCode.noInternetCode
            : ErrorMessageKeysAndCode.defaultErrorMessageCode,
      );
    } on ApiException {
      rethrow;
    } catch (e) {
      throw ApiException(ErrorMessageKeysAndCode.defaultErrorMessageKey);
    }
  }

  static Future<Map<String, dynamic>> get({
    required String url,
    required bool useAuthToken,
    Map<String, dynamic>? queryParameters,
  }) async {
    try {
      final Dio dio = Dio();

      debugPrint('API Called GET: $url');

      final response = await dio.get(
        url,
        queryParameters: queryParameters,
        options: useAuthToken ? Options(headers: headers()) : null,
      );

      if (response.data['error']) {
        if (response.data['code'] == null && response.data['message'] != null) {
          throw ApiException(
            response.data['message'].toString(),
            isApiMessage: true,
          );
        } else {
          throw ApiException(response.data['code'].toString());
        }
      }

      return Map.from(response.data);
    } on DioException catch (e) {
      if (e.response?.statusCode == 401) {
        logoutUser();
        throw ApiException(ErrorMessageKeysAndCode.unauthorizedAccessCode);
      }
      if (e.response?.statusCode == 503 || e.response?.statusCode == 500) {
        throw ApiException(ErrorMessageKeysAndCode.internetServerErrorCode);
      }
      throw ApiException(
        e.error is SocketException
            ? ErrorMessageKeysAndCode.noInternetCode
            : ErrorMessageKeysAndCode.defaultErrorMessageCode,
      );
    } on ApiException {
      rethrow;
    } catch (e) {
      throw ApiException(ErrorMessageKeysAndCode.defaultErrorMessageKey);
    }
  }

  static Future<void> download({
    required String url,
    required CancelToken cancelToken,
    required String savePath,
    required Function updateDownloadedPercentage,
  }) async {
    try {
      final Dio dio = Dio();
      await dio.download(
        url,
        savePath,
        cancelToken: cancelToken,
        onReceiveProgress: (count, total) {
          final double percentage = (count / total) * 100;
          updateDownloadedPercentage(percentage < 0.0 ? 99.0 : percentage);
        },
      );
    } on DioException catch (e) {
      if (e.response?.statusCode == 503 || e.response?.statusCode == 500) {
        throw ApiException(ErrorMessageKeysAndCode.internetServerErrorCode);
      }
      if (e.response?.statusCode == 404) {
        throw ApiException(ErrorMessageKeysAndCode.fileNotFoundErrorCode);
      }
      throw ApiException(
        e.error is SocketException
            ? ErrorMessageKeysAndCode.noInternetCode
            : ErrorMessageKeysAndCode.defaultErrorMessageCode,
      );
    } on ApiException catch (e) {
      throw ApiException(e.errorMessage);
    } catch (e) {
      throw ApiException(ErrorMessageKeysAndCode.defaultErrorMessageKey);
    }
  }

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
    ).pushNamedAndRemoveUntil(Routes.auth, (_) => false).then((_) {
      isLoggingOut = false;
    });
  }
}
