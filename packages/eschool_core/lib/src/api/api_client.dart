import 'dart:async';
import 'dart:io';

import 'package:dio/dio.dart';
import 'package:eschool_core/src/api/api_error_codes.dart';
import 'package:eschool_core/src/api/api_exception.dart';
import 'package:flutter/foundation.dart';

/// Makes the calls to the admin panel's API and turns every failure into an
/// [ApiException]. What differs between the apps is passed in: how to find the
/// signed-in user's token, and what to do when the server rejects it.
class ApiClient {
  ApiClient({
    required this.authToken,
    required this.onUnauthorized,
    Dio Function()? createDio,
    this.sendsBearerToken = true,
  }) : _createDio = createDio ?? Dio.new;

  /// The signed-in user's token, or an empty string when nobody is signed in.
  final String Function() authToken;

  /// Called when the server answers 401, before the [ApiException] is thrown.
  final void Function() onUnauthorized;

  final Dio Function() _createDio;

  /// False when the signed-in user is known by a session cookie instead (the
  /// web portals), so no Authorization header is sent.
  final bool sendsBearerToken;

  Map<String, dynamic> headers() {
    return {
      if (sendsBearerToken) 'Authorization': 'Bearer ${authToken()}',
      // Without it an older server answers an expired token with a redirect
      // instead of the 401 that signs the user out.
      'Accept': 'application/json',
    };
  }

  Future<Map<String, dynamic>> post({
    required Map<String, dynamic> body,
    required String url,
    required bool useAuthToken,
    Map<String, dynamic>? queryParameters,
    CancelToken? cancelToken,
    Function(int, int)? onSendProgress,
    Function(int, int)? onReceiveProgress,
  }) async {
    try {
      final Dio dio = _createDio();
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

      return _checked(response);
    } on DioException catch (e) {
      throw _fromDioException(e);
    } on ApiException {
      rethrow;
    } catch (e) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
  }

  Future<Map<String, dynamic>> get({
    required String url,
    required bool useAuthToken,
    Map<String, dynamic>? queryParameters,
  }) async {
    try {
      final Dio dio = _createDio();

      debugPrint('API Called GET: $url');

      final response = await dio.get(
        url,
        queryParameters: queryParameters,
        options: useAuthToken ? Options(headers: headers()) : null,
      );

      return _checked(response);
    } on DioException catch (e) {
      throw _fromDioException(e);
    } on ApiException {
      rethrow;
    } catch (e) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
  }

  /// Like [get], but answers `null` instead of failing when the server has
  /// no such route (HTTP 404), for endpoints newer than some servers.
  Future<Map<String, dynamic>?> getIfSupported({
    required String url,
    required bool useAuthToken,
    Map<String, dynamic>? queryParameters,
  }) async {
    try {
      final Dio dio = _createDio();

      debugPrint('API Called GET: $url');

      final response = await dio.get(
        url,
        queryParameters: queryParameters,
        options: useAuthToken ? Options(headers: headers()) : null,
      );

      return _checked(response);
    } on DioException catch (e) {
      if (e.response?.statusCode == 404) {
        return null;
      }
      throw _fromDioException(e);
    } on ApiException {
      rethrow;
    } catch (e) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
  }

