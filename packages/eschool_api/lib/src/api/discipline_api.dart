//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

import 'dart:async';

// ignore: unused_import
import 'dart:convert';
import 'package:eschool_api/src/deserialize.dart';
import 'package:dio/dio.dart';

import 'package:eschool_api/src/model/discipline_acknowledge_write.dart';
import 'package:eschool_api/src/model/discipline_acknowledgement_response.dart';
import 'package:eschool_api/src/model/discipline_configuration_response.dart';
import 'package:eschool_api/src/model/discipline_detention_attendance_response.dart';
import 'package:eschool_api/src/model/discipline_detention_attendance_write.dart';
import 'package:eschool_api/src/model/discipline_detention_cancel_write.dart';
import 'package:eschool_api/src/model/discipline_detention_detail_response.dart';
import 'package:eschool_api/src/model/discipline_detention_enroll_write.dart';
import 'package:eschool_api/src/model/discipline_detention_enrollment_response.dart';
import 'package:eschool_api/src/model/discipline_detention_response.dart';
import 'package:eschool_api/src/model/discipline_detention_write.dart';
import 'package:eschool_api/src/model/discipline_detentions_response.dart';
import 'package:eschool_api/src/model/discipline_family_history_response.dart';
import 'package:eschool_api/src/model/discipline_framework_response.dart';
import 'package:eschool_api/src/model/discipline_framework_view_response.dart';
import 'package:eschool_api/src/model/discipline_framework_write.dart';
import 'package:eschool_api/src/model/discipline_hearing_write.dart';
import 'package:eschool_api/src/model/discipline_learner_choices_response.dart';
import 'package:eschool_api/src/model/discipline_record_detail_response.dart';
import 'package:eschool_api/src/model/discipline_record_history_response.dart';
import 'package:eschool_api/src/model/discipline_record_metadata_response.dart';
import 'package:eschool_api/src/model/discipline_record_queue_response.dart';
import 'package:eschool_api/src/model/discipline_record_write.dart';
import 'package:eschool_api/src/model/discipline_review_write.dart';
import 'package:eschool_api/src/model/discipline_supervisors_response.dart';
import 'package:eschool_api/src/model/error_response.dart';

class DisciplineApi {
  final Dio _dio;

  const DisciplineApi(this._dio);

