import 'dart:io';

import 'package:eschool_session/eschool_session.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:hive/hive.dart';

void main() {
  late Directory directory;
  late Box<dynamic> box;

  setUp(() async {
    directory = await Directory.systemTemp.createTemp('session_test');
    Hive.init(directory.path);
    box = await Hive.openBox(SessionBoxKeys.authBox);
  });

  tearDown(() async {
    await Hive.close();
    await directory.delete(recursive: true);
  });

  test('a phone keeps the token where the apps always kept it', () async {
    final session = HiveSessionRepository(mode: AuthMode.bearer, box: box);

    await session.setToken('1|abc');
    await session.setSignedIn(true);

    expect(session.token, '1|abc');
    expect(session.isSignedIn, isTrue);
    // An app update must find an existing sign-in under the old keys.
    expect(box.get('jwtToken'), '1|abc');
    expect(box.get('isLogIn'), isTrue);
  });

  test('an earlier sign-in stored by the old code is still signed in', () {
    box.put('jwtToken', 'old-token');
    box.put('isLogIn', true);
    box.put('hasSubscribedToNotificationTopics', true);

    final session = HiveSessionRepository(mode: AuthMode.bearer, box: box);

    expect(session.isSignedIn, isTrue);
    expect(session.token, 'old-token');
    expect(session.hasSubscribedToNotificationTopics, isTrue);
  });

  test('a web portal never stores a bearer token', () async {
    final session = HiveSessionRepository(mode: AuthMode.cookie, box: box);

    await session.setToken('1|abc');
    await session.setSignedIn(true);

    expect(session.token, isEmpty);
    expect(box.get('jwtToken'), isEmpty);
    expect(session.isSignedIn, isTrue);
  });

  test('the in-memory session follows the same rule', () async {
    final bearer = InMemorySessionRepository();
    final cookie = InMemorySessionRepository(mode: AuthMode.cookie);

    await bearer.setToken('t');
    await cookie.setToken('t');

    expect(bearer.token, 't');
    expect(cookie.token, isEmpty);
  });

  group('AuthMode', () {
    test('phones use bearer tokens whatever the define says', () {
      expect(
        AuthMode.forPlatform(isWeb: false, webAuthMode: 'cookie'),
        AuthMode.bearer,
      );
    });

    test('the web uses the session cookie unless told otherwise', () {
      expect(
        AuthMode.forPlatform(isWeb: true, webAuthMode: 'cookie'),
        AuthMode.cookie,
      );
      expect(
        AuthMode.forPlatform(isWeb: true, webAuthMode: 'bearer'),
        AuthMode.bearer,
      );
    });

    test('tests run as a phone', () {
      expect(AuthMode.current, AuthMode.bearer);
    });
  });
}
