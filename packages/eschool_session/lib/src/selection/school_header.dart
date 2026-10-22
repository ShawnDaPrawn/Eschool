import 'package:dio/dio.dart';

/// Adds `X-School-Id` to every request once a school is chosen, so a person
/// at several schools is answered for the school they are working in (the
/// server checks the header against their memberships). A request that
/// already names a school keeps its own.
///
/// Every request also says `X-Push-Topics: school`: this app joins each
/// school's own push topics (`SchoolTopics`), so the server lets a user who
/// is not at the first school sign in with it, which it refuses to app
/// versions that join the shared topic names from before Phase 1.
class SchoolHeaderInterceptor extends Interceptor {
  SchoolHeaderInterceptor(this.schoolId);

  static const String headerName = 'X-School-Id';

  static const String pushTopicsHeader = 'X-Push-Topics';
  static const String pushTopicsSchool = 'school';

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
    options.headers[pushTopicsHeader] = pushTopicsSchool;
    handler.next(options);
  }
}

/// [dio] with the [SchoolHeaderInterceptor]: what each app's API client
/// wraps every [Dio] it makes in.
Dio withSchoolHeader(Dio dio, int? Function() schoolId) =>
    dio..interceptors.add(SchoolHeaderInterceptor(schoolId));
