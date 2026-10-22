//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

import 'dart:async';

// ignore: unused_import
import 'dart:convert';
import 'package:eschool_api/src/deserialize.dart';
import 'package:dio/dio.dart';

import 'package:eschool_api/src/model/context_response.dart';
import 'package:eschool_api/src/model/error_response.dart';

class PlatformApi {
  final Dio _dio;

  const PlatformApi(this._dio);

  /// The signed-in user, their schools and the current school
  /// What an app needs to set itself up: the user and their roles, the schools they belong to, the current school (branding, timezone, year and terms), which modules it has on, its notification rules and feature flags, the sign-in methods it offers and, for a parent, each child with that child&#39;s school. Apps fetch it at start-up, after signing in and when the user changes school, and keep a cached copy. Every profile may call it. Budget: 150 ms at p95.  The answer is for the school the request works in: the one &#x60;X-School-Id&#x60; names (one of the user&#39;s &#x60;schools&#x60;), or else the user&#39;s first school. &#x60;user.roles&#x60; are the roles there; each entry of &#x60;schools&#x60; lists the user&#39;s roles at that school. The answer is composed by contributor classes (&#x60;config/context.php&#x60;); a key or field is only ever added.
  ///
  /// Parameters:
  /// * [xSchoolId] - The school to work in, one of the signed-in user's schools (`schools` in `/me/context`). Without it the request works in the user's first school. A school the user is not an active member of is refused with 403. Apps send it on every call once a school is chosen.
  /// * [cancelToken] - A [CancelToken] that can be used to cancel the operation
  /// * [headers] - Can be used to add additional headers to the request
  /// * [extras] - Can be used to add flags to the request
  /// * [validateStatus] - A [ValidateStatus] callback that can be used to determine request success based on the HTTP status of the response
  /// * [onSendProgress] - A [ProgressCallback] that can be used to get the send progress
  /// * [onReceiveProgress] - A [ProgressCallback] that can be used to get the receive progress
  ///
  /// Returns a [Future] containing a [Response] with a [ContextResponse] as data
  /// Throws [DioException] if API call or serialization fails
  Future<Response<ContextResponse>> getMeContext({
    int? xSchoolId,
    CancelToken? cancelToken,
    Map<String, dynamic>? headers,
    Map<String, dynamic>? extra,
    ValidateStatus? validateStatus,
    ProgressCallback? onSendProgress,
    ProgressCallback? onReceiveProgress,
  }) async {
    final _path = r'/me/context';
    final _options = Options(
      method: r'GET',
      headers: <String, dynamic>{
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

    ContextResponse? _responseData;

    try {
      final rawData = _response.data;
      _responseData = rawData == null
          ? null
          : deserialize<ContextResponse, ContextResponse>(
              rawData,
              'ContextResponse',
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

    return Response<ContextResponse>(
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
