import 'package:dio/browser.dart';
import 'package:dio/dio.dart';
import 'package:eschool_session/src/auth/auth_mode.dart';
import 'package:web/web.dart' as web;

const String _xsrfCookie = 'XSRF-TOKEN';

/// The browser: with [AuthMode.cookie] every request is sent with the
/// cookies of the API origin, and writes carry the XSRF token Laravel set.
Dio createSessionDio({required AuthMode mode, required String apiBaseUrl}) {
  final bool withCredentials = mode == AuthMode.cookie;
  final Dio dio = Dio()
    ..httpClientAdapter = BrowserHttpClientAdapter(
      withCredentials: withCredentials,
    );
  if (withCredentials) {
    dio.interceptors.add(_XsrfInterceptor('$apiBaseUrl/sanctum/csrf-cookie'));
  }
  return dio;
}

/// Sanctum's SPA protection: a request that changes something must echo the
/// `XSRF-TOKEN` cookie in the `X-XSRF-TOKEN` header. The cookie is readable
/// here because the portals and the API share the parent domain the server
/// sets it on.
class _XsrfInterceptor extends Interceptor {
  _XsrfInterceptor(this.csrfCookieUrl);

  final String csrfCookieUrl;

  @override
  Future<void> onRequest(
    RequestOptions options,
    RequestInterceptorHandler handler,
  ) async {
    if (options.method != 'GET' && options.method != 'HEAD') {
      String? token = _readCookie(_xsrfCookie);
      if (token == null) {
        try {
          await (Dio()
                ..httpClientAdapter = BrowserHttpClientAdapter(
                  withCredentials: true,
                ))
              .get<dynamic>(csrfCookieUrl);
        } catch (_) {
          // The request below then fails with the server's own answer.
        }
        token = _readCookie(_xsrfCookie);
      }
      if (token != null) {
        options.headers['X-XSRF-TOKEN'] = Uri.decodeComponent(token);
      }
    }
    handler.next(options);
  }

  String? _readCookie(String name) {
    for (final String part in web.document.cookie.split(';')) {
      final int separator = part.indexOf('=');
      if (separator > 0 && part.substring(0, separator).trim() == name) {
        return part.substring(separator + 1).trim();
      }
    }
    return null;
  }
}
