//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

import 'dart:async';

// ignore: unused_import
import 'dart:convert';
import 'package:eschool_api/src/deserialize.dart';
import 'package:dio/dio.dart';

import 'package:eschool_api/src/model/error_response.dart';
import 'package:eschool_api/src/model/learning_path_action_input.dart';
import 'package:eschool_api/src/model/learning_path_answer_input.dart';
import 'package:eschool_api/src/model/learning_path_family_summary_response.dart';
import 'package:eschool_api/src/model/learning_path_group_response.dart';
import 'package:eschool_api/src/model/learning_path_response.dart';

class LearningPathApi {
  final Dio _dio;

  const LearningPathApi(this._dio);

  /// actOnLearningPath
  /// Own approved learner only, with daily practice and mastery on for the learner&#39;s stage. One path per learner and learning group: gap, teach, practise, a server-graded check, then a review scheduled with FSRS. Approved answers never leave the server; an explanation appears only after the learner answers. Writes accept body fields only; an unknown outcome retries the same operation, and a step action names the revision it expects, so a double submit advances once.
  ///
  /// Parameters:
  /// * [learner]
  /// * [group]
  /// * [learningPathActionInput]
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [LearningPathResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<LearningPathResponse>> actOnLearningPath({
    required int learner,
    required int group,
    required LearningPathActionInput learningPathActionInput,
    int? xAccountId,
    int? xSchoolId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/learning/paths/learners/{learner}/groups/{group}/actions'
        .replaceAll(
          '{'
          r'learner'
          '}',
          learner.toString(),
        )
        .replaceAll(
          '{'
          r'group'
          '}',
          group.toString(),
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
      _bodyData = jsonEncode(learningPathActionInput);
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

    LearningPathResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<LearningPathResponse, LearningPathResponse>(
              rawData,
              'LearningPathResponse',
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

    return Response<LearningPathResponse>(
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

  /// answerLearningPathItem
  /// Own approved learner only, with daily practice and mastery on for the learner&#39;s stage. One path per learner and learning group: gap, teach, practise, a server-graded check, then a review scheduled with FSRS. Approved answers never leave the server; an explanation appears only after the learner answers. Writes accept body fields only; an unknown outcome retries the same operation, and a step action names the revision it expects, so a double submit advances once.
  ///
  /// Parameters:
  /// * [learner]
  /// * [group]
  /// * [item]
  /// * [learningPathAnswerInput]
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [LearningPathResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<LearningPathResponse>> answerLearningPathItem({
    required int learner,
    required int group,
    required int item,
    required LearningPathAnswerInput learningPathAnswerInput,
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
        r'/learning/paths/learners/{learner}/groups/{group}/items/{item}/answer'
            .replaceAll(
              '{'
              r'learner'
              '}',
              learner.toString(),
            )
            .replaceAll(
              '{'
              r'group'
              '}',
              group.toString(),
            )
            .replaceAll(
              '{'
              r'item'
              '}',
              item.toString(),
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
      _bodyData = jsonEncode(learningPathAnswerInput);
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

    LearningPathResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<LearningPathResponse, LearningPathResponse>(
              rawData,
              'LearningPathResponse',
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

    return Response<LearningPathResponse>(
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

  /// getLearningPath
  /// Own approved learner only, with daily practice and mastery on for the learner&#39;s stage. One path per learner and learning group: gap, teach, practise, a server-graded check, then a review scheduled with FSRS. Approved answers never leave the server; an explanation appears only after the learner answers. Writes accept body fields only; an unknown outcome retries the same operation, and a step action names the revision it expects, so a double submit advances once.
  ///
  /// Parameters:
  /// * [learner]
  /// * [group]
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [LearningPathResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<LearningPathResponse>> getLearningPath({
    required int learner,
    required int group,
    int? xAccountId,
    int? xSchoolId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/learning/paths/learners/{learner}/groups/{group}'
        .replaceAll(
          '{'
          r'learner'
          '}',
          learner.toString(),
        )
        .replaceAll(
          '{'
          r'group'
          '}',
          group.toString(),
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

    LearningPathResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<LearningPathResponse, LearningPathResponse>(
              rawData,
              'LearningPathResponse',
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

    return Response<LearningPathResponse>(
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

  /// getLearningPathFamilySummary
  /// A verified guardian of an approved learner: a plain summary of the child&#39;s learning paths this school year, without questions, answers or tutor conversations.
  ///
  /// Parameters:
  /// * [learner]
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [LearningPathFamilySummaryResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<LearningPathFamilySummaryResponse>>
  getLearningPathFamilySummary({
    required int learner,
    int? xAccountId,
    int? xSchoolId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/learning/paths/learners/{learner}/summary'.replaceAll(
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

    final _response = await _dio.request<Object>(
      _path,
      options: _options,
      cancelToken: cancelToken,
      onSendProgress: onSendProgress,
      onReceiveProgress: onReceiveProgress,
    );

    LearningPathFamilySummaryResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              LearningPathFamilySummaryResponse,
              LearningPathFamilySummaryResponse
            >(rawData, 'LearningPathFamilySummaryResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<LearningPathFamilySummaryResponse>(
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

  /// getLearningPathGroup
  /// A teacher (or academic leader) who may read the group&#39;s marks: which step each current learner is on, in learner order, with no scores, levels or rankings.
  ///
  /// Parameters:
  /// * [group]
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [page]
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [LearningPathGroupResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<LearningPathGroupResponse>> getLearningPathGroup({
    required int group,
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
    final _path = r'/learning/paths/groups/{group}/learners'.replaceAll(
      '{'
      r'group'
      '}',
      group.toString(),
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

    final _queryParameters = <String, dynamic>{if (page != null) r'page': page};

    final _response = await _dio.request<Object>(
      _path,
      options: _options,
      queryParameters: _queryParameters,
      cancelToken: cancelToken,
      onSendProgress: onSendProgress,
      onReceiveProgress: onReceiveProgress,
    );

    LearningPathGroupResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<LearningPathGroupResponse, LearningPathGroupResponse>(
              rawData,
              'LearningPathGroupResponse',
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

    return Response<LearningPathGroupResponse>(
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
