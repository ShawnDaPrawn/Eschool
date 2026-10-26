//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

import 'dart:async';

// ignore: unused_import
import 'dart:convert';
import 'package:eschool_api/src/deserialize.dart';
import 'package:dio/dio.dart';

import 'package:eschool_api/src/model/error_response.dart';
import 'package:eschool_api/src/model/school_assistance_claim_request.dart';
import 'package:eschool_api/src/model/school_assistance_close_request.dart';
import 'package:eschool_api/src/model/school_assistance_configuration_response.dart';
import 'package:eschool_api/src/model/school_assistance_document_body_response.dart';
import 'package:eschool_api/src/model/school_assistance_document_page_response.dart';
import 'package:eschool_api/src/model/school_assistance_document_result_response.dart';
import 'package:eschool_api/src/model/school_assistance_office_queue_response.dart';
import 'package:eschool_api/src/model/school_assistance_open_thread_request.dart';
import 'package:eschool_api/src/model/school_assistance_prepare_answer_request.dart';
import 'package:eschool_api/src/model/school_assistance_publish_request.dart';
import 'package:eschool_api/src/model/school_assistance_reassign_request.dart';
import 'package:eschool_api/src/model/school_assistance_reply_request.dart';
import 'package:eschool_api/src/model/school_assistance_run_request.dart';
import 'package:eschool_api/src/model/school_assistance_staff_choices_response.dart';
import 'package:eschool_api/src/model/school_assistance_thread_page_response.dart';
import 'package:eschool_api/src/model/school_assistance_thread_response.dart';
import 'package:eschool_api/src/model/school_assistance_thread_result_response.dart';
import 'package:eschool_api/src/model/school_assistance_translation_request.dart';
import 'package:eschool_api/src/model/school_assistance_turn_response.dart';
import 'package:eschool_api/src/model/school_assistance_unavailable_response.dart';
import 'package:eschool_api/src/model/school_assistance_withdraw_request.dart';

class SchoolAssistanceApi {
  final Dio _dio;

  const SchoolAssistanceApi(this._dio);

