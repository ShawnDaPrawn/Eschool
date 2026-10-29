import 'dart:convert';
import 'dart:io';
import 'package:eschool_session/src/storage/native_auth_box.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:hive/hive.dart';

void main() {
  late Directory directory;
  late String? key;
  const name = 'authBox', token = 'synthetic-private-credential';
  Future<Box<dynamic>> open() => NativeAuthBox.open(
    directory: directory.path,
    name: name,
    readKey: () async => key,
    writeKey: (value) async => key = value,
    otherLegacyDirectories: ['${directory.path}/old-documents'],
  );
  Future<void> seed() async {
    final old = await Hive.openBox<dynamic>(name, path: directory.path);
    await old.putAll({
      'jwtToken': token,
      'isLogIn': true,
      'profile': {
        'name': 'Synthetic parent',
        'children': [1, 2],
      },
      'selectedChildId': 2,
    });
    await old.close();
  }

  setUp(() async {
    directory = await Directory.systemTemp.createTemp('protected-auth-test-');
    key = null;
  });
  tearDown(() async {
    await Hive.close();
    await directory.delete(recursive: true);
  });
  test(
    'migrates complete identity and erases plaintext only after verification',
    () async {
      await seed();
      final documents = Directory('${directory.path}/old-documents');
      await documents.create();
      await File(
        '${directory.path}/authbox.hive',
      ).copy('${documents.path}/authbox.hive');
      final box = await open();
      expect(box.get('jwtToken'), token);
      expect(box.get('profile'), {
        'name': 'Synthetic parent',
        'children': [1, 2],
      });
      expect(box.get('selectedChildId'), 2);
      expect(await File('${directory.path}/authbox.hive').exists(), isFalse);
      expect(await File('${documents.path}/authbox.hive').exists(), isFalse);
      await box.put('selectedChildId', 1);
      await box.close();
      final bytes = await File(
        '${directory.path}/protected_auth_v1/authbox.hive',
      ).readAsBytes();
      expect(utf8.decode(bytes, allowMalformed: true), isNot(contains(token)));
      final reopened = await open();
      expect(reopened.get('jwtToken'), token);
      expect(reopened.get('selectedChildId'), 1);
    },
  );
  test('resumes an interrupted copy from its intact original', () async {
    await seed();
    key = base64UrlEncode(Hive.generateSecureKey());
    final path = '${directory.path}/protected_auth_v1';
    await Directory(path).create();
    final incomplete = await Hive.openBox<dynamic>(
      name,
      path: path,
      encryptionCipher: HiveAesCipher(base64Url.decode(key!)),
    );
    await incomplete.put('jwtToken', 'incomplete-value');
    await incomplete.close();
    final box = await open();
    expect(box.get('jwtToken'), token);
    expect(box.get('profile'), isA<Map>());
    expect(await File('${directory.path}/authbox.hive').exists(), isFalse);
  });
  test(
    'keystore read failure never falls back to a plaintext session',
    () async {
      await seed();
      final old = await File('${directory.path}/authbox.hive').readAsBytes();
      await expectLater(
        NativeAuthBox.open(
          directory: directory.path,
          name: name,
          readKey: () async => throw StateError('locked'),
        ),
        throwsStateError,
      );
      expect(Hive.isBoxOpen(name), isFalse);
      expect(await File('${directory.path}/authbox.hive').readAsBytes(), old);
    },
  );
  test('failed key persistence keeps the only original copy intact', () async {
    await seed();
    await expectLater(
      NativeAuthBox.open(
        directory: directory.path,
        name: name,
        readKey: () async => null,
        writeKey: (_) async {},
      ),
      throwsStateError,
    );
    expect(Hive.isBoxOpen(name), isFalse);
    expect(await File('${directory.path}/authbox.hive').exists(), isTrue);
  });
  test(
    'device restore without its key requires sign-in and cannot revive old credentials',
    () async {
      await seed();
      await (await open()).close();
      await seed();
      key = null;
      final box = await open();
      expect(box.get('isLogIn'), isNull);
      expect(box.get('jwtToken'), isNull);
      expect(box.get('profile'), isNull);
      expect(await File('${directory.path}/authbox.hive').exists(), isFalse);
    },
  );
  test(
    'malformed key fails closed without erasing an upgrade source',
    () async {
      await seed();
      key = base64UrlEncode([1, 2, 3]);
      await expectLater(open(), throwsStateError);
      expect(Hive.isBoxOpen(name), isFalse);
      expect(await File('${directory.path}/authbox.hive').exists(), isTrue);
    },
  );
  test('clearing a protected session preserves a later login', () async {
    await seed();
    final box = await open();
    await box.clear();
    await box.putAll({'jwtToken': 'synthetic-replacement', 'isLogIn': true});
    await box.close();
    final reopened = await open();
    expect(reopened.get('jwtToken'), 'synthetic-replacement');
    expect(reopened.get('profile'), isNull);
  });
}
