//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

import 'dart:async';

// ignore: unused_import
import 'dart:convert';
import 'package:eschool_api/src/deserialize.dart';
import 'package:dio/dio.dart';

import 'package:eschool_api/src/model/error_response.dart';
import 'package:eschool_api/src/model/weekly_digest_access_response.dart';
import 'package:eschool_api/src/model/weekly_digest_detail_response.dart';
import 'package:eschool_api/src/model/weekly_digest_page_response.dart';
import 'package:eschool_api/src/model/weekly_digest_preference_input.dart';
import 'package:eschool_api/src/model/weekly_digest_reference_response.dart';
import 'package:eschool_api/src/model/weekly_digest_template_input.dart';
import 'package:eschool_api/src/model/weekly_digest_template_page_response.dart';
import 'package:eschool_api/src/model/weekly_digest_template_review.dart';

class WeeklyDigestsApi {
  final Dio _dio;

  const WeeklyDigestsApi(this._dio);

  /// createWeeklyDigestTemplate
  /// Parent-only per-child school evidence, never clinical notes or risk labels. Current account, school, guardian relationship and learner source authorize each read and notification delivery. School managers review immutable language versions; an unapproved preferred language never silently falls back. Exact mutation retries retain their operation identifier.
  ///
  /// Parameters:
  /// * [weeklyDigestTemplateInput]
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [WeeklyDigestReferenceResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<WeeklyDigestReferenceResponse>> createWeeklyDigestTemplate({
    required WeeklyDigestTemplateInput weeklyDigestTemplateInput,
    int? xSchoolId,
    int? xAccountId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/digests/templates';
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
      _bodyData = jsonEncode(weeklyDigestTemplateInput);
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

    WeeklyDigestReferenceResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              WeeklyDigestReferenceResponse,
              WeeklyDigestReferenceResponse
            >(rawData, 'WeeklyDigestReferenceResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<WeeklyDigestReferenceResponse>(
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

  /// getWeeklyDigest
  /// Parent-only per-child school evidence, never clinical notes or risk labels. Current account, school, guardian relationship and learner source authorize each read and notification delivery. School managers review immutable language versions; an unapproved preferred language never silently falls back. Exact mutation retries retain their operation identifier.
  ///
  /// Parameters:
  /// * [digest]
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [WeeklyDigestDetailResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<WeeklyDigestDetailResponse>> getWeeklyDigest({
    required int digest,
    int? xSchoolId,
    int? xAccountId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/digests/{digest}'.replaceAll(
      '{'
      r'digest'
      '}',
      digest.toString(),
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

    final _response = await _dio.request<Object>(
      _path,
      options: _options,
      cancelToken: cancelToken,
      onSendProgress: onSendProgress,
      onReceiveProgress: onReceiveProgress,
    );

    WeeklyDigestDetailResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<WeeklyDigestDetailResponse, WeeklyDigestDetailResponse>(
              rawData,
              'WeeklyDigestDetailResponse',
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

    return Response<WeeklyDigestDetailResponse>(
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

  /// getWeeklyDigestAccess
  /// Parent-only per-child school evidence, never clinical notes or risk labels. Current account, school, guardian relationship and learner source authorize each read and notification delivery. School managers review immutable language versions; an unapproved preferred language never silently falls back. Exact mutation retries retain their operation identifier.
  ///
  /// Parameters:
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [WeeklyDigestAccessResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<WeeklyDigestAccessResponse>> getWeeklyDigestAccess({
    int? xSchoolId,
    int? xAccountId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/digests/access';
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

    final _response = await _dio.request<Object>(
      _path,
      options: _options,
      cancelToken: cancelToken,
      onSendProgress: onSendProgress,
      onReceiveProgress: onReceiveProgress,
    );

    WeeklyDigestAccessResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<WeeklyDigestAccessResponse, WeeklyDigestAccessResponse>(
              rawData,
              'WeeklyDigestAccessResponse',
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

    return Response<WeeklyDigestAccessResponse>(
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

  /// getWeeklyDigestTemplates
  /// Parent-only per-child school evidence, never clinical notes or risk labels. Current account, school, guardian relationship and learner source authorize each read and notification delivery. School managers review immutable language versions; an unapproved preferred language never silently falls back. Exact mutation retries retain their operation identifier.
  ///
  /// Parameters:
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [WeeklyDigestTemplatePageResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<WeeklyDigestTemplatePageResponse>> getWeeklyDigestTemplates({
    int? xSchoolId,
    int? xAccountId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/digests/templates';
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

    final _response = await _dio.request<Object>(
      _path,
      options: _options,
      cancelToken: cancelToken,
      onSendProgress: onSendProgress,
      onReceiveProgress: onReceiveProgress,
    );

    WeeklyDigestTemplatePageResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              WeeklyDigestTemplatePageResponse,
              WeeklyDigestTemplatePageResponse
            >(rawData, 'WeeklyDigestTemplatePageResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<WeeklyDigestTemplatePageResponse>(
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

  /// getWeeklyDigests
  /// Parent-only per-child school evidence, never clinical notes or risk labels. Current account, school, guardian relationship and learner source authorize each read and notification delivery. School managers review immutable language versions; an unapproved preferred language never silently falls back. Exact mutation retries retain their operation identifier.
  ///
  /// Parameters:
  /// * [learner]
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [before]
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [WeeklyDigestPageResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<WeeklyDigestPageResponse>> getWeeklyDigests({
    required int learner,
    int? xSchoolId,
    int? xAccountId,
    int? before,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/digests/learners/{learner}/weeks'.replaceAll(
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

    final _queryParameters = <String, dynamic>{
      if (before != null) r'before': before,
    };

    final _response = await _dio.request<Object>(
      _path,
      options: _options,
      queryParameters: _queryParameters,
      cancelToken: cancelToken,
      onSendProgress: onSendProgress,
      onReceiveProgress: onReceiveProgress,
    );

    WeeklyDigestPageResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<WeeklyDigestPageResponse, WeeklyDigestPageResponse>(
              rawData,
              'WeeklyDigestPageResponse',
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

    return Response<WeeklyDigestPageResponse>(
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

  /// reviewWeeklyDigestTemplate
  /// Parent-only per-child school evidence, never clinical notes or risk labels. Current account, school, guardian relationship and learner source authorize each read and notification delivery. School managers review immutable language versions; an unapproved preferred language never silently falls back. Exact mutation retries retain their operation identifier.
  ///
  /// Parameters:
  /// * [template]
  /// * [weeklyDigestTemplateReview]
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [WeeklyDigestReferenceResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<WeeklyDigestReferenceResponse>> reviewWeeklyDigestTemplate({
    required int template,
    required WeeklyDigestTemplateReview weeklyDigestTemplateReview,
    int? xSchoolId,
    int? xAccountId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/digests/templates/{template}/review'.replaceAll(
      '{'
      r'template'
      '}',
      template.toString(),
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
      _bodyData = jsonEncode(weeklyDigestTemplateReview);
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

    WeeklyDigestReferenceResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              WeeklyDigestReferenceResponse,
              WeeklyDigestReferenceResponse
            >(rawData, 'WeeklyDigestReferenceResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<WeeklyDigestReferenceResponse>(
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

  /// setWeeklyDigestPreference
  /// Parent-only per-child school evidence, never clinical notes or risk labels. Current account, school, guardian relationship and learner source authorize each read and notification delivery. School managers review immutable language versions; an unapproved preferred language never silently falls back. Exact mutation retries retain their operation identifier.
  ///
  /// Parameters:
  /// * [weeklyDigestPreferenceInput]
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [WeeklyDigestReferenceResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<WeeklyDigestReferenceResponse>> setWeeklyDigestPreference({
    required WeeklyDigestPreferenceInput weeklyDigestPreferenceInput,
    int? xSchoolId,
    int? xAccountId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/digests/preferences';
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
      _bodyData = jsonEncode(weeklyDigestPreferenceInput);
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

    WeeklyDigestReferenceResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              WeeklyDigestReferenceResponse,
              WeeklyDigestReferenceResponse
            >(rawData, 'WeeklyDigestReferenceResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<WeeklyDigestReferenceResponse>(
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
