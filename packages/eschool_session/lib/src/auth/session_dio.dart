import 'package:dio/dio.dart';
import 'package:eschool_session/src/auth/auth_mode.dart';

import 'session_dio_io.dart'
    if (dart.library.js_interop) 'session_dio_web.dart'
    as platform;

/// The [Dio] every API call goes through, set up for how the signed-in user
/// is known. Pass it to `ApiClient(createDio: ...)`.
///
/// With [AuthMode.cookie] (the web portals) requests carry the browser's
/// cookies for the API origin, and requests that change something send
/// Laravel's `X-XSRF-TOKEN` header, fetching the token from
/// `<apiBaseUrl>/sanctum/csrf-cookie` first when the browser has none. With
/// [AuthMode.bearer] it is a plain [Dio], as before.
Dio createSessionDio({required AuthMode mode, required String apiBaseUrl}) =>
    platform.createSessionDio(mode: mode, apiBaseUrl: apiBaseUrl);
