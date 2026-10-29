import 'package:web/web.dart' as web;

/// An opaque, non-authorizing cookie rotated by the server on web sign-in/out.
/// Its shared domain matches the session cookie, so another tab/portal's
/// replacement is visible without exposing the HttpOnly authentication cookie.
String? browserSessionIdentity() {
  const name = 'navigateed-session-identity';
  for (final part in web.document.cookie.split(';')) {
    final separator = part.indexOf('=');
    if (separator > 0 && part.substring(0, separator).trim() == name) {
      return part.substring(separator + 1).trim();
    }
  }
  return null;
}
