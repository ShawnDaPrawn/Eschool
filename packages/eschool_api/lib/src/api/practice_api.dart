//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

import 'dart:async';

// ignore: unused_import
import 'dart:convert';
import 'package:eschool_api/src/deserialize.dart';
import 'package:dio/dio.dart';

import 'dart:typed_data';
import 'package:eschool_api/src/model/approve_practice_question_response.dart';
import 'package:eschool_api/src/model/create_practice_test_blueprint_response.dart';
import 'package:eschool_api/src/model/error_response.dart';
import 'package:eschool_api/src/model/get_practice_access_response.dart';
import 'package:eschool_api/src/model/get_practice_bank_choices_response.dart';
import 'package:eschool_api/src/model/get_practice_bank_response.dart';
import 'package:eschool_api/src/model/get_practice_daily_set_response.dart';
import 'package:eschool_api/src/model/get_practice_marking_response.dart';
import 'package:eschool_api/src/model/get_practice_motivation_response.dart';
import 'package:eschool_api/src/model/get_practice_quiz_topics_response.dart';
import 'package:eschool_api/src/model/get_practice_test_options_response.dart';
import 'package:eschool_api/src/model/get_practice_test_response.dart';
import 'package:eschool_api/src/model/get_practice_test_results_response.dart';
import 'package:eschool_api/src/model/issue_practice_daily_set_response.dart';
import 'package:eschool_api/src/model/list_practice_test_blueprints_response.dart';
import 'package:eschool_api/src/model/list_practice_tests_response.dart';
import 'package:eschool_api/src/model/mark_practice_answer_response.dart';
import 'package:eschool_api/src/model/practice_approve_input.dart';
import 'package:eschool_api/src/model/practice_goal_input.dart';
import 'package:eschool_api/src/model/practice_issue_input.dart';
import 'package:eschool_api/src/model/practice_mark_input.dart';
import 'package:eschool_api/src/model/practice_quick_quiz_input.dart';
import 'package:eschool_api/src/model/practice_review_input.dart';
import 'package:eschool_api/src/model/practice_test_answers_input.dart';
import 'package:eschool_api/src/model/practice_test_blueprint_input.dart';
import 'package:eschool_api/src/model/practice_test_start_input.dart';
import 'package:eschool_api/src/model/practice_test_submit_input.dart';
import 'package:eschool_api/src/model/practice_test_withdraw_input.dart';
import 'package:eschool_api/src/model/review_practice_entry_response.dart';
import 'package:eschool_api/src/model/revise_practice_question_response.dart';
import 'package:eschool_api/src/model/save_practice_test_answers_response.dart';
import 'package:eschool_api/src/model/start_practice_quick_quiz_response.dart';
import 'package:eschool_api/src/model/start_practice_test_response.dart';
import 'package:eschool_api/src/model/submit_practice_test_response.dart';
import 'package:eschool_api/src/model/upload_practice_diagram_response.dart';
import 'package:eschool_api/src/model/withdraw_practice_test_blueprint_response.dart';

class PracticeApi {
  final Dio _dio;

  const PracticeApi(this._dio);

