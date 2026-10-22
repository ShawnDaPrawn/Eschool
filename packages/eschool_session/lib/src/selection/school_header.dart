import 'package:dio/dio.dart';

/// Adds `X-School-Id` to every request once a school is chosen, so a person
/// at several schools is answered for the school they are working in (the
/// server checks the header against their memberships). A request that
/// already names a school keeps its own.
class SchoolHeaderInterceptor extends Interceptor {
  SchoolHeaderInterceptor(this.schoolId);

  static const String headerName = 'X-School-Id';

  /// The school to send, or `null` to send none (the server then uses the
  /// user's own school).
  final int? Function() schoolId;

  @override
  void onRequest(RequestOptions options, RequestInterceptorHandler handler) {
    final bool named = options.headers.keys.any(
      (key) => key.toLowerCase() == headerName.toLowerCase(),
    );
    if (!named) {
      int? id;
      try {
        id = schoolId();
      } catch (_) {
        // No store yet (before the app's boxes are open): send none.
      }
      if (id != null && id > 0) {
        options.headers[headerName] = '$id';
      }
    }
    handler.next(options);
  }
}

/// [dio] with the [SchoolHeaderInterceptor]: what each app's API client
/// wraps every [Dio] it makes in.
Dio withSchoolHeader(Dio dio, int? Function() schoolId) =>
    dio..interceptors.add(SchoolHeaderInterceptor(schoolId));
