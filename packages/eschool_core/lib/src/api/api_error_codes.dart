/// The codes [ApiException.errorMessage] carries when the app, not the server,
/// decided what went wrong. Each app maps them to a translated message.
// ignore: avoid_classes_with_only_static_members
class ApiErrorCodes {
  static const String internetServerError = '500';
  static const String unauthorizedAccess = '401';
  static const String fileNotFound = '404';
  static const String noInternet = '301';
  static const String defaultError = '302';

  /// Label key used when the failure is none of the above.
  static const String defaultErrorMessageKey = 'defaultErrorMessage';
}