  /// acknowledgeDisciplineRecord
  /// School-configured discipline; no default sanctions or automatic expulsion. Fresh school/role and dated allocation or grade leadership authorize staff records. Families see published owned history only; only verified guardians acknowledge. Hearing decisions require an appointed principal. Private body-only UUID writes use encrypted feature receipts and reauthorize before replay. Detention supervision is a separate explicit session grant; unknown attendance remains null.
  ///
  /// Parameters:
  /// * [record] - record
  /// * [disciplineAcknowledgeWrite]
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [DisciplineAcknowledgementResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<DisciplineAcknowledgementResponse>>
  acknowledgeDisciplineRecord({
    required int record,
    required DisciplineAcknowledgeWrite disciplineAcknowledgeWrite,
    int? xAccountId,
    int? xSchoolId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/discipline/family/records/{record}/acknowledge'.replaceAll(
      '{'
      r'record'
      '}',
      record.toString(),
    );
    final _options = Options(
      method: r'POST',
      headers: <String, dynamic>{
        if (xAccountId != null) r'X-Account-Id': xAccountId,
        if (xSchoolId != null) r'X-School-Id': xSchoolId,
        ...?headers,
      },
      extra: <String, dynamic>{
        'secure': <Map<String, String>>[
          {
            'type': 'apiKey',
            'name': 'cookieAuth',
            'keyName': 'e_school_session',
            'where': '',
          },
          {'type': 'http', 'scheme': 'bearer', 'name': 'bearerAuth'},
        ],
        ...?extra,
      },
      contentType: 'application/json',
      validateStatus: validateStatus,
    );

    dynamic _bodyData;

    try {
      _bodyData = jsonEncode(disciplineAcknowledgeWrite);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _options.compose(_dio.options, _path),
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    final _response = await _dio.request<Object>(
      _path,
      data: _bodyData,
      options: _options,
      cancelToken: cancelToken,
      onSendProgress: onSendProgress,
      onReceiveProgress: onReceiveProgress,
    );

    DisciplineAcknowledgementResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              DisciplineAcknowledgementResponse,
              DisciplineAcknowledgementResponse
            >(rawData, 'DisciplineAcknowledgementResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<DisciplineAcknowledgementResponse>(
      data: _responseData,
      headers: _response.headers,
      isRedirect: _response.isRedirect,
      requestOptions: _response.requestOptions,
      redirects: _response.redirects,
      statusCode: _response.statusCode,
      statusMessage: _response.statusMessage,
      extra: _response.extra,
    );
  }

  /// cancelDisciplineDetention
  /// School-configured discipline; no default sanctions or automatic expulsion. Fresh school/role and dated allocation or grade leadership authorize staff records. Families see published owned history only; only verified guardians acknowledge. Hearing decisions require an appointed principal. Private body-only UUID writes use encrypted feature receipts and reauthorize before replay. Detention supervision is a separate explicit session grant; unknown attendance remains null.
  ///
  /// Parameters:
  /// * [detention] - detention
  /// * [disciplineDetentionCancelWrite]
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [DisciplineDetentionResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<DisciplineDetentionResponse>> cancelDisciplineDetention({
    required int detention,
    required DisciplineDetentionCancelWrite disciplineDetentionCancelWrite,
    int? xAccountId,
    int? xSchoolId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/discipline/detentions/{detention}/cancel'.replaceAll(
      '{'
      r'detention'
      '}',
      detention.toString(),
    );
    final _options = Options(
      method: r'POST',
      headers: <String, dynamic>{
        if (xAccountId != null) r'X-Account-Id': xAccountId,
        if (xSchoolId != null) r'X-School-Id': xSchoolId,
        ...?headers,
      },
      extra: <String, dynamic>{
        'secure': <Map<String, String>>[
          {
            'type': 'apiKey',
            'name': 'cookieAuth',
            'keyName': 'e_school_session',
            'where': '',
          },
          {'type': 'http', 'scheme': 'bearer', 'name': 'bearerAuth'},
        ],
        ...?extra,
      },
      contentType: 'application/json',
      validateStatus: validateStatus,
    );

    dynamic _bodyData;

    try {
      _bodyData = jsonEncode(disciplineDetentionCancelWrite);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _options.compose(_dio.options, _path),
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    final _response = await _dio.request<Object>(
      _path,
      data: _bodyData,
      options: _options,
      cancelToken: cancelToken,
      onSendProgress: onSendProgress,
      onReceiveProgress: onReceiveProgress,
    );

    DisciplineDetentionResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              DisciplineDetentionResponse,
              DisciplineDetentionResponse
            >(rawData, 'DisciplineDetentionResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<DisciplineDetentionResponse>(
      data: _responseData,
      headers: _response.headers,
      isRedirect: _response.isRedirect,
      requestOptions: _response.requestOptions,
      redirects: _response.redirects,
      statusCode: _response.statusCode,
      statusMessage: _response.statusMessage,
      extra: _response.extra,
    );
  }

  /// captureDisciplineDetentionAttendance
  /// School-configured discipline; no default sanctions or automatic expulsion. Fresh school/role and dated allocation or grade leadership authorize staff records. Families see published owned history only; only verified guardians acknowledge. Hearing decisions require an appointed principal. Private body-only UUID writes use encrypted feature receipts and reauthorize before replay. Detention supervision is a separate explicit session grant; unknown attendance remains null.
  ///
  /// Parameters:
  /// * [detention] - detention
  /// * [entry] - entry
  /// * [disciplineDetentionAttendanceWrite]
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [DisciplineDetentionAttendanceResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<DisciplineDetentionAttendanceResponse>>
  captureDisciplineDetentionAttendance({
    required int detention,
    required int entry,
    required DisciplineDetentionAttendanceWrite
    disciplineDetentionAttendanceWrite,
    int? xAccountId,
    int? xSchoolId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path =
        r'/discipline/detentions/{detention}/entries/{entry}/attendance'
            .replaceAll(
              '{'
              r'detention'
              '}',
              detention.toString(),
            )
            .replaceAll(
              '{'
              r'entry'
              '}',
              entry.toString(),
            );
    final _options = Options(
      method: r'POST',
      headers: <String, dynamic>{
        if (xAccountId != null) r'X-Account-Id': xAccountId,
        if (xSchoolId != null) r'X-School-Id': xSchoolId,
        ...?headers,
      },
      extra: <String, dynamic>{
        'secure': <Map<String, String>>[
          {
            'type': 'apiKey',
            'name': 'cookieAuth',
            'keyName': 'e_school_session',
            'where': '',
          },
          {'type': 'http', 'scheme': 'bearer', 'name': 'bearerAuth'},
        ],
        ...?extra,
      },
      contentType: 'application/json',
      validateStatus: validateStatus,
    );

    dynamic _bodyData;

    try {
      _bodyData = jsonEncode(disciplineDetentionAttendanceWrite);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _options.compose(_dio.options, _path),
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    final _response = await _dio.request<Object>(
      _path,
      data: _bodyData,
      options: _options,
      cancelToken: cancelToken,
      onSendProgress: onSendProgress,
      onReceiveProgress: onReceiveProgress,
    );

    DisciplineDetentionAttendanceResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              DisciplineDetentionAttendanceResponse,
              DisciplineDetentionAttendanceResponse
            >(rawData, 'DisciplineDetentionAttendanceResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<DisciplineDetentionAttendanceResponse>(
      data: _responseData,
      headers: _response.headers,
      isRedirect: _response.isRedirect,
      requestOptions: _response.requestOptions,
      redirects: _response.redirects,
      statusCode: _response.statusCode,
      statusMessage: _response.statusMessage,
      extra: _response.extra,
    );
  }

  /// createDisciplineDetention
  /// School-configured discipline; no default sanctions or automatic expulsion. Fresh school/role and dated allocation or grade leadership authorize staff records. Families see published owned history only; only verified guardians acknowledge. Hearing decisions require an appointed principal. Private body-only UUID writes use encrypted feature receipts and reauthorize before replay. Detention supervision is a separate explicit session grant; unknown attendance remains null.
  ///
  /// Parameters:
  /// * [disciplineDetentionWrite]
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [DisciplineDetentionResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<DisciplineDetentionResponse>> createDisciplineDetention({
    required DisciplineDetentionWrite disciplineDetentionWrite,
    int? xAccountId,
    int? xSchoolId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/discipline/detentions';
    final _options = Options(
      method: r'POST',
      headers: <String, dynamic>{
        if (xAccountId != null) r'X-Account-Id': xAccountId,
        if (xSchoolId != null) r'X-School-Id': xSchoolId,
        ...?headers,
      },
      extra: <String, dynamic>{
        'secure': <Map<String, String>>[
          {
            'type': 'apiKey',
            'name': 'cookieAuth',
            'keyName': 'e_school_session',
            'where': '',
          },
          {'type': 'http', 'scheme': 'bearer', 'name': 'bearerAuth'},
        ],
        ...?extra,
      },
      contentType: 'application/json',
      validateStatus: validateStatus,
    );

    dynamic _bodyData;

    try {
      _bodyData = jsonEncode(disciplineDetentionWrite);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _options.compose(_dio.options, _path),
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    final _response = await _dio.request<Object>(
      _path,
      data: _bodyData,
      options: _options,
      cancelToken: cancelToken,
      onSendProgress: onSendProgress,
      onReceiveProgress: onReceiveProgress,
    );

    DisciplineDetentionResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              DisciplineDetentionResponse,
              DisciplineDetentionResponse
            >(rawData, 'DisciplineDetentionResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<DisciplineDetentionResponse>(
      data: _responseData,
      headers: _response.headers,
      isRedirect: _response.isRedirect,
      requestOptions: _response.requestOptions,
      redirects: _response.redirects,
      statusCode: _response.statusCode,
      statusMessage: _response.statusMessage,
      extra: _response.extra,
    );
  }

  /// createDisciplineRecord
  /// School-configured discipline; no default sanctions or automatic expulsion. Fresh school/role and dated allocation or grade leadership authorize staff records. Families see published owned history only; only verified guardians acknowledge. Hearing decisions require an appointed principal. Private body-only UUID writes use encrypted feature receipts and reauthorize before replay. Detention supervision is a separate explicit session grant; unknown attendance remains null.
  ///
  /// Parameters:
  /// * [learner] - learner
  /// * [disciplineRecordWrite]
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [DisciplineRecordMetadataResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<DisciplineRecordMetadataResponse>> createDisciplineRecord({
    required int learner,
    required DisciplineRecordWrite disciplineRecordWrite,
    int? xAccountId,
    int? xSchoolId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/discipline/learners/{learner}/records'.replaceAll(
      '{'
      r'learner'
      '}',
      learner.toString(),
    );
    final _options = Options(
      method: r'POST',
      headers: <String, dynamic>{
        if (xAccountId != null) r'X-Account-Id': xAccountId,
        if (xSchoolId != null) r'X-School-Id': xSchoolId,
        ...?headers,
      },
      extra: <String, dynamic>{
        'secure': <Map<String, String>>[
          {
            'type': 'apiKey',
            'name': 'cookieAuth',
            'keyName': 'e_school_session',
            'where': '',
          },
          {'type': 'http', 'scheme': 'bearer', 'name': 'bearerAuth'},
        ],
        ...?extra,
      },
      contentType: 'application/json',
      validateStatus: validateStatus,
    );

    dynamic _bodyData;

    try {
      _bodyData = jsonEncode(disciplineRecordWrite);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _options.compose(_dio.options, _path),
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    final _response = await _dio.request<Object>(
      _path,
      data: _bodyData,
      options: _options,
      cancelToken: cancelToken,
      onSendProgress: onSendProgress,
      onReceiveProgress: onReceiveProgress,
    );

    DisciplineRecordMetadataResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              DisciplineRecordMetadataResponse,
              DisciplineRecordMetadataResponse
            >(rawData, 'DisciplineRecordMetadataResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<DisciplineRecordMetadataResponse>(
      data: _responseData,
      headers: _response.headers,
      isRedirect: _response.isRedirect,
      requestOptions: _response.requestOptions,
      redirects: _response.redirects,
      statusCode: _response.statusCode,
      statusMessage: _response.statusMessage,
      extra: _response.extra,
    );
  }

  /// enrollDisciplineDetention
  /// School-configured discipline; no default sanctions or automatic expulsion. Fresh school/role and dated allocation or grade leadership authorize staff records. Families see published owned history only; only verified guardians acknowledge. Hearing decisions require an appointed principal. Private body-only UUID writes use encrypted feature receipts and reauthorize before replay. Detention supervision is a separate explicit session grant; unknown attendance remains null.
  ///
  /// Parameters:
  /// * [detention] - detention
  /// * [disciplineDetentionEnrollWrite]
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [DisciplineDetentionEnrollmentResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<DisciplineDetentionEnrollmentResponse>>
  enrollDisciplineDetention({
    required int detention,
    required DisciplineDetentionEnrollWrite disciplineDetentionEnrollWrite,
    int? xAccountId,
    int? xSchoolId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/discipline/detentions/{detention}/entries'.replaceAll(
      '{'
      r'detention'
      '}',
      detention.toString(),
    );
    final _options = Options(
      method: r'POST',
      headers: <String, dynamic>{
        if (xAccountId != null) r'X-Account-Id': xAccountId,
        if (xSchoolId != null) r'X-School-Id': xSchoolId,
        ...?headers,
      },
      extra: <String, dynamic>{
        'secure': <Map<String, String>>[
          {
            'type': 'apiKey',
            'name': 'cookieAuth',
            'keyName': 'e_school_session',
            'where': '',
          },
          {'type': 'http', 'scheme': 'bearer', 'name': 'bearerAuth'},
        ],
        ...?extra,
      },
      contentType: 'application/json',
      validateStatus: validateStatus,
    );

    dynamic _bodyData;

    try {
      _bodyData = jsonEncode(disciplineDetentionEnrollWrite);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _options.compose(_dio.options, _path),
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    final _response = await _dio.request<Object>(
      _path,
      data: _bodyData,
      options: _options,
      cancelToken: cancelToken,
      onSendProgress: onSendProgress,
      onReceiveProgress: onReceiveProgress,
    );

    DisciplineDetentionEnrollmentResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              DisciplineDetentionEnrollmentResponse,
              DisciplineDetentionEnrollmentResponse
            >(rawData, 'DisciplineDetentionEnrollmentResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<DisciplineDetentionEnrollmentResponse>(
      data: _responseData,
      headers: _response.headers,
      isRedirect: _response.isRedirect,
      requestOptions: _response.requestOptions,
      redirects: _response.redirects,
      statusCode: _response.statusCode,
      statusMessage: _response.statusMessage,
      extra: _response.extra,
    );
  }

  /// getDisciplineConfiguration
  /// School-configured discipline; no default sanctions or automatic expulsion. Fresh school/role and dated allocation or grade leadership authorize staff records. Families see published owned history only; only verified guardians acknowledge. Hearing decisions require an appointed principal. Private body-only UUID writes use encrypted feature receipts and reauthorize before replay. Detention supervision is a separate explicit session grant; unknown attendance remains null.
  ///
  /// Parameters:
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [DisciplineConfigurationResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<DisciplineConfigurationResponse>> getDisciplineConfiguration({
    int? xAccountId,
    int? xSchoolId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/discipline/configuration';
    final _options = Options(
      method: r'GET',
      headers: <String, dynamic>{
        if (xAccountId != null) r'X-Account-Id': xAccountId,
        if (xSchoolId != null) r'X-School-Id': xSchoolId,
        ...?headers,
      },
      extra: <String, dynamic>{
        'secure': <Map<String, String>>[
          {
            'type': 'apiKey',
            'name': 'cookieAuth',
            'keyName': 'e_school_session',
            'where': '',
          },
          {'type': 'http', 'scheme': 'bearer', 'name': 'bearerAuth'},
        ],
        ...?extra,
      },
      validateStatus: validateStatus,
    );

    final _response = await _dio.request<Object>(
      _path,
      options: _options,
      cancelToken: cancelToken,
      onSendProgress: onSendProgress,
      onReceiveProgress: onReceiveProgress,
    );

    DisciplineConfigurationResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              DisciplineConfigurationResponse,
              DisciplineConfigurationResponse
            >(rawData, 'DisciplineConfigurationResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<DisciplineConfigurationResponse>(
      data: _responseData,
      headers: _response.headers,
      isRedirect: _response.isRedirect,
      requestOptions: _response.requestOptions,
      redirects: _response.redirects,
      statusCode: _response.statusCode,
      statusMessage: _response.statusMessage,
      extra: _response.extra,
    );
  }

  /// getDisciplineDetention
  /// School-configured discipline; no default sanctions or automatic expulsion. Fresh school/role and dated allocation or grade leadership authorize staff records. Families see published owned history only; only verified guardians acknowledge. Hearing decisions require an appointed principal. Private body-only UUID writes use encrypted feature receipts and reauthorize before replay. Detention supervision is a separate explicit session grant; unknown attendance remains null.
  ///
  /// Parameters:
  /// * [detention] - detention
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [DisciplineDetentionDetailResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<DisciplineDetentionDetailResponse>> getDisciplineDetention({
    required int detention,
    int? xAccountId,
    int? xSchoolId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/discipline/detentions/{detention}'.replaceAll(
      '{'
      r'detention'
      '}',
      detention.toString(),
    );
    final _options = Options(
      method: r'GET',
      headers: <String, dynamic>{
        if (xAccountId != null) r'X-Account-Id': xAccountId,
        if (xSchoolId != null) r'X-School-Id': xSchoolId,
        ...?headers,
      },
      extra: <String, dynamic>{
        'secure': <Map<String, String>>[
          {
            'type': 'apiKey',
            'name': 'cookieAuth',
            'keyName': 'e_school_session',
            'where': '',
          },
          {'type': 'http', 'scheme': 'bearer', 'name': 'bearerAuth'},
        ],
        ...?extra,
      },
      validateStatus: validateStatus,
    );

    final _response = await _dio.request<Object>(
      _path,
      options: _options,
      cancelToken: cancelToken,
      onSendProgress: onSendProgress,
      onReceiveProgress: onReceiveProgress,
    );

    DisciplineDetentionDetailResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              DisciplineDetentionDetailResponse,
              DisciplineDetentionDetailResponse
            >(rawData, 'DisciplineDetentionDetailResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<DisciplineDetentionDetailResponse>(
      data: _responseData,
      headers: _response.headers,
      isRedirect: _response.isRedirect,
      requestOptions: _response.requestOptions,
      redirects: _response.redirects,
      statusCode: _response.statusCode,
      statusMessage: _response.statusMessage,
      extra: _response.extra,
    );
  }

  /// getDisciplineDetentions
  /// School-configured discipline; no default sanctions or automatic expulsion. Fresh school/role and dated allocation or grade leadership authorize staff records. Families see published owned history only; only verified guardians acknowledge. Hearing decisions require an appointed principal. Private body-only UUID writes use encrypted feature receipts and reauthorize before replay. Detention supervision is a separate explicit session grant; unknown attendance remains null.
  ///
  /// Parameters:
  /// * [sessionYearId] - session year id
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [page] - page
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [DisciplineDetentionsResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<DisciplineDetentionsResponse>> getDisciplineDetentions({
    required int sessionYearId,
    int? xAccountId,
    int? xSchoolId,
    int? page,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/discipline/detentions';
    final _options = Options(
      method: r'GET',
      headers: <String, dynamic>{
        if (xAccountId != null) r'X-Account-Id': xAccountId,
        if (xSchoolId != null) r'X-School-Id': xSchoolId,
        ...?headers,
      },
      extra: <String, dynamic>{
        'secure': <Map<String, String>>[
          {
            'type': 'apiKey',
            'name': 'cookieAuth',
            'keyName': 'e_school_session',
            'where': '',
          },
          {'type': 'http', 'scheme': 'bearer', 'name': 'bearerAuth'},
        ],
        ...?extra,
      },
      validateStatus: validateStatus,
    );

    final _queryParameters = <String, dynamic>{
      r'session_year_id': sessionYearId,
      if (page != null) r'page': page,
    };

    final _response = await _dio.request<Object>(
      _path,
      options: _options,
      queryParameters: _queryParameters,
      cancelToken: cancelToken,
      onSendProgress: onSendProgress,
      onReceiveProgress: onReceiveProgress,
    );

    DisciplineDetentionsResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              DisciplineDetentionsResponse,
              DisciplineDetentionsResponse
            >(rawData, 'DisciplineDetentionsResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<DisciplineDetentionsResponse>(
      data: _responseData,
      headers: _response.headers,
      isRedirect: _response.isRedirect,
      requestOptions: _response.requestOptions,
      redirects: _response.redirects,
      statusCode: _response.statusCode,
      statusMessage: _response.statusMessage,
      extra: _response.extra,
    );
  }

  /// getDisciplineFramework
  /// School-configured discipline; no default sanctions or automatic expulsion. Fresh school/role and dated allocation or grade leadership authorize staff records. Families see published owned history only; only verified guardians acknowledge. Hearing decisions require an appointed principal. Private body-only UUID writes use encrypted feature receipts and reauthorize before replay. Detention supervision is a separate explicit session grant; unknown attendance remains null.
  ///
  /// Parameters:
  /// * [sessionYearId] - session year id
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [DisciplineFrameworkViewResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<DisciplineFrameworkViewResponse>> getDisciplineFramework({
    required int sessionYearId,
    int? xAccountId,
    int? xSchoolId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/discipline/framework';
    final _options = Options(
      method: r'GET',
      headers: <String, dynamic>{
        if (xAccountId != null) r'X-Account-Id': xAccountId,
        if (xSchoolId != null) r'X-School-Id': xSchoolId,
        ...?headers,
      },
      extra: <String, dynamic>{
        'secure': <Map<String, String>>[
          {
            'type': 'apiKey',
            'name': 'cookieAuth',
            'keyName': 'e_school_session',
            'where': '',
          },
          {'type': 'http', 'scheme': 'bearer', 'name': 'bearerAuth'},
        ],
        ...?extra,
      },
      validateStatus: validateStatus,
    );

    final _queryParameters = <String, dynamic>{
      r'session_year_id': sessionYearId,
    };

    final _response = await _dio.request<Object>(
      _path,
      options: _options,
      queryParameters: _queryParameters,
      cancelToken: cancelToken,
      onSendProgress: onSendProgress,
      onReceiveProgress: onReceiveProgress,
    );

    DisciplineFrameworkViewResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              DisciplineFrameworkViewResponse,
              DisciplineFrameworkViewResponse
            >(rawData, 'DisciplineFrameworkViewResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<DisciplineFrameworkViewResponse>(
      data: _responseData,
      headers: _response.headers,
      isRedirect: _response.isRedirect,
      requestOptions: _response.requestOptions,
      redirects: _response.redirects,
      statusCode: _response.statusCode,
      statusMessage: _response.statusMessage,
      extra: _response.extra,
    );
  }

  /// getDisciplineLearners
  /// School-configured discipline; no default sanctions or automatic expulsion. Fresh school/role and dated allocation or grade leadership authorize staff records. Families see published owned history only; only verified guardians acknowledge. Hearing decisions require an appointed principal. Private body-only UUID writes use encrypted feature receipts and reauthorize before replay. Detention supervision is a separate explicit session grant; unknown attendance remains null.
  ///
  /// Parameters:
  /// * [sessionYearId] - session year id
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [page] - page
  /// * [search] - search
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [DisciplineLearnerChoicesResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<DisciplineLearnerChoicesResponse>> getDisciplineLearners({
    required int sessionYearId,
    int? xAccountId,
    int? xSchoolId,
    int? page,
    String? search,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/discipline/learners';
    final _options = Options(
      method: r'GET',
      headers: <String, dynamic>{
        if (xAccountId != null) r'X-Account-Id': xAccountId,
        if (xSchoolId != null) r'X-School-Id': xSchoolId,
        ...?headers,
      },
      extra: <String, dynamic>{
        'secure': <Map<String, String>>[
          {
            'type': 'apiKey',
            'name': 'cookieAuth',
            'keyName': 'e_school_session',
            'where': '',
          },
          {'type': 'http', 'scheme': 'bearer', 'name': 'bearerAuth'},
        ],
        ...?extra,
      },
      validateStatus: validateStatus,
    );

    final _queryParameters = <String, dynamic>{
      r'session_year_id': sessionYearId,
      if (page != null) r'page': page,
      if (search != null) r'search': search,
    };

    final _response = await _dio.request<Object>(
      _path,
      options: _options,
      queryParameters: _queryParameters,
      cancelToken: cancelToken,
      onSendProgress: onSendProgress,
      onReceiveProgress: onReceiveProgress,
    );

    DisciplineLearnerChoicesResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              DisciplineLearnerChoicesResponse,
              DisciplineLearnerChoicesResponse
            >(rawData, 'DisciplineLearnerChoicesResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<DisciplineLearnerChoicesResponse>(
      data: _responseData,
      headers: _response.headers,
      isRedirect: _response.isRedirect,
      requestOptions: _response.requestOptions,
      redirects: _response.redirects,
      statusCode: _response.statusCode,
      statusMessage: _response.statusMessage,
      extra: _response.extra,
    );
  }

  /// getDisciplineRecord
  /// School-configured discipline; no default sanctions or automatic expulsion. Fresh school/role and dated allocation or grade leadership authorize staff records. Families see published owned history only; only verified guardians acknowledge. Hearing decisions require an appointed principal. Private body-only UUID writes use encrypted feature receipts and reauthorize before replay. Detention supervision is a separate explicit session grant; unknown attendance remains null.
  ///
  /// Parameters:
  /// * [record] - record
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [DisciplineRecordDetailResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<DisciplineRecordDetailResponse>> getDisciplineRecord({
    required int record,
    int? xAccountId,
    int? xSchoolId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/discipline/records/{record}'.replaceAll(
      '{'
      r'record'
      '}',
      record.toString(),
    );
    final _options = Options(
      method: r'GET',
      headers: <String, dynamic>{
        if (xAccountId != null) r'X-Account-Id': xAccountId,
        if (xSchoolId != null) r'X-School-Id': xSchoolId,
        ...?headers,
      },
      extra: <String, dynamic>{
        'secure': <Map<String, String>>[
          {
            'type': 'apiKey',
            'name': 'cookieAuth',
            'keyName': 'e_school_session',
            'where': '',
          },
          {'type': 'http', 'scheme': 'bearer', 'name': 'bearerAuth'},
        ],
        ...?extra,
      },
      validateStatus: validateStatus,
    );

    final _response = await _dio.request<Object>(
      _path,
      options: _options,
      cancelToken: cancelToken,
      onSendProgress: onSendProgress,
      onReceiveProgress: onReceiveProgress,
    );

    DisciplineRecordDetailResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              DisciplineRecordDetailResponse,
              DisciplineRecordDetailResponse
            >(rawData, 'DisciplineRecordDetailResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<DisciplineRecordDetailResponse>(
      data: _responseData,
      headers: _response.headers,
      isRedirect: _response.isRedirect,
      requestOptions: _response.requestOptions,
      redirects: _response.redirects,
      statusCode: _response.statusCode,
      statusMessage: _response.statusMessage,
      extra: _response.extra,
    );
  }

  /// getDisciplineRecordHistory
  /// School-configured discipline; no default sanctions or automatic expulsion. Fresh school/role and dated allocation or grade leadership authorize staff records. Families see published owned history only; only verified guardians acknowledge. Hearing decisions require an appointed principal. Private body-only UUID writes use encrypted feature receipts and reauthorize before replay. Detention supervision is a separate explicit session grant; unknown attendance remains null.
  ///
  /// Parameters:
  /// * [record] - record
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [DisciplineRecordHistoryResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<DisciplineRecordHistoryResponse>> getDisciplineRecordHistory({
    required int record,
    int? xAccountId,
    int? xSchoolId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/discipline/records/{record}/history'.replaceAll(
      '{'
      r'record'
      '}',
      record.toString(),
    );
    final _options = Options(
      method: r'GET',
      headers: <String, dynamic>{
        if (xAccountId != null) r'X-Account-Id': xAccountId,
        if (xSchoolId != null) r'X-School-Id': xSchoolId,
        ...?headers,
      },
      extra: <String, dynamic>{
        'secure': <Map<String, String>>[
          {
            'type': 'apiKey',
            'name': 'cookieAuth',
            'keyName': 'e_school_session',
            'where': '',
          },
          {'type': 'http', 'scheme': 'bearer', 'name': 'bearerAuth'},
        ],
        ...?extra,
      },
      validateStatus: validateStatus,
    );

    final _response = await _dio.request<Object>(
      _path,
      options: _options,
      cancelToken: cancelToken,
      onSendProgress: onSendProgress,
      onReceiveProgress: onReceiveProgress,
    );

    DisciplineRecordHistoryResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              DisciplineRecordHistoryResponse,
              DisciplineRecordHistoryResponse
            >(rawData, 'DisciplineRecordHistoryResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<DisciplineRecordHistoryResponse>(
      data: _responseData,
      headers: _response.headers,
      isRedirect: _response.isRedirect,
      requestOptions: _response.requestOptions,
      redirects: _response.redirects,
      statusCode: _response.statusCode,
      statusMessage: _response.statusMessage,
      extra: _response.extra,
    );
  }

  /// getDisciplineRecords
  /// School-configured discipline; no default sanctions or automatic expulsion. Fresh school/role and dated allocation or grade leadership authorize staff records. Families see published owned history only; only verified guardians acknowledge. Hearing decisions require an appointed principal. Private body-only UUID writes use encrypted feature receipts and reauthorize before replay. Detention supervision is a separate explicit session grant; unknown attendance remains null.
  ///
  /// Parameters:
  /// * [sessionYearId] - session year id
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [page] - page
  /// * [status] - status
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [DisciplineRecordQueueResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<DisciplineRecordQueueResponse>> getDisciplineRecords({
    required int sessionYearId,
    int? xAccountId,
    int? xSchoolId,
    int? page,
    String? status,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/discipline/records';
    final _options = Options(
      method: r'GET',
      headers: <String, dynamic>{
        if (xAccountId != null) r'X-Account-Id': xAccountId,
        if (xSchoolId != null) r'X-School-Id': xSchoolId,
        ...?headers,
      },
      extra: <String, dynamic>{
        'secure': <Map<String, String>>[
          {
            'type': 'apiKey',
            'name': 'cookieAuth',
            'keyName': 'e_school_session',
            'where': '',
          },
          {'type': 'http', 'scheme': 'bearer', 'name': 'bearerAuth'},
        ],
        ...?extra,
      },
      validateStatus: validateStatus,
    );

    final _queryParameters = <String, dynamic>{
      r'session_year_id': sessionYearId,
      if (page != null) r'page': page,
      if (status != null) r'status': status,
    };

    final _response = await _dio.request<Object>(
      _path,
      options: _options,
      queryParameters: _queryParameters,
      cancelToken: cancelToken,
      onSendProgress: onSendProgress,
      onReceiveProgress: onReceiveProgress,
    );

    DisciplineRecordQueueResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              DisciplineRecordQueueResponse,
              DisciplineRecordQueueResponse
            >(rawData, 'DisciplineRecordQueueResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<DisciplineRecordQueueResponse>(
      data: _responseData,
      headers: _response.headers,
      isRedirect: _response.isRedirect,
      requestOptions: _response.requestOptions,
      redirects: _response.redirects,
      statusCode: _response.statusCode,
      statusMessage: _response.statusMessage,
      extra: _response.extra,
    );
  }

  /// getDisciplineSupervisors
  /// School-configured discipline; no default sanctions or automatic expulsion. Fresh school/role and dated allocation or grade leadership authorize staff records. Families see published owned history only; only verified guardians acknowledge. Hearing decisions require an appointed principal. Private body-only UUID writes use encrypted feature receipts and reauthorize before replay. Detention supervision is a separate explicit session grant; unknown attendance remains null.
  ///
  /// Parameters:
  /// * [sessionYearId] - session year id
  /// * [gradeLevelId] - grade level id
  /// * [onDate] - on date
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [page] - page
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [DisciplineSupervisorsResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<DisciplineSupervisorsResponse>> getDisciplineSupervisors({
    required int sessionYearId,
    required int gradeLevelId,
    required String onDate,
    int? xAccountId,
    int? xSchoolId,
    int? page,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/discipline/supervisors';
    final _options = Options(
      method: r'GET',
      headers: <String, dynamic>{
        if (xAccountId != null) r'X-Account-Id': xAccountId,
        if (xSchoolId != null) r'X-School-Id': xSchoolId,
        ...?headers,
      },
      extra: <String, dynamic>{
        'secure': <Map<String, String>>[
          {
            'type': 'apiKey',
            'name': 'cookieAuth',
            'keyName': 'e_school_session',
            'where': '',
          },
          {'type': 'http', 'scheme': 'bearer', 'name': 'bearerAuth'},
        ],
        ...?extra,
      },
      validateStatus: validateStatus,
    );

    final _queryParameters = <String, dynamic>{
      r'session_year_id': sessionYearId,
      r'grade_level_id': gradeLevelId,
      r'on_date': onDate,
      if (page != null) r'page': page,
    };

    final _response = await _dio.request<Object>(
      _path,
      options: _options,
      queryParameters: _queryParameters,
      cancelToken: cancelToken,
      onSendProgress: onSendProgress,
      onReceiveProgress: onReceiveProgress,
    );

    DisciplineSupervisorsResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              DisciplineSupervisorsResponse,
              DisciplineSupervisorsResponse
            >(rawData, 'DisciplineSupervisorsResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<DisciplineSupervisorsResponse>(
      data: _responseData,
      headers: _response.headers,
      isRedirect: _response.isRedirect,
      requestOptions: _response.requestOptions,
      redirects: _response.redirects,
      statusCode: _response.statusCode,
      statusMessage: _response.statusMessage,
      extra: _response.extra,
    );
  }

  /// getFamilyDisciplineHistory
  /// School-configured discipline; no default sanctions or automatic expulsion. Fresh school/role and dated allocation or grade leadership authorize staff records. Families see published owned history only; only verified guardians acknowledge. Hearing decisions require an appointed principal. Private body-only UUID writes use encrypted feature receipts and reauthorize before replay. Detention supervision is a separate explicit session grant; unknown attendance remains null.
  ///
  /// Parameters:
  /// * [learner] - learner
  /// * [sessionYearId] - session year id
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [page] - page
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [DisciplineFamilyHistoryResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<DisciplineFamilyHistoryResponse>> getFamilyDisciplineHistory({
    required int learner,
    required int sessionYearId,
    int? xAccountId,
    int? xSchoolId,
    int? page,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/discipline/family/learners/{learner}'.replaceAll(
      '{'
      r'learner'
      '}',
      learner.toString(),
    );
    final _options = Options(
      method: r'GET',
      headers: <String, dynamic>{
        if (xAccountId != null) r'X-Account-Id': xAccountId,
        if (xSchoolId != null) r'X-School-Id': xSchoolId,
        ...?headers,
      },
      extra: <String, dynamic>{
        'secure': <Map<String, String>>[
          {
            'type': 'apiKey',
            'name': 'cookieAuth',
            'keyName': 'e_school_session',
            'where': '',
          },
          {'type': 'http', 'scheme': 'bearer', 'name': 'bearerAuth'},
        ],
        ...?extra,
      },
      validateStatus: validateStatus,
    );

    final _queryParameters = <String, dynamic>{
      r'session_year_id': sessionYearId,
      if (page != null) r'page': page,
    };

    final _response = await _dio.request<Object>(
      _path,
      options: _options,
      queryParameters: _queryParameters,
      cancelToken: cancelToken,
      onSendProgress: onSendProgress,
      onReceiveProgress: onReceiveProgress,
    );

    DisciplineFamilyHistoryResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              DisciplineFamilyHistoryResponse,
              DisciplineFamilyHistoryResponse
            >(rawData, 'DisciplineFamilyHistoryResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<DisciplineFamilyHistoryResponse>(
      data: _responseData,
      headers: _response.headers,
      isRedirect: _response.isRedirect,
      requestOptions: _response.requestOptions,
      redirects: _response.redirects,
      statusCode: _response.statusCode,
      statusMessage: _response.statusMessage,
      extra: _response.extra,
    );
  }

  /// resolveDisciplineHearing
  /// School-configured discipline; no default sanctions or automatic expulsion. Fresh school/role and dated allocation or grade leadership authorize staff records. Families see published owned history only; only verified guardians acknowledge. Hearing decisions require an appointed principal. Private body-only UUID writes use encrypted feature receipts and reauthorize before replay. Detention supervision is a separate explicit session grant; unknown attendance remains null.
  ///
  /// Parameters:
  /// * [record] - record
  /// * [disciplineHearingWrite]
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [DisciplineRecordMetadataResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<DisciplineRecordMetadataResponse>> resolveDisciplineHearing({
    required int record,
    required DisciplineHearingWrite disciplineHearingWrite,
    int? xAccountId,
    int? xSchoolId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/discipline/records/{record}/hearing'.replaceAll(
      '{'
      r'record'
      '}',
      record.toString(),
    );
    final _options = Options(
      method: r'POST',
      headers: <String, dynamic>{
        if (xAccountId != null) r'X-Account-Id': xAccountId,
        if (xSchoolId != null) r'X-School-Id': xSchoolId,
        ...?headers,
      },
      extra: <String, dynamic>{
        'secure': <Map<String, String>>[
          {
            'type': 'apiKey',
            'name': 'cookieAuth',
            'keyName': 'e_school_session',
            'where': '',
          },
          {'type': 'http', 'scheme': 'bearer', 'name': 'bearerAuth'},
        ],
        ...?extra,
      },
      contentType: 'application/json',
      validateStatus: validateStatus,
    );

    dynamic _bodyData;

    try {
      _bodyData = jsonEncode(disciplineHearingWrite);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _options.compose(_dio.options, _path),
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    final _response = await _dio.request<Object>(
      _path,
      data: _bodyData,
      options: _options,
      cancelToken: cancelToken,
      onSendProgress: onSendProgress,
      onReceiveProgress: onReceiveProgress,
    );

    DisciplineRecordMetadataResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              DisciplineRecordMetadataResponse,
              DisciplineRecordMetadataResponse
            >(rawData, 'DisciplineRecordMetadataResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<DisciplineRecordMetadataResponse>(
      data: _responseData,
      headers: _response.headers,
      isRedirect: _response.isRedirect,
      requestOptions: _response.requestOptions,
      redirects: _response.redirects,
      statusCode: _response.statusCode,
      statusMessage: _response.statusMessage,
      extra: _response.extra,
    );
  }

  /// reviewDisciplineRecord
  /// School-configured discipline; no default sanctions or automatic expulsion. Fresh school/role and dated allocation or grade leadership authorize staff records. Families see published owned history only; only verified guardians acknowledge. Hearing decisions require an appointed principal. Private body-only UUID writes use encrypted feature receipts and reauthorize before replay. Detention supervision is a separate explicit session grant; unknown attendance remains null.
  ///
  /// Parameters:
  /// * [record] - record
  /// * [disciplineReviewWrite]
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [DisciplineRecordMetadataResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<DisciplineRecordMetadataResponse>> reviewDisciplineRecord({
    required int record,
    required DisciplineReviewWrite disciplineReviewWrite,
    int? xAccountId,
    int? xSchoolId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/discipline/records/{record}/review'.replaceAll(
      '{'
      r'record'
      '}',
      record.toString(),
    );
    final _options = Options(
      method: r'POST',
      headers: <String, dynamic>{
        if (xAccountId != null) r'X-Account-Id': xAccountId,
        if (xSchoolId != null) r'X-School-Id': xSchoolId,
        ...?headers,
      },
      extra: <String, dynamic>{
        'secure': <Map<String, String>>[
          {
            'type': 'apiKey',
            'name': 'cookieAuth',
            'keyName': 'e_school_session',
            'where': '',
          },
          {'type': 'http', 'scheme': 'bearer', 'name': 'bearerAuth'},
        ],
        ...?extra,
      },
      contentType: 'application/json',
      validateStatus: validateStatus,
    );

    dynamic _bodyData;

    try {
      _bodyData = jsonEncode(disciplineReviewWrite);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _options.compose(_dio.options, _path),
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    final _response = await _dio.request<Object>(
      _path,
      data: _bodyData,
      options: _options,
      cancelToken: cancelToken,
      onSendProgress: onSendProgress,
      onReceiveProgress: onReceiveProgress,
    );

    DisciplineRecordMetadataResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              DisciplineRecordMetadataResponse,
              DisciplineRecordMetadataResponse
            >(rawData, 'DisciplineRecordMetadataResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<DisciplineRecordMetadataResponse>(
      data: _responseData,
      headers: _response.headers,
      isRedirect: _response.isRedirect,
      requestOptions: _response.requestOptions,
      redirects: _response.redirects,
      statusCode: _response.statusCode,
      statusMessage: _response.statusMessage,
      extra: _response.extra,
    );
  }

  /// saveDisciplineFramework
  /// School-configured discipline; no default sanctions or automatic expulsion. Fresh school/role and dated allocation or grade leadership authorize staff records. Families see published owned history only; only verified guardians acknowledge. Hearing decisions require an appointed principal. Private body-only UUID writes use encrypted feature receipts and reauthorize before replay. Detention supervision is a separate explicit session grant; unknown attendance remains null.
  ///
  /// Parameters:
  /// * [disciplineFrameworkWrite]
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [DisciplineFrameworkResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<DisciplineFrameworkResponse>> saveDisciplineFramework({
    required DisciplineFrameworkWrite disciplineFrameworkWrite,
    int? xAccountId,
    int? xSchoolId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/discipline/framework';
    final _options = Options(
      method: r'POST',
      headers: <String, dynamic>{
        if (xAccountId != null) r'X-Account-Id': xAccountId,
        if (xSchoolId != null) r'X-School-Id': xSchoolId,
        ...?headers,
      },
      extra: <String, dynamic>{
        'secure': <Map<String, String>>[
          {
            'type': 'apiKey',
            'name': 'cookieAuth',
            'keyName': 'e_school_session',
            'where': '',
          },
          {'type': 'http', 'scheme': 'bearer', 'name': 'bearerAuth'},
        ],
        ...?extra,
      },
      contentType: 'application/json',
      validateStatus: validateStatus,
    );

    dynamic _bodyData;

    try {
      _bodyData = jsonEncode(disciplineFrameworkWrite);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _options.compose(_dio.options, _path),
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    final _response = await _dio.request<Object>(
      _path,
      data: _bodyData,
      options: _options,
      cancelToken: cancelToken,
      onSendProgress: onSendProgress,
      onReceiveProgress: onReceiveProgress,
    );

    DisciplineFrameworkResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              DisciplineFrameworkResponse,
              DisciplineFrameworkResponse
            >(rawData, 'DisciplineFrameworkResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<DisciplineFrameworkResponse>(
      data: _responseData,
      headers: _response.headers,
      isRedirect: _response.isRedirect,
      requestOptions: _response.requestOptions,
      redirects: _response.redirects,
      statusCode: _response.statusCode,
      statusMessage: _response.statusMessage,
      extra: _response.extra,
    );
  }
}
