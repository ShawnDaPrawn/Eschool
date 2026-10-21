import 'dart:io';
import 'package:dio/dio.dart';
import 'package:eschool_teacher/app/routes.dart';
import 'package:eschool_teacher/cubits/authCubit.dart';
import 'package:eschool_teacher/utils/constants.dart';
import 'package:eschool_teacher/utils/errorMessageKeysAndCodes.dart';
import 'package:eschool_teacher/utils/hiveBoxKeys.dart';
import 'package:eschool_teacher/utils/notificationUtils/generalNotificationUtility.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/cupertino.dart';
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

  //
  //Teacher app apis
  //
  static String login = '${databaseUrl}teacher/login';
  static String profile = '${databaseUrl}teacher/get-profile-details';
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
      //
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
    ).pushNamedAndRemoveUntil(Routes.login, (_) => false).then((_) {
      isLoggingOut = false;
    });
  }
}
