//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

import 'dart:async';

// ignore: unused_import
import 'dart:convert';
import 'package:eschool_api/src/deserialize.dart';
import 'package:dio/dio.dart';

import 'package:eschool_api/src/model/error_response.dart';
import 'package:eschool_api/src/model/foundation_eligibility_input.dart';
import 'package:eschool_api/src/model/foundation_grant_input.dart';
import 'package:eschool_api/src/model/foundation_operation_input.dart';
import 'package:eschool_api/src/model/foundation_registration_input.dart';
import 'package:eschool_api/src/model/foundation_review_input.dart';
import 'package:eschool_api/src/model/foundation_select_input.dart';
import 'package:eschool_api/src/model/get_foundation_configuration_response.dart';
import 'package:eschool_api/src/model/get_foundation_learner_context_response.dart';
import 'package:eschool_api/src/model/get_foundation_practice_response.dart';
import 'package:eschool_api/src/model/grant_foundation_authorization_response.dart';
import 'package:eschool_api/src/model/issue_foundation_practice_response.dart';
import 'package:eschool_api/src/model/leave_foundation_learner_response.dart';
import 'package:eschool_api/src/model/register_foundation_tablet_response.dart';
import 'package:eschool_api/src/model/review_foundation_practice_response.dart';
import 'package:eschool_api/src/model/revoke_foundation_authorization_response.dart';
import 'package:eschool_api/src/model/revoke_foundation_tablet_response.dart';
import 'package:eschool_api/src/model/select_foundation_learner_response.dart';
import 'package:eschool_api/src/model/set_foundation_eligibility_response.dart';

class FoundationClassroomApi {
  final Dio _dio;

  const FoundationClassroomApi(this._dio);

