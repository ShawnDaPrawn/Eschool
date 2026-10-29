import 'package:eschool_session/src/auth/auth_mode.dart';
import 'package:hive/hive.dart';

/// Names in the Hive box both apps have used since their first release. They
/// must not change, or an app update signs everybody out.
// ignore: avoid_classes_with_only_static_members
abstract final class SessionBoxKeys {
  static const String authBox = 'authBox';
  static const String jwtToken = 'jwtToken';
  static const String isLogIn = 'isLogIn';
  static const String hasSubscribedToNotificationTopics =
      'hasSubscribedToNotificationTopics';
  static const String meContext = 'meContext';

  /// When [meContext] was fetched, in milliseconds since the epoch.
  static const String meContextFetchedAt = 'meContextFetchedAt';

  /// The child a guardian picked in the child switcher (their learner id).
  static const String selectedChildId = 'selectedChildId';

  /// The school picked in the school switcher, sent as `X-School-Id`.
  static const String selectedSchoolId = 'selectedSchoolId';
}

/// Who is signed in on this device. Each app's AuthRepository reads and
/// writes the session through this, and adds its own profile data.
abstract interface class SessionRepository {
  AuthMode get mode;

  /// Changes whenever login credentials or signed-in state are replaced.
  int get generation;

  bool get isSignedIn;
  Future<void> setSignedIn(bool value);

  /// The bearer token, or an empty string when nobody is signed in or the
  /// session is a cookie ([AuthMode.cookie]).
  String get token;
  Future<void> setToken(String value);

  bool get hasSubscribedToNotificationTopics;
  Future<void> setHasSubscribedToNotificationTopics(bool value);
}

/// The session in Hive's `authBox`, where both apps have always kept it.
/// With [AuthMode.cookie] the token is never written: the browser holds the
/// session cookie instead.
class HiveSessionRepository implements SessionRepository {
  HiveSessionRepository({AuthMode? mode, Box<dynamic>? box})
    : mode = mode ?? AuthMode.current,
      _box = box;

  @override
  final AuthMode mode;

  final Box<dynamic>? _box;

  int _generation = 0;

  @override
  int get generation => _generation;

  Box<dynamic> get _authBox => _box ?? Hive.box(SessionBoxKeys.authBox);

  @override
  bool get isSignedIn => _authBox.get(SessionBoxKeys.isLogIn) ?? false;

  @override
  Future<void> setSignedIn(bool value) {
    _generation++;
    return _authBox.put(SessionBoxKeys.isLogIn, value);
  }

  @override
  String get token => mode == AuthMode.bearer
      ? _authBox.get(SessionBoxKeys.jwtToken) ?? ''
      : '';

  @override
  Future<void> setToken(String value) {
    _generation++;
    return _authBox.put(
      SessionBoxKeys.jwtToken,
      mode == AuthMode.bearer ? value : '',
    );
  }

  @override
  bool get hasSubscribedToNotificationTopics =>
      _authBox.get(SessionBoxKeys.hasSubscribedToNotificationTopics) ?? false;

  @override
  Future<void> setHasSubscribedToNotificationTopics(bool value) =>
      _authBox.put(SessionBoxKeys.hasSubscribedToNotificationTopics, value);
}

/// A session kept only in memory, for tests and the integration smoke flows.
class InMemorySessionRepository implements SessionRepository {
  InMemorySessionRepository({this.mode = AuthMode.bearer});

  @override
  final AuthMode mode;

  @override
  bool isSignedIn = false;

  int _generation = 0;

  @override
  int get generation => _generation;

  String _token = '';

  @override
  bool hasSubscribedToNotificationTopics = false;

  @override
  String get token => mode == AuthMode.bearer ? _token : '';

  @override
  Future<void> setSignedIn(bool value) async {
    _generation++;
    isSignedIn = value;
  }

  @override
  Future<void> setToken(String value) async {
    _generation++;
    _token = mode == AuthMode.bearer ? value : '';
  }

  @override
  Future<void> setHasSubscribedToNotificationTopics(bool value) async =>
      hasSubscribedToNotificationTopics = value;
}
