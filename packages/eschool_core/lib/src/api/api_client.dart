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
  }) : _createDio = createDio ?? Dio.new;

  /// The signed-in user's token, or an empty string when nobody is signed in.
  final String Function() authToken;

  /// Called when the server answers 401, before the [ApiException] is thrown.
  final void Function() onUnauthorized;

  final Dio Function() _createDio;

  Map<String, dynamic> headers() {
    return {
      'Authorization': 'Bearer ${authToken()}',
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
        onReceiveProgress: (count, total) {
          final double percentage = (count / total) * 100;
          updateDownloadedPercentage(percentage < 0.0 ? 99.0 : percentage);
        },
      );
    } on DioException catch (e) {
      if (e.response?.statusCode == 503 || e.response?.statusCode == 500) {
        throw ApiException(ApiErrorCodes.internetServerError);
      }
      if (e.response?.statusCode == 404) {
        throw ApiException(ApiErrorCodes.fileNotFound);
      }
      throw ApiException(
        e.error is SocketException
            ? ApiErrorCodes.noInternet
            : ApiErrorCodes.defaultError,
      );
    } on ApiException catch (e) {
      throw ApiException(e.errorMessage);
    } catch (e) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
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
      return ApiException(ApiErrorCodes.unauthorizedAccess);
    }
    if (e.response?.statusCode == 503 || e.response?.statusCode == 500) {
      return ApiException(ApiErrorCodes.internetServerError);
    }
    return ApiException(
      e.error is SocketException
          ? ApiErrorCodes.noInternet
          : ApiErrorCodes.defaultError,
    );
  }
}