  /// getFoundationConfiguration
  /// Fresh school, manager/Teacher allocation, eligibility, device issuer, guardian approval and current card authority are checked before data, writes and receipt replay. No ordinary user authentication or gate scan is created. Private writes accept body fields only and reject unknown fields; uncertain writes retry the same operation UUID. Existing short delegations validate classroom credentials as reads, so read-only card inventory does not disable independently writable daily practice; new registration and Teacher grants still require writable card inventory.
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
  /// Returns a [Future] containing a [Response] with a [GetFoundationConfigurationResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<GetFoundationConfigurationResponse>>
  getFoundationConfiguration({
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
    final _path = r'/foundation-classroom/configuration';
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

    GetFoundationConfigurationResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              GetFoundationConfigurationResponse,
              GetFoundationConfigurationResponse
            >(rawData, 'GetFoundationConfigurationResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<GetFoundationConfigurationResponse>(
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

  /// getFoundationLearnerContext
  /// Fresh school, manager/Teacher allocation, eligibility, device issuer, guardian approval and current card authority are checked before data, writes and receipt replay. No ordinary user authentication or gate scan is created. Private writes accept body fields only and reject unknown fields; uncertain writes retry the same operation UUID. Existing short delegations validate classroom credentials as reads, so read-only card inventory does not disable independently writable daily practice; new registration and Teacher grants still require writable card inventory.
  ///
  /// Parameters:
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [GetFoundationLearnerContextResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<GetFoundationLearnerContextResponse>>
  getFoundationLearnerContext({
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/foundation-learner/context';
    final _options = Options(
      method: r'GET',
      headers: <String, dynamic>{...?headers},
      extra: <String, dynamic>{
        'secure': <Map<String, String>>[
          {'type': 'http', 'scheme': 'bearer', 'name': 'foundationLearnerAuth'},
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

    GetFoundationLearnerContextResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              GetFoundationLearnerContextResponse,
              GetFoundationLearnerContextResponse
            >(rawData, 'GetFoundationLearnerContextResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<GetFoundationLearnerContextResponse>(
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

  /// getFoundationPractice
  /// Fresh school, manager/Teacher allocation, eligibility, device issuer, guardian approval and current card authority are checked before data, writes and receipt replay. No ordinary user authentication or gate scan is created. Private writes accept body fields only and reject unknown fields; uncertain writes retry the same operation UUID. Existing short delegations validate classroom credentials as reads, so read-only card inventory does not disable independently writable daily practice; new registration and Teacher grants still require writable card inventory.
  ///
  /// Parameters:
  /// * [set_]
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [GetFoundationPracticeResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<GetFoundationPracticeResponse>> getFoundationPractice({
    required int set_,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/foundation-learner/practice/{set}'.replaceAll(
      '{'
      r'set'
      '}',
      set_.toString(),
    );
    final _options = Options(
      method: r'GET',
      headers: <String, dynamic>{...?headers},
      extra: <String, dynamic>{
        'secure': <Map<String, String>>[
          {'type': 'http', 'scheme': 'bearer', 'name': 'foundationLearnerAuth'},
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

    GetFoundationPracticeResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              GetFoundationPracticeResponse,
              GetFoundationPracticeResponse
            >(rawData, 'GetFoundationPracticeResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<GetFoundationPracticeResponse>(
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

  /// grantFoundationAuthorization
  /// Fresh school, manager/Teacher allocation, eligibility, device issuer, guardian approval and current card authority are checked before data, writes and receipt replay. No ordinary user authentication or gate scan is created. Private writes accept body fields only and reject unknown fields; uncertain writes retry the same operation UUID. Existing short delegations validate classroom credentials as reads, so read-only card inventory does not disable independently writable daily practice; new registration and Teacher grants still require writable card inventory.
  ///
  /// Parameters:
  /// * [foundationGrantInput]
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [GrantFoundationAuthorizationResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<GrantFoundationAuthorizationResponse>>
  grantFoundationAuthorization({
    required FoundationGrantInput foundationGrantInput,
    int? xSchoolId,
    int? xAccountId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/foundation-classroom/authorizations';
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
      _bodyData = jsonEncode(foundationGrantInput);
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

    GrantFoundationAuthorizationResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              GrantFoundationAuthorizationResponse,
              GrantFoundationAuthorizationResponse
            >(rawData, 'GrantFoundationAuthorizationResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<GrantFoundationAuthorizationResponse>(
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

  /// issueFoundationPractice
  /// Fresh school, manager/Teacher allocation, eligibility, device issuer, guardian approval and current card authority are checked before data, writes and receipt replay. No ordinary user authentication or gate scan is created. Private writes accept body fields only and reject unknown fields; uncertain writes retry the same operation UUID. Existing short delegations validate classroom credentials as reads, so read-only card inventory does not disable independently writable daily practice; new registration and Teacher grants still require writable card inventory.
  ///
  /// Parameters:
  /// * [foundationOperationInput]
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [IssueFoundationPracticeResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<IssueFoundationPracticeResponse>> issueFoundationPractice({
    required FoundationOperationInput foundationOperationInput,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/foundation-learner/practice';
    final _options = Options(
      method: r'POST',
      headers: <String, dynamic>{...?headers},
      extra: <String, dynamic>{
        'secure': <Map<String, String>>[
          {'type': 'http', 'scheme': 'bearer', 'name': 'foundationLearnerAuth'},
        ],
        ...?extra,
      },
      contentType: 'application/json',
      validateStatus: validateStatus,
    );

    dynamic _bodyData;

    try {
      _bodyData = jsonEncode(foundationOperationInput);
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

    IssueFoundationPracticeResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              IssueFoundationPracticeResponse,
              IssueFoundationPracticeResponse
            >(rawData, 'IssueFoundationPracticeResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<IssueFoundationPracticeResponse>(
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

  /// leaveFoundationLearner
  /// Fresh school, manager/Teacher allocation, eligibility, device issuer, guardian approval and current card authority are checked before data, writes and receipt replay. No ordinary user authentication or gate scan is created. Private writes accept body fields only and reject unknown fields; uncertain writes retry the same operation UUID. Existing short delegations validate classroom credentials as reads, so read-only card inventory does not disable independently writable daily practice; new registration and Teacher grants still require writable card inventory.
  ///
  /// Parameters:
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [LeaveFoundationLearnerResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<LeaveFoundationLearnerResponse>> leaveFoundationLearner({
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/foundation-learner/leave';
    final _options = Options(
      method: r'POST',
      headers: <String, dynamic>{...?headers},
      extra: <String, dynamic>{
        'secure': <Map<String, String>>[
          {'type': 'http', 'scheme': 'bearer', 'name': 'foundationLearnerAuth'},
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

    LeaveFoundationLearnerResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              LeaveFoundationLearnerResponse,
              LeaveFoundationLearnerResponse
            >(rawData, 'LeaveFoundationLearnerResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<LeaveFoundationLearnerResponse>(
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

  /// registerFoundationTablet
  /// Fresh school, manager/Teacher allocation, eligibility, device issuer, guardian approval and current card authority are checked before data, writes and receipt replay. No ordinary user authentication or gate scan is created. Private writes accept body fields only and reject unknown fields; uncertain writes retry the same operation UUID. Existing short delegations validate classroom credentials as reads, so read-only card inventory does not disable independently writable daily practice; new registration and Teacher grants still require writable card inventory.
  ///
  /// Parameters:
  /// * [foundationRegistrationInput]
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [RegisterFoundationTabletResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<RegisterFoundationTabletResponse>> registerFoundationTablet({
    required FoundationRegistrationInput foundationRegistrationInput,
    int? xSchoolId,
    int? xAccountId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/foundation-classroom/devices';
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
      _bodyData = jsonEncode(foundationRegistrationInput);
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

    RegisterFoundationTabletResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              RegisterFoundationTabletResponse,
              RegisterFoundationTabletResponse
            >(rawData, 'RegisterFoundationTabletResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<RegisterFoundationTabletResponse>(
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

  /// reviewFoundationPractice
  /// Fresh school, manager/Teacher allocation, eligibility, device issuer, guardian approval and current card authority are checked before data, writes and receipt replay. No ordinary user authentication or gate scan is created. Private writes accept body fields only and reject unknown fields; uncertain writes retry the same operation UUID. Existing short delegations validate classroom credentials as reads, so read-only card inventory does not disable independently writable daily practice; new registration and Teacher grants still require writable card inventory.
  ///
  /// Parameters:
  /// * [set_]
  /// * [entry]
  /// * [foundationReviewInput]
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [ReviewFoundationPracticeResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<ReviewFoundationPracticeResponse>> reviewFoundationPractice({
    required int set_,
    required int entry,
    required FoundationReviewInput foundationReviewInput,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/foundation-learner/practice/{set}/entries/{entry}/review'
        .replaceAll(
          '{'
          r'set'
          '}',
          set_.toString(),
        )
        .replaceAll(
          '{'
          r'entry'
          '}',
          entry.toString(),
        );
    final _options = Options(
      method: r'POST',
      headers: <String, dynamic>{...?headers},
      extra: <String, dynamic>{
        'secure': <Map<String, String>>[
          {'type': 'http', 'scheme': 'bearer', 'name': 'foundationLearnerAuth'},
        ],
        ...?extra,
      },
      contentType: 'application/json',
      validateStatus: validateStatus,
    );

    dynamic _bodyData;

    try {
      _bodyData = jsonEncode(foundationReviewInput);
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

    ReviewFoundationPracticeResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              ReviewFoundationPracticeResponse,
              ReviewFoundationPracticeResponse
            >(rawData, 'ReviewFoundationPracticeResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<ReviewFoundationPracticeResponse>(
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

  /// revokeFoundationAuthorization
  /// Fresh school, manager/Teacher allocation, eligibility, device issuer, guardian approval and current card authority are checked before data, writes and receipt replay. No ordinary user authentication or gate scan is created. Private writes accept body fields only and reject unknown fields; uncertain writes retry the same operation UUID. Existing short delegations validate classroom credentials as reads, so read-only card inventory does not disable independently writable daily practice; new registration and Teacher grants still require writable card inventory.
  ///
  /// Parameters:
  /// * [lease]
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [RevokeFoundationAuthorizationResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<RevokeFoundationAuthorizationResponse>>
  revokeFoundationAuthorization({
    required String lease,
    int? xSchoolId,
    int? xAccountId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/foundation-classroom/authorizations/{lease}'.replaceAll(
      '{'
      r'lease'
      '}',
      lease.toString(),
    );
    final _options = Options(
      method: r'DELETE',
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

    RevokeFoundationAuthorizationResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              RevokeFoundationAuthorizationResponse,
              RevokeFoundationAuthorizationResponse
            >(rawData, 'RevokeFoundationAuthorizationResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<RevokeFoundationAuthorizationResponse>(
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

  /// revokeFoundationTablet
  /// Fresh school, manager/Teacher allocation, eligibility, device issuer, guardian approval and current card authority are checked before data, writes and receipt replay. No ordinary user authentication or gate scan is created. Private writes accept body fields only and reject unknown fields; uncertain writes retry the same operation UUID. Existing short delegations validate classroom credentials as reads, so read-only card inventory does not disable independently writable daily practice; new registration and Teacher grants still require writable card inventory.
  ///
  /// Parameters:
  /// * [device]
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [RevokeFoundationTabletResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<RevokeFoundationTabletResponse>> revokeFoundationTablet({
    required String device,
    int? xSchoolId,
    int? xAccountId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/foundation-classroom/devices/{device}'.replaceAll(
      '{'
      r'device'
      '}',
      device.toString(),
    );
    final _options = Options(
      method: r'DELETE',
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

    RevokeFoundationTabletResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              RevokeFoundationTabletResponse,
              RevokeFoundationTabletResponse
            >(rawData, 'RevokeFoundationTabletResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<RevokeFoundationTabletResponse>(
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

  /// selectFoundationLearner
  /// Fresh school, manager/Teacher allocation, eligibility, device issuer, guardian approval and current card authority are checked before data, writes and receipt replay. No ordinary user authentication or gate scan is created. Private writes accept body fields only and reject unknown fields; uncertain writes retry the same operation UUID. Existing short delegations validate classroom credentials as reads, so read-only card inventory does not disable independently writable daily practice; new registration and Teacher grants still require writable card inventory.
  ///
  /// Parameters:
  /// * [foundationSelectInput]
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [SelectFoundationLearnerResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<SelectFoundationLearnerResponse>> selectFoundationLearner({
    required FoundationSelectInput foundationSelectInput,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/foundation-device/select';
    final _options = Options(
      method: r'POST',
      headers: <String, dynamic>{...?headers},
      extra: <String, dynamic>{
        'secure': <Map<String, String>>[
          {'type': 'http', 'scheme': 'bearer', 'name': 'foundationDeviceAuth'},
        ],
        ...?extra,
      },
      contentType: 'application/json',
      validateStatus: validateStatus,
    );

    dynamic _bodyData;

    try {
      _bodyData = jsonEncode(foundationSelectInput);
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

    SelectFoundationLearnerResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              SelectFoundationLearnerResponse,
              SelectFoundationLearnerResponse
            >(rawData, 'SelectFoundationLearnerResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<SelectFoundationLearnerResponse>(
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

  /// setFoundationEligibility
  /// Fresh school, manager/Teacher allocation, eligibility, device issuer, guardian approval and current card authority are checked before data, writes and receipt replay. No ordinary user authentication or gate scan is created. Private writes accept body fields only and reject unknown fields; uncertain writes retry the same operation UUID. Existing short delegations validate classroom credentials as reads, so read-only card inventory does not disable independently writable daily practice; new registration and Teacher grants still require writable card inventory.
  ///
  /// Parameters:
  /// * [group]
  /// * [foundationEligibilityInput]
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [SetFoundationEligibilityResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<SetFoundationEligibilityResponse>> setFoundationEligibility({
    required int group,
    required FoundationEligibilityInput foundationEligibilityInput,
    int? xSchoolId,
    int? xAccountId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/foundation-classroom/groups/{group}/eligibility'
        .replaceAll(
          '{'
          r'group'
          '}',
          group.toString(),
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
      _bodyData = jsonEncode(foundationEligibilityInput);
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

    SetFoundationEligibilityResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              SetFoundationEligibilityResponse,
              SetFoundationEligibilityResponse
            >(rawData, 'SetFoundationEligibilityResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<SetFoundationEligibilityResponse>(
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
