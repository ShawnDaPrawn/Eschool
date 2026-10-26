//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

import 'dart:async';

// ignore: unused_import
import 'dart:convert';
import 'package:eschool_api/src/deserialize.dart';
import 'package:dio/dio.dart';

import 'package:eschool_api/src/model/error_response.dart';
import 'package:eschool_api/src/model/family_gate_collected_response.dart';
import 'package:eschool_api/src/model/family_gate_collection.dart';
import 'package:eschool_api/src/model/family_gate_lookup_response.dart';
import 'package:eschool_api/src/model/family_gate_pass_page_response.dart';
import 'package:eschool_api/src/model/family_gate_pass_response.dart';
import 'package:eschool_api/src/model/family_gate_request.dart';
import 'package:eschool_api/src/model/family_meeting_booking.dart';
import 'package:eschool_api/src/model/family_meeting_create.dart';
import 'package:eschool_api/src/model/family_meeting_page_response.dart';
import 'package:eschool_api/src/model/family_meeting_slot_response.dart';
import 'package:eschool_api/src/model/family_visit_cancelled_response.dart';
import 'package:eschool_api/src/model/family_visit_configuration_response.dart';
import 'package:eschool_api/src/model/family_visit_revision.dart';
import 'package:eschool_api/src/model/family_visit_rules_response.dart';
import 'package:eschool_api/src/model/family_visit_rules_write.dart';

class FamilyVisitsApi {
  final Dio _dio;

  const FamilyVisitsApi(this._dio);

