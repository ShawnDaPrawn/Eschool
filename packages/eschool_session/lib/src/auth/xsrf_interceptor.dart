import 'package:dio/dio.dart';

/// Reads one cookie of the page's `document.cookie`, or null.
typedef CookieReader = String? Function(String name);

/// Sanctum's protection for the web portals (cookie mode): a request that
/// changes something must echo the `XSRF-TOKEN` cookie in the
/// `X-XSRF-TOKEN` header. The cookie is readable here because the server
/// sets it on the parent domain the portals share with the API
/// (`SESSION_DOMAIN`), or on the same host when developing locally.
///
/// When the browser has no token yet, it is fetched from
/// `/sanctum/csrf-cookie` first. When the server answers 419 (the session
/// behind the token expired, or another environment on the same parent
/// domain replaced the cookie), a fresh token is fetched and the request is
/// sent once more.
class XsrfInterceptor extends Interceptor {
  XsrfInterceptor({
    required this.dio,
    required this.readCookie,
    required this.fetchCsrfCookie,
  });

  static const String cookieName = 'XSRF-TOKEN';
  static const String headerName = 'X-XSRF-TOKEN';
  static const String _retried = 'xsrfRetried';

  /// The Dio the request is sent again with.
  final Dio dio;

  final CookieReader readCookie;

  /// GETs `<apiBaseUrl>/sanctum/csrf-cookie` with the browser's credentials.
  final Future<void> Function() fetchCsrfCookie;

  static bool _changesSomething(RequestOptions options) => !const <String>{
    'GET',
    'HEAD',
    'OPTIONS',
  }.contains(options.method.toUpperCase());

  @override
  Future<void> onRequest(
    RequestOptions options,
    RequestInterceptorHandler handler,
  ) async {
    if (_changesSomething(options)) {
      String? token = readCookie(cookieName);
      if (token == null) {
        await _fetch();
        token = readCookie(cookieName);
      }
      if (token != null) {
        options.headers[headerName] = Uri.decodeComponent(token);
      }
    }
    handler.next(options);
  }

  @override
  Future<void> onError(
    DioException err,
    ErrorInterceptorHandler handler,
  ) async {
    final RequestOptions options = err.requestOptions;
    if (err.response?.statusCode != 419 ||
        !_changesSomething(options) ||
        options.extra[_retried] == true) {
      handler.next(err);
      return;
    }
    await _fetch();
    options.extra[_retried] = true;
    // onRequest puts the fresh cookie in the header again.
    options.headers.remove(headerName);
    try {
      handler.resolve(await dio.fetch<dynamic>(options));
    } on DioException catch (again) {
      handler.next(again);
    }
  }

  Future<void> _fetch() async {
    try {
      await fetchCsrfCookie();
    } catch (_) {
      // The request then fails with the server's own answer.
    }
  }
}