  /// claimSchoolAssistanceThread
  /// Fresh selected-school/account/guardian or assigned office authority before private retrieval and every replay. Body-only exact UUID writes; source changes retire prepared reviews. External inference remains disabled until qualified. Originals are preserved; translations never send messages or publish marks.
  ///
  /// Parameters:
  /// * [thread]
  /// * [schoolAssistanceClaimRequest]
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [SchoolAssistanceThreadResultResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<SchoolAssistanceThreadResultResponse>>
  claimSchoolAssistanceThread({
    required String thread,
    required SchoolAssistanceClaimRequest schoolAssistanceClaimRequest,
    int? xSchoolId,
    int? xAccountId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/school-assistance/office/threads/{thread}/claim'
        .replaceAll(
          '{'
          r'thread'
          '}',
          thread.toString(),
        );
    final _options = Options(
      method: r'POST',
      headers: <String, dynamic>{
        if (xSchoolId != null) r'X-School-Id': xSchoolId,
        if (xAccountId != null) r'X-Account-Id': xAccountId,
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
      _bodyData = jsonEncode(schoolAssistanceClaimRequest);
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

    SchoolAssistanceThreadResultResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              SchoolAssistanceThreadResultResponse,
              SchoolAssistanceThreadResultResponse
            >(rawData, 'SchoolAssistanceThreadResultResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<SchoolAssistanceThreadResultResponse>(
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

  /// closeSchoolAssistanceThread
  /// Fresh selected-school/account/guardian or assigned office authority before private retrieval and every replay. Body-only exact UUID writes; source changes retire prepared reviews. External inference remains disabled until qualified. Originals are preserved; translations never send messages or publish marks.
  ///
  /// Parameters:
  /// * [thread]
  /// * [schoolAssistanceCloseRequest]
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [SchoolAssistanceThreadResultResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<SchoolAssistanceThreadResultResponse>>
  closeSchoolAssistanceThread({
    required String thread,
    required SchoolAssistanceCloseRequest schoolAssistanceCloseRequest,
    int? xSchoolId,
    int? xAccountId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/school-assistance/threads/{thread}/close'.replaceAll(
      '{'
      r'thread'
      '}',
      thread.toString(),
    );
    final _options = Options(
      method: r'POST',
      headers: <String, dynamic>{
        if (xSchoolId != null) r'X-School-Id': xSchoolId,
        if (xAccountId != null) r'X-Account-Id': xAccountId,
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
      _bodyData = jsonEncode(schoolAssistanceCloseRequest);
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

    SchoolAssistanceThreadResultResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              SchoolAssistanceThreadResultResponse,
              SchoolAssistanceThreadResultResponse
            >(rawData, 'SchoolAssistanceThreadResultResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<SchoolAssistanceThreadResultResponse>(
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

  /// getSchoolAssistanceConfiguration
  /// Fresh selected-school/account/guardian or assigned office authority before private retrieval and every replay. Body-only exact UUID writes; source changes retire prepared reviews. External inference remains disabled until qualified. Originals are preserved; translations never send messages or publish marks.
  ///
  /// Parameters:
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [page]
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [SchoolAssistanceConfigurationResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<SchoolAssistanceConfigurationResponse>>
  getSchoolAssistanceConfiguration({
    int? xSchoolId,
    int? xAccountId,
    int? page,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/school-assistance/configuration';
    final _options = Options(
      method: r'GET',
      headers: <String, dynamic>{
        if (xSchoolId != null) r'X-School-Id': xSchoolId,
        if (xAccountId != null) r'X-Account-Id': xAccountId,
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

    final _queryParameters = <String, dynamic>{if (page != null) r'page': page};

    final _response = await _dio.request<Object>(
      _path,
      options: _options,
      queryParameters: _queryParameters,
      cancelToken: cancelToken,
      onSendProgress: onSendProgress,
      onReceiveProgress: onReceiveProgress,
    );

    SchoolAssistanceConfigurationResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              SchoolAssistanceConfigurationResponse,
              SchoolAssistanceConfigurationResponse
            >(rawData, 'SchoolAssistanceConfigurationResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<SchoolAssistanceConfigurationResponse>(
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

  /// getSchoolAssistanceDocument
  /// Fresh selected-school/account/guardian or assigned office authority before private retrieval and every replay. Body-only exact UUID writes; source changes retire prepared reviews. External inference remains disabled until qualified. Originals are preserved; translations never send messages or publish marks.
  ///
  /// Parameters:
  /// * [learner]
  /// * [document]
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [page]
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [SchoolAssistanceDocumentBodyResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<SchoolAssistanceDocumentBodyResponse>>
  getSchoolAssistanceDocument({
    required int learner,
    required String document,
    int? xSchoolId,
    int? xAccountId,
    int? page,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path =
        r'/school-assistance/family/learners/{learner}/documents/{document}'
            .replaceAll(
              '{'
              r'learner'
              '}',
              learner.toString(),
            )
            .replaceAll(
              '{'
              r'document'
              '}',
              document.toString(),
            );
    final _options = Options(
      method: r'GET',
      headers: <String, dynamic>{
        if (xSchoolId != null) r'X-School-Id': xSchoolId,
        if (xAccountId != null) r'X-Account-Id': xAccountId,
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

    final _queryParameters = <String, dynamic>{if (page != null) r'page': page};

    final _response = await _dio.request<Object>(
      _path,
      options: _options,
      queryParameters: _queryParameters,
      cancelToken: cancelToken,
      onSendProgress: onSendProgress,
      onReceiveProgress: onReceiveProgress,
    );

    SchoolAssistanceDocumentBodyResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              SchoolAssistanceDocumentBodyResponse,
              SchoolAssistanceDocumentBodyResponse
            >(rawData, 'SchoolAssistanceDocumentBodyResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<SchoolAssistanceDocumentBodyResponse>(
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

  /// getSchoolAssistanceOfficeDocument
  /// Fresh selected-school/account/guardian or assigned office authority before private retrieval and every replay. Body-only exact UUID writes; source changes retire prepared reviews. External inference remains disabled until qualified. Originals are preserved; translations never send messages or publish marks.
  ///
  /// Parameters:
  /// * [document]
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [page]
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [SchoolAssistanceDocumentBodyResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<SchoolAssistanceDocumentBodyResponse>>
  getSchoolAssistanceOfficeDocument({
    required String document,
    int? xSchoolId,
    int? xAccountId,
    int? page,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/school-assistance/office/documents/{document}'.replaceAll(
      '{'
      r'document'
      '}',
      document.toString(),
    );
    final _options = Options(
      method: r'GET',
      headers: <String, dynamic>{
        if (xSchoolId != null) r'X-School-Id': xSchoolId,
        if (xAccountId != null) r'X-Account-Id': xAccountId,
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

    final _queryParameters = <String, dynamic>{if (page != null) r'page': page};

    final _response = await _dio.request<Object>(
      _path,
      options: _options,
      queryParameters: _queryParameters,
      cancelToken: cancelToken,
      onSendProgress: onSendProgress,
      onReceiveProgress: onReceiveProgress,
    );

    SchoolAssistanceDocumentBodyResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              SchoolAssistanceDocumentBodyResponse,
              SchoolAssistanceDocumentBodyResponse
            >(rawData, 'SchoolAssistanceDocumentBodyResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<SchoolAssistanceDocumentBodyResponse>(
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

  /// getSchoolAssistanceOfficeQueue
  /// Fresh selected-school/account/guardian or assigned office authority before private retrieval and every replay. Body-only exact UUID writes; source changes retire prepared reviews. External inference remains disabled until qualified. Originals are preserved; translations never send messages or publish marks.
  ///
  /// Parameters:
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [page]
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [SchoolAssistanceOfficeQueueResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<SchoolAssistanceOfficeQueueResponse>>
  getSchoolAssistanceOfficeQueue({
    int? xSchoolId,
    int? xAccountId,
    int? page,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/school-assistance/office/queue';
    final _options = Options(
      method: r'GET',
      headers: <String, dynamic>{
        if (xSchoolId != null) r'X-School-Id': xSchoolId,
        if (xAccountId != null) r'X-Account-Id': xAccountId,
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

    final _queryParameters = <String, dynamic>{if (page != null) r'page': page};

    final _response = await _dio.request<Object>(
      _path,
      options: _options,
      queryParameters: _queryParameters,
      cancelToken: cancelToken,
      onSendProgress: onSendProgress,
      onReceiveProgress: onReceiveProgress,
    );

    SchoolAssistanceOfficeQueueResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              SchoolAssistanceOfficeQueueResponse,
              SchoolAssistanceOfficeQueueResponse
            >(rawData, 'SchoolAssistanceOfficeQueueResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<SchoolAssistanceOfficeQueueResponse>(
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

  /// getSchoolAssistanceOfficeStaff
  /// Fresh selected-school/account/guardian or assigned office authority before private retrieval and every replay. Body-only exact UUID writes; source changes retire prepared reviews. External inference remains disabled until qualified. Originals are preserved; translations never send messages or publish marks.
  ///
  /// Parameters:
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [page]
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [SchoolAssistanceStaffChoicesResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<SchoolAssistanceStaffChoicesResponse>>
  getSchoolAssistanceOfficeStaff({
    int? xSchoolId,
    int? xAccountId,
    int? page,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/school-assistance/office/staff';
    final _options = Options(
      method: r'GET',
      headers: <String, dynamic>{
        if (xSchoolId != null) r'X-School-Id': xSchoolId,
        if (xAccountId != null) r'X-Account-Id': xAccountId,
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

    final _queryParameters = <String, dynamic>{if (page != null) r'page': page};

    final _response = await _dio.request<Object>(
      _path,
      options: _options,
      queryParameters: _queryParameters,
      cancelToken: cancelToken,
      onSendProgress: onSendProgress,
      onReceiveProgress: onReceiveProgress,
    );

    SchoolAssistanceStaffChoicesResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              SchoolAssistanceStaffChoicesResponse,
              SchoolAssistanceStaffChoicesResponse
            >(rawData, 'SchoolAssistanceStaffChoicesResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<SchoolAssistanceStaffChoicesResponse>(
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

  /// getSchoolAssistanceThread
  /// Fresh selected-school/account/guardian or assigned office authority before private retrieval and every replay. Body-only exact UUID writes; source changes retire prepared reviews. External inference remains disabled until qualified. Originals are preserved; translations never send messages or publish marks.
  ///
  /// Parameters:
  /// * [thread]
  /// * [persona]
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [page]
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [SchoolAssistanceThreadResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<SchoolAssistanceThreadResponse>> getSchoolAssistanceThread({
    required String thread,
    required String persona,
    int? xSchoolId,
    int? xAccountId,
    int? page,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/school-assistance/threads/{thread}'.replaceAll(
      '{'
      r'thread'
      '}',
      thread.toString(),
    );
    final _options = Options(
      method: r'GET',
      headers: <String, dynamic>{
        if (xSchoolId != null) r'X-School-Id': xSchoolId,
        if (xAccountId != null) r'X-Account-Id': xAccountId,
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
      if (page != null) r'page': page,
      r'persona': persona,
    };

    final _response = await _dio.request<Object>(
      _path,
      options: _options,
      queryParameters: _queryParameters,
      cancelToken: cancelToken,
      onSendProgress: onSendProgress,
      onReceiveProgress: onReceiveProgress,
    );

    SchoolAssistanceThreadResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              SchoolAssistanceThreadResponse,
              SchoolAssistanceThreadResponse
            >(rawData, 'SchoolAssistanceThreadResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<SchoolAssistanceThreadResponse>(
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

  /// getSchoolAssistanceTurn
  /// Fresh selected-school/account/guardian or assigned office authority before private retrieval and every replay. Body-only exact UUID writes; source changes retire prepared reviews. External inference remains disabled until qualified. Originals are preserved; translations never send messages or publish marks.
  ///
  /// Parameters:
  /// * [turn]
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [page]
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [SchoolAssistanceTurnResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<SchoolAssistanceTurnResponse>> getSchoolAssistanceTurn({
    required String turn,
    int? xSchoolId,
    int? xAccountId,
    int? page,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/school-assistance/turns/{turn}'.replaceAll(
      '{'
      r'turn'
      '}',
      turn.toString(),
    );
    final _options = Options(
      method: r'GET',
      headers: <String, dynamic>{
        if (xSchoolId != null) r'X-School-Id': xSchoolId,
        if (xAccountId != null) r'X-Account-Id': xAccountId,
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

    final _queryParameters = <String, dynamic>{if (page != null) r'page': page};

    final _response = await _dio.request<Object>(
      _path,
      options: _options,
      queryParameters: _queryParameters,
      cancelToken: cancelToken,
      onSendProgress: onSendProgress,
      onReceiveProgress: onReceiveProgress,
    );

    SchoolAssistanceTurnResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              SchoolAssistanceTurnResponse,
              SchoolAssistanceTurnResponse
            >(rawData, 'SchoolAssistanceTurnResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<SchoolAssistanceTurnResponse>(
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

  /// listSchoolAssistanceDocuments
  /// Fresh selected-school/account/guardian or assigned office authority before private retrieval and every replay. Body-only exact UUID writes; source changes retire prepared reviews. External inference remains disabled until qualified. Originals are preserved; translations never send messages or publish marks.
  ///
  /// Parameters:
  /// * [learner]
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [page]
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [SchoolAssistanceDocumentPageResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<SchoolAssistanceDocumentPageResponse>>
  listSchoolAssistanceDocuments({
    required int learner,
    int? xSchoolId,
    int? xAccountId,
    int? page,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/school-assistance/family/learners/{learner}/documents'
        .replaceAll(
          '{'
          r'learner'
          '}',
          learner.toString(),
        );
    final _options = Options(
      method: r'GET',
      headers: <String, dynamic>{
        if (xSchoolId != null) r'X-School-Id': xSchoolId,
        if (xAccountId != null) r'X-Account-Id': xAccountId,
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

    final _queryParameters = <String, dynamic>{if (page != null) r'page': page};

    final _response = await _dio.request<Object>(
      _path,
      options: _options,
      queryParameters: _queryParameters,
      cancelToken: cancelToken,
      onSendProgress: onSendProgress,
      onReceiveProgress: onReceiveProgress,
    );

    SchoolAssistanceDocumentPageResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              SchoolAssistanceDocumentPageResponse,
              SchoolAssistanceDocumentPageResponse
            >(rawData, 'SchoolAssistanceDocumentPageResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<SchoolAssistanceDocumentPageResponse>(
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

  /// listSchoolAssistanceOfficeDocuments
  /// Fresh selected-school/account/guardian or assigned office authority before private retrieval and every replay. Body-only exact UUID writes; source changes retire prepared reviews. External inference remains disabled until qualified. Originals are preserved; translations never send messages or publish marks.
  ///
  /// Parameters:
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [page]
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [SchoolAssistanceDocumentPageResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<SchoolAssistanceDocumentPageResponse>>
  listSchoolAssistanceOfficeDocuments({
    int? xSchoolId,
    int? xAccountId,
    int? page,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/school-assistance/office/documents';
    final _options = Options(
      method: r'GET',
      headers: <String, dynamic>{
        if (xSchoolId != null) r'X-School-Id': xSchoolId,
        if (xAccountId != null) r'X-Account-Id': xAccountId,
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

    final _queryParameters = <String, dynamic>{if (page != null) r'page': page};

    final _response = await _dio.request<Object>(
      _path,
      options: _options,
      queryParameters: _queryParameters,
      cancelToken: cancelToken,
      onSendProgress: onSendProgress,
      onReceiveProgress: onReceiveProgress,
    );

    SchoolAssistanceDocumentPageResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              SchoolAssistanceDocumentPageResponse,
              SchoolAssistanceDocumentPageResponse
            >(rawData, 'SchoolAssistanceDocumentPageResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<SchoolAssistanceDocumentPageResponse>(
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

  /// listSchoolAssistanceThreads
  /// Fresh selected-school/account/guardian or assigned office authority before private retrieval and every replay. Body-only exact UUID writes; source changes retire prepared reviews. External inference remains disabled until qualified. Originals are preserved; translations never send messages or publish marks.
  ///
  /// Parameters:
  /// * [learner]
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [page]
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [SchoolAssistanceThreadPageResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<SchoolAssistanceThreadPageResponse>>
  listSchoolAssistanceThreads({
    required int learner,
    int? xSchoolId,
    int? xAccountId,
    int? page,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/school-assistance/family/learners/{learner}/threads'
        .replaceAll(
          '{'
          r'learner'
          '}',
          learner.toString(),
        );
    final _options = Options(
      method: r'GET',
      headers: <String, dynamic>{
        if (xSchoolId != null) r'X-School-Id': xSchoolId,
        if (xAccountId != null) r'X-Account-Id': xAccountId,
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

    final _queryParameters = <String, dynamic>{if (page != null) r'page': page};

    final _response = await _dio.request<Object>(
      _path,
      options: _options,
      queryParameters: _queryParameters,
      cancelToken: cancelToken,
      onSendProgress: onSendProgress,
      onReceiveProgress: onReceiveProgress,
    );

    SchoolAssistanceThreadPageResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              SchoolAssistanceThreadPageResponse,
              SchoolAssistanceThreadPageResponse
            >(rawData, 'SchoolAssistanceThreadPageResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<SchoolAssistanceThreadPageResponse>(
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

  /// openSchoolAssistanceThread
  /// Fresh selected-school/account/guardian or assigned office authority before private retrieval and every replay. Body-only exact UUID writes; source changes retire prepared reviews. External inference remains disabled until qualified. Originals are preserved; translations never send messages or publish marks.
  ///
  /// Parameters:
  /// * [learner]
  /// * [schoolAssistanceOpenThreadRequest]
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [SchoolAssistanceThreadResultResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<SchoolAssistanceThreadResultResponse>>
  openSchoolAssistanceThread({
    required int learner,
    required SchoolAssistanceOpenThreadRequest
    schoolAssistanceOpenThreadRequest,
    int? xSchoolId,
    int? xAccountId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/school-assistance/family/learners/{learner}/threads'
        .replaceAll(
          '{'
          r'learner'
          '}',
          learner.toString(),
        );
    final _options = Options(
      method: r'POST',
      headers: <String, dynamic>{
        if (xSchoolId != null) r'X-School-Id': xSchoolId,
        if (xAccountId != null) r'X-Account-Id': xAccountId,
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
      _bodyData = jsonEncode(schoolAssistanceOpenThreadRequest);
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

    SchoolAssistanceThreadResultResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              SchoolAssistanceThreadResultResponse,
              SchoolAssistanceThreadResultResponse
            >(rawData, 'SchoolAssistanceThreadResultResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<SchoolAssistanceThreadResultResponse>(
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

  /// prepareSchoolAssistanceAnswer
  /// Fresh selected-school/account/guardian or assigned office authority before private retrieval and every replay. Body-only exact UUID writes; source changes retire prepared reviews. External inference remains disabled until qualified. Originals are preserved; translations never send messages or publish marks.
  ///
  /// Parameters:
  /// * [learner]
  /// * [schoolAssistancePrepareAnswerRequest]
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [SchoolAssistanceTurnResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<SchoolAssistanceTurnResponse>> prepareSchoolAssistanceAnswer({
    required int learner,
    required SchoolAssistancePrepareAnswerRequest
    schoolAssistancePrepareAnswerRequest,
    int? xSchoolId,
    int? xAccountId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/school-assistance/family/learners/{learner}/answers'
        .replaceAll(
          '{'
          r'learner'
          '}',
          learner.toString(),
        );
    final _options = Options(
      method: r'POST',
      headers: <String, dynamic>{
        if (xSchoolId != null) r'X-School-Id': xSchoolId,
        if (xAccountId != null) r'X-Account-Id': xAccountId,
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
      _bodyData = jsonEncode(schoolAssistancePrepareAnswerRequest);
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

    SchoolAssistanceTurnResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              SchoolAssistanceTurnResponse,
              SchoolAssistanceTurnResponse
            >(rawData, 'SchoolAssistanceTurnResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<SchoolAssistanceTurnResponse>(
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

  /// prepareSchoolAssistanceOfficeTranslation
  /// Fresh selected-school/account/guardian or assigned office authority before private retrieval and every replay. Body-only exact UUID writes; source changes retire prepared reviews. External inference remains disabled until qualified. Originals are preserved; translations never send messages or publish marks.
  ///
  /// Parameters:
  /// * [learner]
  /// * [schoolAssistanceTranslationRequest]
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [SchoolAssistanceTurnResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<SchoolAssistanceTurnResponse>>
  prepareSchoolAssistanceOfficeTranslation({
    required int learner,
    required SchoolAssistanceTranslationRequest
    schoolAssistanceTranslationRequest,
    int? xSchoolId,
    int? xAccountId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/school-assistance/office/learners/{learner}/translations'
        .replaceAll(
          '{'
          r'learner'
          '}',
          learner.toString(),
        );
    final _options = Options(
      method: r'POST',
      headers: <String, dynamic>{
        if (xSchoolId != null) r'X-School-Id': xSchoolId,
        if (xAccountId != null) r'X-Account-Id': xAccountId,
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
      _bodyData = jsonEncode(schoolAssistanceTranslationRequest);
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

    SchoolAssistanceTurnResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              SchoolAssistanceTurnResponse,
              SchoolAssistanceTurnResponse
            >(rawData, 'SchoolAssistanceTurnResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<SchoolAssistanceTurnResponse>(
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

  /// prepareSchoolAssistanceTranslation
  /// Fresh selected-school/account/guardian or assigned office authority before private retrieval and every replay. Body-only exact UUID writes; source changes retire prepared reviews. External inference remains disabled until qualified. Originals are preserved; translations never send messages or publish marks.
  ///
  /// Parameters:
  /// * [learner]
  /// * [schoolAssistanceTranslationRequest]
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [SchoolAssistanceTurnResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<SchoolAssistanceTurnResponse>>
  prepareSchoolAssistanceTranslation({
    required int learner,
    required SchoolAssistanceTranslationRequest
    schoolAssistanceTranslationRequest,
    int? xSchoolId,
    int? xAccountId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/school-assistance/family/learners/{learner}/translations'
        .replaceAll(
          '{'
          r'learner'
          '}',
          learner.toString(),
        );
    final _options = Options(
      method: r'POST',
      headers: <String, dynamic>{
        if (xSchoolId != null) r'X-School-Id': xSchoolId,
        if (xAccountId != null) r'X-Account-Id': xAccountId,
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
      _bodyData = jsonEncode(schoolAssistanceTranslationRequest);
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

    SchoolAssistanceTurnResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              SchoolAssistanceTurnResponse,
              SchoolAssistanceTurnResponse
            >(rawData, 'SchoolAssistanceTurnResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<SchoolAssistanceTurnResponse>(
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

  /// publishSchoolAssistanceDocument
  /// Fresh selected-school/account/guardian or assigned office authority before private retrieval and every replay. Body-only exact UUID writes; source changes retire prepared reviews. External inference remains disabled until qualified. Originals are preserved; translations never send messages or publish marks.
  ///
  /// Parameters:
  /// * [schoolAssistancePublishRequest]
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [SchoolAssistanceDocumentResultResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<SchoolAssistanceDocumentResultResponse>>
  publishSchoolAssistanceDocument({
    required SchoolAssistancePublishRequest schoolAssistancePublishRequest,
    int? xSchoolId,
    int? xAccountId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/school-assistance/office/documents';
    final _options = Options(
      method: r'POST',
      headers: <String, dynamic>{
        if (xSchoolId != null) r'X-School-Id': xSchoolId,
        if (xAccountId != null) r'X-Account-Id': xAccountId,
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
      _bodyData = jsonEncode(schoolAssistancePublishRequest);
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

    SchoolAssistanceDocumentResultResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              SchoolAssistanceDocumentResultResponse,
              SchoolAssistanceDocumentResultResponse
            >(
              rawData,
              'SchoolAssistanceDocumentResultResponse',
              growable: true,
            );
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<SchoolAssistanceDocumentResultResponse>(
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

  /// reassignSchoolAssistanceThread
  /// Fresh selected-school/account/guardian or assigned office authority before private retrieval and every replay. Body-only exact UUID writes; source changes retire prepared reviews. External inference remains disabled until qualified. Originals are preserved; translations never send messages or publish marks.
  ///
  /// Parameters:
  /// * [thread]
  /// * [schoolAssistanceReassignRequest]
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [SchoolAssistanceThreadResultResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<SchoolAssistanceThreadResultResponse>>
  reassignSchoolAssistanceThread({
    required String thread,
    required SchoolAssistanceReassignRequest schoolAssistanceReassignRequest,
    int? xSchoolId,
    int? xAccountId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/school-assistance/office/threads/{thread}/reassign'
        .replaceAll(
          '{'
          r'thread'
          '}',
          thread.toString(),
        );
    final _options = Options(
      method: r'POST',
      headers: <String, dynamic>{
        if (xSchoolId != null) r'X-School-Id': xSchoolId,
        if (xAccountId != null) r'X-Account-Id': xAccountId,
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
      _bodyData = jsonEncode(schoolAssistanceReassignRequest);
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

    SchoolAssistanceThreadResultResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              SchoolAssistanceThreadResultResponse,
              SchoolAssistanceThreadResultResponse
            >(rawData, 'SchoolAssistanceThreadResultResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<SchoolAssistanceThreadResultResponse>(
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

  /// replySchoolAssistanceThread
  /// Fresh selected-school/account/guardian or assigned office authority before private retrieval and every replay. Body-only exact UUID writes; source changes retire prepared reviews. External inference remains disabled until qualified. Originals are preserved; translations never send messages or publish marks.
  ///
  /// Parameters:
  /// * [thread]
  /// * [schoolAssistanceReplyRequest]
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [SchoolAssistanceThreadResultResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<SchoolAssistanceThreadResultResponse>>
  replySchoolAssistanceThread({
    required String thread,
    required SchoolAssistanceReplyRequest schoolAssistanceReplyRequest,
    int? xSchoolId,
    int? xAccountId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/school-assistance/threads/{thread}/reply'.replaceAll(
      '{'
      r'thread'
      '}',
      thread.toString(),
    );
    final _options = Options(
      method: r'POST',
      headers: <String, dynamic>{
        if (xSchoolId != null) r'X-School-Id': xSchoolId,
        if (xAccountId != null) r'X-Account-Id': xAccountId,
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
      _bodyData = jsonEncode(schoolAssistanceReplyRequest);
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

    SchoolAssistanceThreadResultResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              SchoolAssistanceThreadResultResponse,
              SchoolAssistanceThreadResultResponse
            >(rawData, 'SchoolAssistanceThreadResultResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<SchoolAssistanceThreadResultResponse>(
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

  /// runSchoolAssistanceTurn
  /// Fresh selected-school/account/guardian or assigned office authority before private retrieval and every replay. Body-only exact UUID writes; source changes retire prepared reviews. External inference remains disabled until qualified. Originals are preserved; translations never send messages or publish marks.
  ///
  /// Parameters:
  /// * [turn]
  /// * [schoolAssistanceRunRequest]
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [SchoolAssistanceTurnResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<SchoolAssistanceTurnResponse>> runSchoolAssistanceTurn({
    required String turn,
    required SchoolAssistanceRunRequest schoolAssistanceRunRequest,
    int? xSchoolId,
    int? xAccountId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/school-assistance/turns/{turn}/run'.replaceAll(
      '{'
      r'turn'
      '}',
      turn.toString(),
    );
    final _options = Options(
      method: r'POST',
      headers: <String, dynamic>{
        if (xSchoolId != null) r'X-School-Id': xSchoolId,
        if (xAccountId != null) r'X-Account-Id': xAccountId,
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
      _bodyData = jsonEncode(schoolAssistanceRunRequest);
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

    SchoolAssistanceTurnResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              SchoolAssistanceTurnResponse,
              SchoolAssistanceTurnResponse
            >(rawData, 'SchoolAssistanceTurnResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<SchoolAssistanceTurnResponse>(
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

  /// withdrawSchoolAssistanceDocument
  /// Fresh selected-school/account/guardian or assigned office authority before private retrieval and every replay. Body-only exact UUID writes; source changes retire prepared reviews. External inference remains disabled until qualified. Originals are preserved; translations never send messages or publish marks.
  ///
  /// Parameters:
  /// * [document]
  /// * [schoolAssistanceWithdrawRequest]
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [SchoolAssistanceDocumentResultResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<SchoolAssistanceDocumentResultResponse>>
  withdrawSchoolAssistanceDocument({
    required String document,
    required SchoolAssistanceWithdrawRequest schoolAssistanceWithdrawRequest,
    int? xSchoolId,
    int? xAccountId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/school-assistance/office/documents/{document}/withdraw'
        .replaceAll(
          '{'
          r'document'
          '}',
          document.toString(),
        );
    final _options = Options(
      method: r'POST',
      headers: <String, dynamic>{
        if (xSchoolId != null) r'X-School-Id': xSchoolId,
        if (xAccountId != null) r'X-Account-Id': xAccountId,
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
      _bodyData = jsonEncode(schoolAssistanceWithdrawRequest);
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

    SchoolAssistanceDocumentResultResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              SchoolAssistanceDocumentResultResponse,
              SchoolAssistanceDocumentResultResponse
            >(
              rawData,
              'SchoolAssistanceDocumentResultResponse',
              growable: true,
            );
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<SchoolAssistanceDocumentResultResponse>(
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