  /// A call to API v2 with any [method]: [data] is sent as JSON, or as it
  /// is when it is [FormData] (a file). Answers the response body, or `null`
  /// when [notFoundAsNull] and the server has no such route (HTTP 404: an
  /// endpoint not deployed yet). A refusal the server explains (a 4xx with a
  /// `message`, such as 422 for a locked setting) becomes an [ApiException]
  /// carrying that message to show as it is; the rest fail like [get].
  /// [requestTimeout] bounds the whole call and cancels its transport. Only
  /// expiry without an HTTP response is classified as a transport failure.
  Future<Map<String, dynamic>?> send({
    required String method,
    required String url,
    Object? data,
    Map<String, dynamic>? queryParameters,
    bool useAuthToken = true,
    bool notFoundAsNull = false,
    int? schoolId,
    int? accountId,
    String? idempotencyKey,
    Duration? requestTimeout,
  }) async {
    final cancellation = requestTimeout == null ? null : CancelToken();
    var deadlineElapsed = false;
    final deadline = requestTimeout == null
        ? null
        : Timer(requestTimeout, () {
            deadlineElapsed = true;
            cancellation!.cancel('The request deadline elapsed.');
          });
    try {
      final Dio dio = _createDio();

      debugPrint('API Called $method: $url');

      final response = await dio.request<dynamic>(
        url,
        data: data,
        queryParameters: queryParameters,
        cancelToken: cancellation,
        options: Options(
          method: method,
          headers: useAuthToken
              ? {
                  ...headers(),
                  if (schoolId != null) 'X-School-Id': '$schoolId',
                  if (accountId != null) 'X-Account-Id': '$accountId',
                  if (idempotencyKey != null) 'Idempotency-Key': idempotencyKey,
                }
              : const {'Accept': 'application/json'},
          contentType: data is FormData ? null : Headers.jsonContentType,
        ),
      );

      return _checked(response);
    } on DioException catch (e) {
      if (deadlineElapsed &&
          e.type == DioExceptionType.cancel &&
          e.response == null) {
        throw _fromDioException(
          DioException(
            requestOptions: e.requestOptions,
            type: DioExceptionType.receiveTimeout,
          ),
        );
      }
      final int? status = e.response?.statusCode;
      if (status == 404 && notFoundAsNull) {
        return null;
      }
      final Object? body = e.response?.data;
      if (status != null &&
          status >= 400 &&
          status < 500 &&
          status != 401 &&
          status != 404 &&
          body is Map &&
          body['message'] is String &&
          (body['message'] as String).isNotEmpty) {
        throw ApiException(
          body['message'] as String,
          isApiMessage: true,
          statusCode: status,
        );
      }
      throw _fromDioException(e);
    } on ApiException {
      rethrow;
    } catch (e) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    } finally {
      deadline?.cancel();
    }
  }

  /// Reads an authenticated asset into memory, with explicit school scope.
  Future<Uint8List> getBytes({required String url, int? schoolId}) async {
    try {
      final response = await _createDio().get<List<int>>(
        url,
        options: Options(
          responseType: ResponseType.bytes,
          followRedirects: false,
          headers: {
            ...headers(),
            if (schoolId != null) 'X-School-Id': '$schoolId',
          },
        ),
      );
      if (response.data == null) {
        throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
      }
      return Uint8List.fromList(response.data!);
    } on DioException catch (error) {
      throw _fromDioException(error);
    } on ApiException {
      rethrow;
    } catch (_) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
  }

  Future<void> download({
    required String url,
    required CancelToken cancelToken,
    required String savePath,
    required Function updateDownloadedPercentage,
  }) async {
    try {
      final Dio dio = _createDio();
      await dio.download(
        url,
        savePath,
        cancelToken: cancelToken,
        onReceiveProgress: (count, total) =>
            _reportProgress(count, total, updateDownloadedPercentage),
      );
    } on DioException catch (e) {
      throw _fromDownloadException(e);
    } on ApiException catch (e) {
      throw ApiException(e.errorMessage);
    } catch (e) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
  }

  /// Like [download], but keeps the file in memory: what a web build does,
  /// as it cannot write files. The files are public, so no cookie or token
  /// goes with the request (a storage bucket on another origin would refuse
  /// a request with credentials).
  Future<Uint8List> downloadBytes({
    required String url,
    required CancelToken cancelToken,
    required Function updateDownloadedPercentage,
  }) async {
    try {
      final Response<List<int>> response = await Dio().get<List<int>>(
        url,
        cancelToken: cancelToken,
        options: Options(responseType: ResponseType.bytes),
        onReceiveProgress: (count, total) =>
            _reportProgress(count, total, updateDownloadedPercentage),
      );
      return Uint8List.fromList(response.data ?? const <int>[]);
    } on DioException catch (e) {
      throw _fromDownloadException(e);
    } catch (e) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
  }

  void _reportProgress(int count, int total, Function report) {
    final double percentage = (count / total) * 100;
    report(percentage < 0.0 ? 99.0 : percentage);
  }

  ApiException _fromDownloadException(DioException e) {
    if (e.response?.statusCode == 503 || e.response?.statusCode == 500) {
      return ApiException(ApiErrorCodes.internetServerError);
    }
    if (e.response?.statusCode == 404) {
      return ApiException(ApiErrorCodes.fileNotFound);
    }
    return ApiException(
      e.error is SocketException
          ? ApiErrorCodes.noInternet
          : ApiErrorCodes.defaultError,
    );
  }

  /// The API answers 200 with `error: true` for failures it wants the app to
  /// show: either a message to display as it is, or a code to translate.
  Map<String, dynamic> _checked(Response response) {
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
  }

  ApiException _fromDioException(DioException e) {
    if (e.response?.statusCode == 401) {
      onUnauthorized();
      return ApiException(ApiErrorCodes.unauthorizedAccess, statusCode: 401);
    }
    if (e.response?.statusCode == 503 || e.response?.statusCode == 500) {
      return ApiException(
        ApiErrorCodes.internetServerError,
        statusCode: e.response?.statusCode,
      );
    }
    return ApiException(
      e.error is SocketException
          ? ApiErrorCodes.noInternet
          : ApiErrorCodes.defaultError,
      statusCode: e.response?.statusCode,
      isTransportFailure:
          e.response == null &&
          const {
            DioExceptionType.connectionError,
            DioExceptionType.connectionTimeout,
            DioExceptionType.sendTimeout,
            DioExceptionType.receiveTimeout,
          }.contains(e.type),
    );
  }
}
