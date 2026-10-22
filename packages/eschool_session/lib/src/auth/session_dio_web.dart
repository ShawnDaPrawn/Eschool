import 'package:dio/browser.dart';
import 'package:dio/dio.dart';
import 'package:eschool_session/src/auth/auth_mode.dart';
import 'package:eschool_session/src/auth/xsrf_interceptor.dart';
import 'package:web/web.dart' as web;

/// The browser: with [AuthMode.cookie] every request is sent with the
/// cookies of the API origin, and writes carry the XSRF token Laravel set.
Dio createSessionDio({required AuthMode mode, required String apiBaseUrl}) {
  final bool withCredentials = mode == AuthMode.cookie;
  final Dio dio = Dio()
    ..httpClientAdapter = BrowserHttpClientAdapter(
      withCredentials: withCredentials,
    );
  if (withCredentials) {
    dio.interceptors.add(
      XsrfInterceptor(
        dio: dio,
        readCookie: _readCookie,
        fetchCsrfCookie: () =>
            (Dio()
                  ..httpClientAdapter = BrowserHttpClientAdapter(
                    withCredentials: true,
                  ))
                .get<dynamic>('$apiBaseUrl/sanctum/csrf-cookie'),
      ),
    );
  }
  return dio;
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
