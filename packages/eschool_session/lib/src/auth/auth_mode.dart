import 'package:flutter/foundation.dart';

/// How the app proves to the server who is signed in.
enum AuthMode {
  /// A Sanctum token from the sign-in response, kept on the device and sent
  /// as `Authorization: Bearer ...`. What the phone apps have always done.
  bearer,

  /// Sanctum's session cookie for the portal origins: the browser keeps it,
  /// every request is sent with credentials and no token is stored. What the
  /// web portals use.
  cookie;

  /// The one switch: a web build uses [cookie] unless it was built with
  /// `--dart-define=WEB_AUTH_MODE=bearer` (for a server that does not accept
  /// the portal origins yet); every other build uses [bearer].
  static AuthMode get current => forPlatform(isWeb: kIsWeb);

  static const String _webAuthModeDefine = String.fromEnvironment(
    'WEB_AUTH_MODE',
    defaultValue: 'cookie',
  );

  /// [current] for any platform and define, so the rule can be tested.
  static AuthMode forPlatform({
    required bool isWeb,
    String webAuthMode = _webAuthModeDefine,
  }) {
    if (!isWeb) {
      return bearer;
    }
    return webAuthMode == 'bearer' ? bearer : cookie;
  }
}
