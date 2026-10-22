import 'package:dio/dio.dart';
import 'package:eschool_session/src/auth/auth_mode.dart';

/// Phones and tablets: the token goes in the Authorization header that
/// `ApiClient` adds, so nothing else is needed.
Dio createSessionDio({required AuthMode mode, required String apiBaseUrl}) =>
    Dio();