  /// approvePracticeQuestion
  /// Approved school-content practice only, never official assessment marks. Fresh learner approval/ownership or dated Teacher allocation and all published topic/content sources authorize before reads, writes and encrypted receipt replay. The module defaults off. With X-Practice-Contract 2 sets carry questions only, answers are marked on the server on sync, and the result and explanation follow the recorded answer (Action Plan 3.11); without it the legacy contract returns choice and number questions with approved answers included. Writes accept body fields only; an unknown outcome retries the same operation. Offline synchronization uses the server school calendar day.
  ///
  /// Parameters:
  /// * [group]
  /// * [practiceApproveInput]
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [ApprovePracticeQuestionResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<ApprovePracticeQuestionResponse>> approvePracticeQuestion({
    required int group,
    required PracticeApproveInput practiceApproveInput,
    int? xAccountId,
    int? xSchoolId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/learning/practice/groups/{group}/bank'.replaceAll(
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
      _bodyData = jsonEncode(practiceApproveInput);
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

    ApprovePracticeQuestionResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              ApprovePracticeQuestionResponse,
              ApprovePracticeQuestionResponse
            >(rawData, 'ApprovePracticeQuestionResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<ApprovePracticeQuestionResponse>(
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

  /// createPracticeTestBlueprint
  /// Timed practice tests from the school question bank and the platform library, never official assessment marks. Fresh learner approval/ownership or dated Teacher allocation authorize every read, write and receipt replay; a guardian, another learner or another school is not found. The server holds the deadline: before hand-in a sitting carries prompts and options only, never accepted answers or worked explanations. Writes accept body fields only; an unknown outcome retries the same operation.
  ///
  /// Parameters:
  /// * [group]
  /// * [practiceTestBlueprintInput]
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [CreatePracticeTestBlueprintResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<CreatePracticeTestBlueprintResponse>>
  createPracticeTestBlueprint({
    required int group,
    required PracticeTestBlueprintInput practiceTestBlueprintInput,
    int? xAccountId,
    int? xSchoolId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/learning/practice-tests/groups/{group}/blueprints'
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
      _bodyData = jsonEncode(practiceTestBlueprintInput);
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

    CreatePracticeTestBlueprintResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              CreatePracticeTestBlueprintResponse,
              CreatePracticeTestBlueprintResponse
            >(rawData, 'CreatePracticeTestBlueprintResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<CreatePracticeTestBlueprintResponse>(
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

  /// getPracticeAccess
  /// Approved school-content practice only, never official assessment marks. Fresh learner approval/ownership or dated Teacher allocation and all published topic/content sources authorize before reads, writes and encrypted receipt replay. The module defaults off. With X-Practice-Contract 2 sets carry questions only, answers are marked on the server on sync, and the result and explanation follow the recorded answer (Action Plan 3.11); without it the legacy contract returns choice and number questions with approved answers included. Writes accept body fields only; an unknown outcome retries the same operation. Offline synchronization uses the server school calendar day.
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
  /// Returns a [Future] containing a [Response] with a [GetPracticeAccessResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<GetPracticeAccessResponse>> getPracticeAccess({
    int? xAccountId,
    int? xSchoolId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/learning/practice/access';
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

    GetPracticeAccessResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<GetPracticeAccessResponse, GetPracticeAccessResponse>(
              rawData,
              'GetPracticeAccessResponse',
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

    return Response<GetPracticeAccessResponse>(
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

  /// getPracticeBank
  /// Approved school-content practice only, never official assessment marks. Fresh learner approval/ownership or dated Teacher allocation and all published topic/content sources authorize before reads, writes and encrypted receipt replay. The module defaults off. With X-Practice-Contract 2 sets carry questions only, answers are marked on the server on sync, and the result and explanation follow the recorded answer (Action Plan 3.11); without it the legacy contract returns choice and number questions with approved answers included. Writes accept body fields only; an unknown outcome retries the same operation. Offline synchronization uses the server school calendar day.
  ///
  /// Parameters:
  /// * [group]
  /// * [xPracticeContract] - 2 opts in to the answer-free practice contract (Action Plan 3.11): sets carry questions only, of every kind, and answers and explanations arrive in review.solution once the answer is recorded. Without it (or 1) the legacy contract applies, exactly as before: choice and number questions only, approved answers included. The legacy contract is retired with practice.legacy_set_contract; legacy requests are then refused with 426.
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
  /// Returns a [Future] containing a [Response] with a [GetPracticeBankResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<GetPracticeBankResponse>> getPracticeBank({
    required int group,
    String? xPracticeContract,
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
    final _path = r'/learning/practice/groups/{group}/bank'.replaceAll(
      '{'
      r'group'
      '}',
      group.toString(),
    );
    final _options = Options(
      method: r'GET',
      headers: <String, dynamic>{
        if (xPracticeContract != null)
          r'X-Practice-Contract': xPracticeContract,
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

    GetPracticeBankResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<GetPracticeBankResponse, GetPracticeBankResponse>(
              rawData,
              'GetPracticeBankResponse',
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

    return Response<GetPracticeBankResponse>(
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

  /// getPracticeBankChoices
  /// Approved school-content practice only, never official assessment marks. Fresh learner approval/ownership or dated Teacher allocation and all published topic/content sources authorize before reads, writes and encrypted receipt replay. The module defaults off. With X-Practice-Contract 2 sets carry questions only, answers are marked on the server on sync, and the result and explanation follow the recorded answer (Action Plan 3.11); without it the legacy contract returns choice and number questions with approved answers included. Writes accept body fields only; an unknown outcome retries the same operation. Offline synchronization uses the server school calendar day.
  ///
  /// Parameters:
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
  /// Returns a [Future] containing a [Response] with a [GetPracticeBankChoicesResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<GetPracticeBankChoicesResponse>> getPracticeBankChoices({
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
    final _path = r'/learning/practice/groups/{group}/choices'.replaceAll(
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

    GetPracticeBankChoicesResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              GetPracticeBankChoicesResponse,
              GetPracticeBankChoicesResponse
            >(rawData, 'GetPracticeBankChoicesResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<GetPracticeBankChoicesResponse>(
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

  /// getPracticeDailySet
  /// Approved school-content practice only, never official assessment marks. Fresh learner approval/ownership or dated Teacher allocation and all published topic/content sources authorize before reads, writes and encrypted receipt replay. The module defaults off. With X-Practice-Contract 2 sets carry questions only, answers are marked on the server on sync, and the result and explanation follow the recorded answer (Action Plan 3.11); without it the legacy contract returns choice and number questions with approved answers included. Writes accept body fields only; an unknown outcome retries the same operation. Offline synchronization uses the server school calendar day.
  ///
  /// Parameters:
  /// * [learner]
  /// * [set_]
  /// * [xPracticeContract] - 2 opts in to the answer-free practice contract (Action Plan 3.11): sets carry questions only, of every kind, and answers and explanations arrive in review.solution once the answer is recorded. Without it (or 1) the legacy contract applies, exactly as before: choice and number questions only, approved answers included. The legacy contract is retired with practice.legacy_set_contract; legacy requests are then refused with 426.
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [GetPracticeDailySetResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<GetPracticeDailySetResponse>> getPracticeDailySet({
    required int learner,
    required int set_,
    String? xPracticeContract,
    int? xAccountId,
    int? xSchoolId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/learning/practice/learners/{learner}/daily-sets/{set}'
        .replaceAll(
          '{'
          r'learner'
          '}',
          learner.toString(),
        )
        .replaceAll(
          '{'
          r'set'
          '}',
          set_.toString(),
        );
    final _options = Options(
      method: r'GET',
      headers: <String, dynamic>{
        if (xPracticeContract != null)
          r'X-Practice-Contract': xPracticeContract,
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

    GetPracticeDailySetResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              GetPracticeDailySetResponse,
              GetPracticeDailySetResponse
            >(rawData, 'GetPracticeDailySetResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<GetPracticeDailySetResponse>(
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

  /// getPracticeEntryDiagram
  /// The diagram picture of a labelling question in the learner&#39;s own set (Action Plan 3.11), authorized exactly as reading the set. JPEG pixels only; cache it with the set and erase it with the set.
  ///
  /// Parameters:
  /// * [learner]
  /// * [set_]
  /// * [entry]
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [Uint8List] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<Uint8List>> getPracticeEntryDiagram({
    required int learner,
    required int set_,
    required int entry,
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
        r'/learning/practice/learners/{learner}/daily-sets/{set}/entries/{entry}/diagram'
            .replaceAll(
              '{'
              r'learner'
              '}',
              learner.toString(),
            )
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
      method: r'GET',
      responseType: ResponseType.bytes,
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

    Uint8List? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null ? null : rawData as Uint8List;
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<Uint8List>(
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

  /// getPracticeMarking
  /// Practice answers waiting for a teacher in this group (Action Plan 3.11): short written answers that matched no accepted answer, and spoken answers. Only the group&#39;s current teachers, by dated allocation; never another teacher, a learner, a parent or another school.
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
  /// Returns a [Future] containing a [Response] with a [GetPracticeMarkingResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<GetPracticeMarkingResponse>> getPracticeMarking({
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
    final _path = r'/learning/practice/groups/{group}/marking'.replaceAll(
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

    GetPracticeMarkingResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<GetPracticeMarkingResponse, GetPracticeMarkingResponse>(
              rawData,
              'GetPracticeMarkingResponse',
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

    return Response<GetPracticeMarkingResponse>(
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

  /// getPracticeMarkingAudio
  /// A spoken practice answer&#39;s recording, for the current teachers of the group it was given in only (Action Plan 3.11). Audited.
  ///
  /// Parameters:
  /// * [group]
  /// * [review]
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [Uint8List] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<Uint8List>> getPracticeMarkingAudio({
    required int group,
    required int review,
    int? xAccountId,
    int? xSchoolId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/learning/practice/groups/{group}/marking/{review}/audio'
        .replaceAll(
          '{'
          r'group'
          '}',
          group.toString(),
        )
        .replaceAll(
          '{'
          r'review'
          '}',
          review.toString(),
        );
    final _options = Options(
      method: r'GET',
      responseType: ResponseType.bytes,
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

    Uint8List? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null ? null : rawData as Uint8List;
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<Uint8List>(
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

  /// getPracticeMotivation
  /// Action Plan 3.13. The learner&#39;s own practice run (counted in practice days; weekends, holidays and one rest day a week never break it), weekly practice goal and topics made secure this school year, in plain words. Only the learner or a current guardian of a current, guardian-approved learner in a stage with daily practice may read it. It describes this learner only, with no ranking, score or comparison. Private, no-store.
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
  /// Returns a [Future] containing a [Response] with a [GetPracticeMotivationResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<GetPracticeMotivationResponse>> getPracticeMotivation({
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
    final _path = r'/learning/practice/learners/{learner}/motivation'
        .replaceAll(
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

    GetPracticeMotivationResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              GetPracticeMotivationResponse,
              GetPracticeMotivationResponse
            >(rawData, 'GetPracticeMotivationResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<GetPracticeMotivationResponse>(
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

  /// getPracticeQuizTopics
  /// Timed practice tests from the school question bank and the platform library, never official assessment marks. Fresh learner approval/ownership or dated Teacher allocation authorize every read, write and receipt replay; a guardian, another learner or another school is not found. The server holds the deadline: before hand-in a sitting carries prompts and options only, never accepted answers or worked explanations. Writes accept body fields only; an unknown outcome retries the same operation.
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
  /// Returns a [Future] containing a [Response] with a [GetPracticeQuizTopicsResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<GetPracticeQuizTopicsResponse>> getPracticeQuizTopics({
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
    final _path =
        r'/learning/practice-tests/learners/{learner}/groups/{group}/quiz-topics'
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

    GetPracticeQuizTopicsResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              GetPracticeQuizTopicsResponse,
              GetPracticeQuizTopicsResponse
            >(rawData, 'GetPracticeQuizTopicsResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<GetPracticeQuizTopicsResponse>(
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

  /// getPracticeTest
  /// Timed practice tests from the school question bank and the platform library, never official assessment marks. Fresh learner approval/ownership or dated Teacher allocation authorize every read, write and receipt replay; a guardian, another learner or another school is not found. The server holds the deadline: before hand-in a sitting carries prompts and options only, never accepted answers or worked explanations. Writes accept body fields only; an unknown outcome retries the same operation.
  ///
  /// Parameters:
  /// * [learner]
  /// * [test]
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [GetPracticeTestResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<GetPracticeTestResponse>> getPracticeTest({
    required int learner,
    required int test,
    int? xAccountId,
    int? xSchoolId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/learning/practice-tests/learners/{learner}/tests/{test}'
        .replaceAll(
          '{'
          r'learner'
          '}',
          learner.toString(),
        )
        .replaceAll(
          '{'
          r'test'
          '}',
          test.toString(),
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

    GetPracticeTestResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<GetPracticeTestResponse, GetPracticeTestResponse>(
              rawData,
              'GetPracticeTestResponse',
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

    return Response<GetPracticeTestResponse>(
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

  /// getPracticeTestOptions
  /// Timed practice tests from the school question bank and the platform library, never official assessment marks. Fresh learner approval/ownership or dated Teacher allocation authorize every read, write and receipt replay; a guardian, another learner or another school is not found. The server holds the deadline: before hand-in a sitting carries prompts and options only, never accepted answers or worked explanations. Writes accept body fields only; an unknown outcome retries the same operation.
  ///
  /// Parameters:
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
  /// Returns a [Future] containing a [Response] with a [GetPracticeTestOptionsResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<GetPracticeTestOptionsResponse>> getPracticeTestOptions({
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
    final _path = r'/learning/practice-tests/groups/{group}/options'.replaceAll(
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

    GetPracticeTestOptionsResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              GetPracticeTestOptionsResponse,
              GetPracticeTestOptionsResponse
            >(rawData, 'GetPracticeTestOptionsResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<GetPracticeTestOptionsResponse>(
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

  /// getPracticeTestResults
  /// Timed practice tests from the school question bank and the platform library, never official assessment marks. Fresh learner approval/ownership or dated Teacher allocation authorize every read, write and receipt replay; a guardian, another learner or another school is not found. The server holds the deadline: before hand-in a sitting carries prompts and options only, never accepted answers or worked explanations. Writes accept body fields only; an unknown outcome retries the same operation.
  ///
  /// Parameters:
  /// * [group]
  /// * [blueprint]
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [GetPracticeTestResultsResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<GetPracticeTestResultsResponse>> getPracticeTestResults({
    required int group,
    required int blueprint,
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
        r'/learning/practice-tests/groups/{group}/blueprints/{blueprint}/results'
            .replaceAll(
              '{'
              r'group'
              '}',
              group.toString(),
            )
            .replaceAll(
              '{'
              r'blueprint'
              '}',
              blueprint.toString(),
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

    GetPracticeTestResultsResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              GetPracticeTestResultsResponse,
              GetPracticeTestResultsResponse
            >(rawData, 'GetPracticeTestResultsResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<GetPracticeTestResultsResponse>(
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

  /// issuePracticeDailySet
  /// Approved school-content practice only, never official assessment marks. Fresh learner approval/ownership or dated Teacher allocation and all published topic/content sources authorize before reads, writes and encrypted receipt replay. The module defaults off. With X-Practice-Contract 2 sets carry questions only, answers are marked on the server on sync, and the result and explanation follow the recorded answer (Action Plan 3.11); without it the legacy contract returns choice and number questions with approved answers included. Writes accept body fields only; an unknown outcome retries the same operation. Offline synchronization uses the server school calendar day.
  ///
  /// Parameters:
  /// * [learner]
  /// * [group]
  /// * [practiceIssueInput]
  /// * [xPracticeContract] - 2 opts in to the answer-free practice contract (Action Plan 3.11): sets carry questions only, of every kind, and answers and explanations arrive in review.solution once the answer is recorded. Without it (or 1) the legacy contract applies, exactly as before: choice and number questions only, approved answers included. The legacy contract is retired with practice.legacy_set_contract; legacy requests are then refused with 426.
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [IssuePracticeDailySetResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<IssuePracticeDailySetResponse>> issuePracticeDailySet({
    required int learner,
    required int group,
    required PracticeIssueInput practiceIssueInput,
    String? xPracticeContract,
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
        r'/learning/practice/learners/{learner}/groups/{group}/daily-sets'
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
        if (xPracticeContract != null)
          r'X-Practice-Contract': xPracticeContract,
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
      _bodyData = jsonEncode(practiceIssueInput);
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

    IssuePracticeDailySetResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              IssuePracticeDailySetResponse,
              IssuePracticeDailySetResponse
            >(rawData, 'IssuePracticeDailySetResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<IssuePracticeDailySetResponse>(
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

  /// listPracticeTestBlueprints
  /// Timed practice tests from the school question bank and the platform library, never official assessment marks. Fresh learner approval/ownership or dated Teacher allocation authorize every read, write and receipt replay; a guardian, another learner or another school is not found. The server holds the deadline: before hand-in a sitting carries prompts and options only, never accepted answers or worked explanations. Writes accept body fields only; an unknown outcome retries the same operation.
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
  /// Returns a [Future] containing a [Response] with a [ListPracticeTestBlueprintsResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<ListPracticeTestBlueprintsResponse>>
  listPracticeTestBlueprints({
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
    final _path = r'/learning/practice-tests/groups/{group}/blueprints'
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

    final _queryParameters = <String, dynamic>{if (page != null) r'page': page};

    final _response = await _dio.request<Object>(
      _path,
      options: _options,
      queryParameters: _queryParameters,
      cancelToken: cancelToken,
      onSendProgress: onSendProgress,
      onReceiveProgress: onReceiveProgress,
    );

    ListPracticeTestBlueprintsResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              ListPracticeTestBlueprintsResponse,
              ListPracticeTestBlueprintsResponse
            >(rawData, 'ListPracticeTestBlueprintsResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<ListPracticeTestBlueprintsResponse>(
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

  /// listPracticeTests
  /// Timed practice tests from the school question bank and the platform library, never official assessment marks. Fresh learner approval/ownership or dated Teacher allocation authorize every read, write and receipt replay; a guardian, another learner or another school is not found. The server holds the deadline: before hand-in a sitting carries prompts and options only, never accepted answers or worked explanations. Writes accept body fields only; an unknown outcome retries the same operation.
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
  /// Returns a [Future] containing a [Response] with a [ListPracticeTestsResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<ListPracticeTestsResponse>> listPracticeTests({
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
    final _path = r'/learning/practice-tests/learners/{learner}/tests'
        .replaceAll(
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

    ListPracticeTestsResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<ListPracticeTestsResponse, ListPracticeTestsResponse>(
              rawData,
              'ListPracticeTestsResponse',
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

    return Response<ListPracticeTestsResponse>(
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

  /// markPracticeAnswer
  /// A current teacher of the group marks a waiting answer correct or not yet (Action Plan 3.11). Marked once: the same operation replays its receipt and any other attempt is refused with 409. The mark feeds practice memory and mastery evidence exactly as an automatic mark does.
  ///
  /// Parameters:
  /// * [group]
  /// * [review]
  /// * [practiceMarkInput]
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [MarkPracticeAnswerResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<MarkPracticeAnswerResponse>> markPracticeAnswer({
    required int group,
    required int review,
    required PracticeMarkInput practiceMarkInput,
    int? xAccountId,
    int? xSchoolId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/learning/practice/groups/{group}/marking/{review}'
        .replaceAll(
          '{'
          r'group'
          '}',
          group.toString(),
        )
        .replaceAll(
          '{'
          r'review'
          '}',
          review.toString(),
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
      _bodyData = jsonEncode(practiceMarkInput);
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

    MarkPracticeAnswerResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<MarkPracticeAnswerResponse, MarkPracticeAnswerResponse>(
              rawData,
              'MarkPracticeAnswerResponse',
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

    return Response<MarkPracticeAnswerResponse>(
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

  /// reviewPracticeEntry
  /// Approved school-content practice only, never official assessment marks. Fresh learner approval/ownership or dated Teacher allocation and all published topic/content sources authorize before reads, writes and encrypted receipt replay. The module defaults off. With X-Practice-Contract 2 sets carry questions only, answers are marked on the server on sync, and the result and explanation follow the recorded answer (Action Plan 3.11); without it the legacy contract returns choice and number questions with approved answers included. Writes accept body fields only; an unknown outcome retries the same operation. Offline synchronization uses the server school calendar day.
  ///
  /// Parameters:
  /// * [learner]
  /// * [set_]
  /// * [entry]
  /// * [practiceReviewInput]
  /// * [xPracticeContract] - 2 opts in to the answer-free practice contract (Action Plan 3.11): sets carry questions only, of every kind, and answers and explanations arrive in review.solution once the answer is recorded. Without it (or 1) the legacy contract applies, exactly as before: choice and number questions only, approved answers included. The legacy contract is retired with practice.legacy_set_contract; legacy requests are then refused with 426.
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [ReviewPracticeEntryResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<ReviewPracticeEntryResponse>> reviewPracticeEntry({
    required int learner,
    required int set_,
    required int entry,
    required PracticeReviewInput practiceReviewInput,
    String? xPracticeContract,
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
        r'/learning/practice/learners/{learner}/daily-sets/{set}/entries/{entry}/review'
            .replaceAll(
              '{'
              r'learner'
              '}',
              learner.toString(),
            )
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
      headers: <String, dynamic>{
        if (xPracticeContract != null)
          r'X-Practice-Contract': xPracticeContract,
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
      _bodyData = jsonEncode(practiceReviewInput);
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

    ReviewPracticeEntryResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              ReviewPracticeEntryResponse,
              ReviewPracticeEntryResponse
            >(rawData, 'ReviewPracticeEntryResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<ReviewPracticeEntryResponse>(
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

  /// revisePracticeQuestion
  /// Approved school-content practice only, never official assessment marks. Fresh learner approval/ownership or dated Teacher allocation and all published topic/content sources authorize before reads, writes and encrypted receipt replay. The module defaults off. With X-Practice-Contract 2 sets carry questions only, answers are marked on the server on sync, and the result and explanation follow the recorded answer (Action Plan 3.11); without it the legacy contract returns choice and number questions with approved answers included. Writes accept body fields only; an unknown outcome retries the same operation. Offline synchronization uses the server school calendar day.
  ///
  /// Parameters:
  /// * [group]
  /// * [item]
  /// * [practiceApproveInput]
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [RevisePracticeQuestionResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<RevisePracticeQuestionResponse>> revisePracticeQuestion({
    required int group,
    required int item,
    required PracticeApproveInput practiceApproveInput,
    int? xAccountId,
    int? xSchoolId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/learning/practice/groups/{group}/bank/{item}'
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
      _bodyData = jsonEncode(practiceApproveInput);
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

    RevisePracticeQuestionResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              RevisePracticeQuestionResponse,
              RevisePracticeQuestionResponse
            >(rawData, 'RevisePracticeQuestionResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<RevisePracticeQuestionResponse>(
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

  /// savePracticeTestAnswers
  /// Timed practice tests from the school question bank and the platform library, never official assessment marks. Fresh learner approval/ownership or dated Teacher allocation authorize every read, write and receipt replay; a guardian, another learner or another school is not found. The server holds the deadline: before hand-in a sitting carries prompts and options only, never accepted answers or worked explanations. Writes accept body fields only; an unknown outcome retries the same operation.
  ///
  /// Parameters:
  /// * [learner]
  /// * [test]
  /// * [practiceTestAnswersInput]
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [SavePracticeTestAnswersResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<SavePracticeTestAnswersResponse>> savePracticeTestAnswers({
    required int learner,
    required int test,
    required PracticeTestAnswersInput practiceTestAnswersInput,
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
        r'/learning/practice-tests/learners/{learner}/tests/{test}/answers'
            .replaceAll(
              '{'
              r'learner'
              '}',
              learner.toString(),
            )
            .replaceAll(
              '{'
              r'test'
              '}',
              test.toString(),
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
      _bodyData = jsonEncode(practiceTestAnswersInput);
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

    SavePracticeTestAnswersResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              SavePracticeTestAnswersResponse,
              SavePracticeTestAnswersResponse
            >(rawData, 'SavePracticeTestAnswersResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<SavePracticeTestAnswersResponse>(
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

  /// setPracticeGoal
  /// Action Plan 3.13. Only the learner sets or clears (null) their own weekly practice goal, in days a week. Guardians may read it but not set it. Writes accept body fields only, check the goal revision (409 when stale) and replay an exact retry of the same operation. Reaching the goal is celebrated once a week.
  ///
  /// Parameters:
  /// * [learner]
  /// * [practiceGoalInput]
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [GetPracticeMotivationResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<GetPracticeMotivationResponse>> setPracticeGoal({
    required int learner,
    required PracticeGoalInput practiceGoalInput,
    int? xAccountId,
    int? xSchoolId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/learning/practice/learners/{learner}/practice-goal'
        .replaceAll(
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
      _bodyData = jsonEncode(practiceGoalInput);
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

    GetPracticeMotivationResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              GetPracticeMotivationResponse,
              GetPracticeMotivationResponse
            >(rawData, 'GetPracticeMotivationResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<GetPracticeMotivationResponse>(
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

  /// startPracticeQuickQuiz
  /// Timed practice tests from the school question bank and the platform library, never official assessment marks. Fresh learner approval/ownership or dated Teacher allocation authorize every read, write and receipt replay; a guardian, another learner or another school is not found. The server holds the deadline: before hand-in a sitting carries prompts and options only, never accepted answers or worked explanations. Writes accept body fields only; an unknown outcome retries the same operation.
  ///
  /// Parameters:
  /// * [learner]
  /// * [group]
  /// * [practiceQuickQuizInput]
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [StartPracticeQuickQuizResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<StartPracticeQuickQuizResponse>> startPracticeQuickQuiz({
    required int learner,
    required int group,
    required PracticeQuickQuizInput practiceQuickQuizInput,
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
        r'/learning/practice-tests/learners/{learner}/groups/{group}/quick-quizzes'
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
      _bodyData = jsonEncode(practiceQuickQuizInput);
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

    StartPracticeQuickQuizResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              StartPracticeQuickQuizResponse,
              StartPracticeQuickQuizResponse
            >(rawData, 'StartPracticeQuickQuizResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<StartPracticeQuickQuizResponse>(
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

  /// startPracticeTest
  /// Timed practice tests from the school question bank and the platform library, never official assessment marks. Fresh learner approval/ownership or dated Teacher allocation authorize every read, write and receipt replay; a guardian, another learner or another school is not found. The server holds the deadline: before hand-in a sitting carries prompts and options only, never accepted answers or worked explanations. Writes accept body fields only; an unknown outcome retries the same operation.
  ///
  /// Parameters:
  /// * [learner]
  /// * [blueprint]
  /// * [practiceTestStartInput]
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [StartPracticeTestResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<StartPracticeTestResponse>> startPracticeTest({
    required int learner,
    required int blueprint,
    required PracticeTestStartInput practiceTestStartInput,
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
        r'/learning/practice-tests/learners/{learner}/blueprints/{blueprint}/start'
            .replaceAll(
              '{'
              r'learner'
              '}',
              learner.toString(),
            )
            .replaceAll(
              '{'
              r'blueprint'
              '}',
              blueprint.toString(),
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
      _bodyData = jsonEncode(practiceTestStartInput);
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

    StartPracticeTestResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<StartPracticeTestResponse, StartPracticeTestResponse>(
              rawData,
              'StartPracticeTestResponse',
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

    return Response<StartPracticeTestResponse>(
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

  /// submitPracticeTest
  /// Timed practice tests from the school question bank and the platform library, never official assessment marks. Fresh learner approval/ownership or dated Teacher allocation authorize every read, write and receipt replay; a guardian, another learner or another school is not found. The server holds the deadline: before hand-in a sitting carries prompts and options only, never accepted answers or worked explanations. Writes accept body fields only; an unknown outcome retries the same operation.
  ///
  /// Parameters:
  /// * [learner]
  /// * [test]
  /// * [practiceTestSubmitInput]
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [SubmitPracticeTestResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<SubmitPracticeTestResponse>> submitPracticeTest({
    required int learner,
    required int test,
    required PracticeTestSubmitInput practiceTestSubmitInput,
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
        r'/learning/practice-tests/learners/{learner}/tests/{test}/submit'
            .replaceAll(
              '{'
              r'learner'
              '}',
              learner.toString(),
            )
            .replaceAll(
              '{'
              r'test'
              '}',
              test.toString(),
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
      _bodyData = jsonEncode(practiceTestSubmitInput);
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

    SubmitPracticeTestResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<SubmitPracticeTestResponse, SubmitPracticeTestResponse>(
              rawData,
              'SubmitPracticeTestResponse',
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

    return Response<SubmitPracticeTestResponse>(
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

  /// uploadPracticeDiagram
  /// A current teacher of the group uploads a diagram picture for a labelling question (Action Plan 3.11). JPEG or PNG up to 4 MB, re-encoded as pixels only, scanned and stored encrypted in the school&#39;s private storage. Reference the returned id in PracticeQuestion.diagram. A retried upload stores another copy; only a referenced picture is used.
  ///
  /// Parameters:
  /// * [group]
  /// * [photo] - JPEG or PNG up to 4 MB; re-encoded as pixels only and scanned.
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [UploadPracticeDiagramResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<UploadPracticeDiagramResponse>> uploadPracticeDiagram({
    required int group,
    required MultipartFile photo,
    int? xAccountId,
    int? xSchoolId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/learning/practice/groups/{group}/diagrams'.replaceAll(
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
      contentType: 'multipart/form-data',
      validateStatus: validateStatus,
    );

    dynamic _bodyData;

    try {
      _bodyData = FormData.fromMap(<String, dynamic>{r'photo': photo});
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

    UploadPracticeDiagramResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              UploadPracticeDiagramResponse,
              UploadPracticeDiagramResponse
            >(rawData, 'UploadPracticeDiagramResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<UploadPracticeDiagramResponse>(
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

  /// withdrawPracticeTestBlueprint
  /// Timed practice tests from the school question bank and the platform library, never official assessment marks. Fresh learner approval/ownership or dated Teacher allocation authorize every read, write and receipt replay; a guardian, another learner or another school is not found. The server holds the deadline: before hand-in a sitting carries prompts and options only, never accepted answers or worked explanations. Writes accept body fields only; an unknown outcome retries the same operation.
  ///
  /// Parameters:
  /// * [group]
  /// * [blueprint]
  /// * [practiceTestWithdrawInput]
  /// * [xAccountId] - Expected authenticated account for a pinned private view. A mismatch is refused before school data is read or an operation is replayed. This header never grants authority. Older clients may omit it.
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [WithdrawPracticeTestBlueprintResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<WithdrawPracticeTestBlueprintResponse>>
  withdrawPracticeTestBlueprint({
    required int group,
    required int blueprint,
    required PracticeTestWithdrawInput practiceTestWithdrawInput,
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
        r'/learning/practice-tests/groups/{group}/blueprints/{blueprint}/withdraw'
            .replaceAll(
              '{'
              r'group'
              '}',
              group.toString(),
            )
            .replaceAll(
              '{'
              r'blueprint'
              '}',
              blueprint.toString(),
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
      _bodyData = jsonEncode(practiceTestWithdrawInput);
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

    WithdrawPracticeTestBlueprintResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<
              WithdrawPracticeTestBlueprintResponse,
              WithdrawPracticeTestBlueprintResponse
            >(rawData, 'WithdrawPracticeTestBlueprintResponse', growable: true);
    } catch (error, stackTrace) {
      throw DioException(
        requestOptions: _response.requestOptions,
        response: _response,
        type: DioExceptionType.unknown,
        error: error,
        stackTrace: stackTrace,
      );
    }

    return Response<WithdrawPracticeTestBlueprintResponse>(
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