  /// approveFamilyGatePass
  /// Private fresh school/account/guardian or named staff authority. Wall-clock writes use the school timezone and reject ambiguous DST instants. Exact operation UUID/body retries return their original receipt only after fresh authorisation. Gate collection requires a current delegated reader, matching recent online learner-card scan, staff approval and explicit collector check. No offline collection approval.
  ///
  /// Parameters:
  /// * [pass]
  /// * [familyVisitRevision]
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [FamilyGatePassResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<FamilyGatePassResponse>> approveFamilyGatePass({
    required String pass,
    required FamilyVisitRevision familyVisitRevision,
    int? xSchoolId,
    int? xAccountId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/family-visits/passes/{pass}/approve'.replaceAll(
      '{'
      r'pass'
      '}',
      pass.toString(),
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
      _bodyData = jsonEncode(familyVisitRevision);
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

    FamilyGatePassResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<FamilyGatePassResponse, FamilyGatePassResponse>(
              rawData,
              'FamilyGatePassResponse',
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

    return Response<FamilyGatePassResponse>(
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

  /// bookFamilyMeeting
  /// Private fresh school/account/guardian or named staff authority. Wall-clock writes use the school timezone and reject ambiguous DST instants. Exact operation UUID/body retries return their original receipt only after fresh authorisation. Gate collection requires a current delegated reader, matching recent online learner-card scan, staff approval and explicit collector check. No offline collection approval.
  ///
  /// Parameters:
  /// * [slot]
  /// * [familyMeetingBooking]
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [FamilyMeetingSlotResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<FamilyMeetingSlotResponse>> bookFamilyMeeting({
    required String slot,
    required FamilyMeetingBooking familyMeetingBooking,
    int? xSchoolId,
    int? xAccountId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/family-visits/meetings/{slot}/book'.replaceAll(
      '{'
      r'slot'
      '}',
      slot.toString(),
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
      _bodyData = jsonEncode(familyMeetingBooking);
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

    FamilyMeetingSlotResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<FamilyMeetingSlotResponse, FamilyMeetingSlotResponse>(
              rawData,
              'FamilyMeetingSlotResponse',
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

    return Response<FamilyMeetingSlotResponse>(
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

  /// cancelFamilyGatePass
  /// Private fresh school/account/guardian or named staff authority. Wall-clock writes use the school timezone and reject ambiguous DST instants. Exact operation UUID/body retries return their original receipt only after fresh authorisation. Gate collection requires a current delegated reader, matching recent online learner-card scan, staff approval and explicit collector check. No offline collection approval.
  ///
  /// Parameters:
  /// * [pass]
  /// * [familyVisitRevision]
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [FamilyVisitCancelledResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<FamilyVisitCancelledResponse>> cancelFamilyGatePass({
    required String pass,
    required FamilyVisitRevision familyVisitRevision,
    int? xSchoolId,
    int? xAccountId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/family-visits/passes/{pass}/cancel'.replaceAll(
      '{'
      r'pass'
      '}',
      pass.toString(),
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
      _bodyData = jsonEncode(familyVisitRevision);
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

    FamilyVisitCancelledResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              FamilyVisitCancelledResponse,
              FamilyVisitCancelledResponse
            >(rawData, 'FamilyVisitCancelledResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<FamilyVisitCancelledResponse>(
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

  /// cancelFamilyMeeting
  /// Private fresh school/account/guardian or named staff authority. Wall-clock writes use the school timezone and reject ambiguous DST instants. Exact operation UUID/body retries return their original receipt only after fresh authorisation. Gate collection requires a current delegated reader, matching recent online learner-card scan, staff approval and explicit collector check. No offline collection approval.
  ///
  /// Parameters:
  /// * [slot]
  /// * [familyVisitRevision]
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [FamilyVisitCancelledResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<FamilyVisitCancelledResponse>> cancelFamilyMeeting({
    required String slot,
    required FamilyVisitRevision familyVisitRevision,
    int? xSchoolId,
    int? xAccountId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/family-visits/meetings/{slot}/cancel'.replaceAll(
      '{'
      r'slot'
      '}',
      slot.toString(),
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
      _bodyData = jsonEncode(familyVisitRevision);
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

    FamilyVisitCancelledResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              FamilyVisitCancelledResponse,
              FamilyVisitCancelledResponse
            >(rawData, 'FamilyVisitCancelledResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<FamilyVisitCancelledResponse>(
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

  /// cancelFamilyMeetingBooking
  /// Private fresh school/account/guardian or named staff authority. Wall-clock writes use the school timezone and reject ambiguous DST instants. Exact operation UUID/body retries return their original receipt only after fresh authorisation. Gate collection requires a current delegated reader, matching recent online learner-card scan, staff approval and explicit collector check. No offline collection approval.
  ///
  /// Parameters:
  /// * [slot]
  /// * [familyMeetingBooking]
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [FamilyVisitCancelledResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<FamilyVisitCancelledResponse>> cancelFamilyMeetingBooking({
    required String slot,
    required FamilyMeetingBooking familyMeetingBooking,
    int? xSchoolId,
    int? xAccountId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/family-visits/meetings/{slot}/unbook'.replaceAll(
      '{'
      r'slot'
      '}',
      slot.toString(),
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
      _bodyData = jsonEncode(familyMeetingBooking);
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

    FamilyVisitCancelledResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              FamilyVisitCancelledResponse,
              FamilyVisitCancelledResponse
            >(rawData, 'FamilyVisitCancelledResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<FamilyVisitCancelledResponse>(
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

  /// collectFamilyGatePass
  /// Private fresh school/account/guardian or named staff authority. Wall-clock writes use the school timezone and reject ambiguous DST instants. Exact operation UUID/body retries return their original receipt only after fresh authorisation. Gate collection requires a current delegated reader, matching recent online learner-card scan, staff approval and explicit collector check. No offline collection approval.
  ///
  /// Parameters:
  /// * [pass]
  /// * [familyGateCollection]
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [FamilyGateCollectedResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<FamilyGateCollectedResponse>> collectFamilyGatePass({
    required String pass,
    required FamilyGateCollection familyGateCollection,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/card-device/gate-passes/{pass}/collect'.replaceAll(
      '{'
      r'pass'
      '}',
      pass.toString(),
    );
    final _options = Options(
      method: r'POST',
      headers: <String, dynamic>{...?headers},
      extra: <String, dynamic>{
        'secure': <Map<String, String>>[
          {'type': 'http', 'scheme': 'bearer', 'name': 'CardDeviceCredential'},
        ],
        ...?extra,
      },
      contentType: 'application/json',
      validateStatus: validateStatus,
    );

    dynamic _bodyData;

    try {
      _bodyData = jsonEncode(familyGateCollection);
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

    FamilyGateCollectedResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              FamilyGateCollectedResponse,
              FamilyGateCollectedResponse
            >(rawData, 'FamilyGateCollectedResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<FamilyGateCollectedResponse>(
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

  /// createFamilyMeeting
  /// Private fresh school/account/guardian or named staff authority. Wall-clock writes use the school timezone and reject ambiguous DST instants. Exact operation UUID/body retries return their original receipt only after fresh authorisation. Gate collection requires a current delegated reader, matching recent online learner-card scan, staff approval and explicit collector check. No offline collection approval.
  ///
  /// Parameters:
  /// * [familyMeetingCreate]
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [FamilyMeetingSlotResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<FamilyMeetingSlotResponse>> createFamilyMeeting({
    required FamilyMeetingCreate familyMeetingCreate,
    int? xSchoolId,
    int? xAccountId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/family-visits/meetings';
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
      _bodyData = jsonEncode(familyMeetingCreate);
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

    FamilyMeetingSlotResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<FamilyMeetingSlotResponse, FamilyMeetingSlotResponse>(
              rawData,
              'FamilyMeetingSlotResponse',
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

    return Response<FamilyMeetingSlotResponse>(
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

  /// getFamilyGatePasses
  /// Private fresh school/account/guardian or named staff authority. Wall-clock writes use the school timezone and reject ambiguous DST instants. Exact operation UUID/body retries return their original receipt only after fresh authorisation. Gate collection requires a current delegated reader, matching recent online learner-card scan, staff approval and explicit collector check. No offline collection approval.
  ///
  /// Parameters:
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [learnerId]
  /// * [page]
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [FamilyGatePassPageResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<FamilyGatePassPageResponse>> getFamilyGatePasses({
    int? xSchoolId,
    int? xAccountId,
    int? learnerId,
    int? page,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/family-visits/passes';
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
      if (learnerId != null) r'learner_id': learnerId,
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

    FamilyGatePassPageResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<FamilyGatePassPageResponse, FamilyGatePassPageResponse>(
              rawData,
              'FamilyGatePassPageResponse',
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

    return Response<FamilyGatePassPageResponse>(
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

  /// getFamilyMeetings
  /// Private fresh school/account/guardian or named staff authority. Wall-clock writes use the school timezone and reject ambiguous DST instants. Exact operation UUID/body retries return their original receipt only after fresh authorisation. Gate collection requires a current delegated reader, matching recent online learner-card scan, staff approval and explicit collector check. No offline collection approval.
  ///
  /// Parameters:
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [learnerId]
  /// * [page]
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [FamilyMeetingPageResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<FamilyMeetingPageResponse>> getFamilyMeetings({
    int? xSchoolId,
    int? xAccountId,
    int? learnerId,
    int? page,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/family-visits/meetings';
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
      if (learnerId != null) r'learner_id': learnerId,
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

    FamilyMeetingPageResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<FamilyMeetingPageResponse, FamilyMeetingPageResponse>(
              rawData,
              'FamilyMeetingPageResponse',
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

    return Response<FamilyMeetingPageResponse>(
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

  /// getFamilyVisitConfiguration
  /// Private fresh school/account/guardian or named staff authority. Wall-clock writes use the school timezone and reject ambiguous DST instants. Exact operation UUID/body retries return their original receipt only after fresh authorisation. Gate collection requires a current delegated reader, matching recent online learner-card scan, staff approval and explicit collector check. No offline collection approval.
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
  /// Returns a [Future] containing a [Response] with a [FamilyVisitConfigurationResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<FamilyVisitConfigurationResponse>>
  getFamilyVisitConfiguration({
    int? xSchoolId,
    int? xAccountId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/family-visits/configuration';
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

    FamilyVisitConfigurationResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              FamilyVisitConfigurationResponse,
              FamilyVisitConfigurationResponse
            >(rawData, 'FamilyVisitConfigurationResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<FamilyVisitConfigurationResponse>(
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

  /// getScannedFamilyGatePasses
  /// Private fresh school/account/guardian or named staff authority. Wall-clock writes use the school timezone and reject ambiguous DST instants. Exact operation UUID/body retries return their original receipt only after fresh authorisation. Gate collection requires a current delegated reader, matching recent online learner-card scan, staff approval and explicit collector check. No offline collection approval.
  ///
  /// Parameters:
  /// * [scan]
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [FamilyGateLookupResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<FamilyGateLookupResponse>> getScannedFamilyGatePasses({
    required String scan,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/card-device/scans/{scan}/gate-passes'.replaceAll(
      '{'
      r'scan'
      '}',
      scan.toString(),
    );
    final _options = Options(
      method: r'GET',
      headers: <String, dynamic>{...?headers},
      extra: <String, dynamic>{
        'secure': <Map<String, String>>[
          {'type': 'http', 'scheme': 'bearer', 'name': 'CardDeviceCredential'},
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

    FamilyGateLookupResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<FamilyGateLookupResponse, FamilyGateLookupResponse>(
              rawData,
              'FamilyGateLookupResponse',
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

    return Response<FamilyGateLookupResponse>(
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

  /// requestFamilyGatePass
  /// Private fresh school/account/guardian or named staff authority. Wall-clock writes use the school timezone and reject ambiguous DST instants. Exact operation UUID/body retries return their original receipt only after fresh authorisation. Gate collection requires a current delegated reader, matching recent online learner-card scan, staff approval and explicit collector check. No offline collection approval.
  ///
  /// Parameters:
  /// * [familyGateRequest]
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [FamilyGatePassResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<FamilyGatePassResponse>> requestFamilyGatePass({
    required FamilyGateRequest familyGateRequest,
    int? xSchoolId,
    int? xAccountId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/family-visits/passes';
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
      _bodyData = jsonEncode(familyGateRequest);
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

    FamilyGatePassResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<FamilyGatePassResponse, FamilyGatePassResponse>(
              rawData,
              'FamilyGatePassResponse',
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

    return Response<FamilyGatePassResponse>(
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

  /// saveFamilyVisitRules
  /// Private fresh school/account/guardian or named staff authority. Wall-clock writes use the school timezone and reject ambiguous DST instants. Exact operation UUID/body retries return their original receipt only after fresh authorisation. Gate collection requires a current delegated reader, matching recent online learner-card scan, staff approval and explicit collector check. No offline collection approval.
  ///
  /// Parameters:
  /// * [familyVisitRulesWrite]
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [FamilyVisitRulesResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<FamilyVisitRulesResponse>> saveFamilyVisitRules({
    required FamilyVisitRulesWrite familyVisitRulesWrite,
    int? xSchoolId,
    int? xAccountId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/family-visits/rules';
    final _options = Options(
      method: r'PUT',
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
      _bodyData = jsonEncode(familyVisitRulesWrite);
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

    FamilyVisitRulesResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<FamilyVisitRulesResponse, FamilyVisitRulesResponse>(
              rawData,
              'FamilyVisitRulesResponse',
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

    return Response<FamilyVisitRulesResponse>(
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
