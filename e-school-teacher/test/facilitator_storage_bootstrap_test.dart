import 'dart:io';

import 'package:eschool_teacher/app/app.dart';
import 'package:flutter/services.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:hive/hive.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  test(
    'native dedicated bootstrap enables encrypted feature storage only',
    () async {
      final root = await Directory.systemTemp.createTemp(
        'facilitator-storage-',
      );
      final support = Directory('${root.path}/private-support');
      final documents = await Directory('${root.path}/documents').create();
      final legacy = File('${documents.path}/authbox.hive');
      await legacy.writeAsString('legacy teacher sentinel');
      const channel = MethodChannel('plugins.flutter.io/path_provider');
      TestDefaultBinaryMessengerBinding.instance.defaultBinaryMessenger
          .setMockMethodCallHandler(
            channel,
            (call) async => switch (call.method) {
              'getApplicationSupportDirectory' => support.path,
              'getApplicationDocumentsDirectory' => documents.path,
              _ => throw PlatformException(code: 'unexpected-path-request'),
            },
          );
      addTearDown(() async {
        await Hive.close();
        TestDefaultBinaryMessengerBinding.instance.defaultBinaryMessenger
            .setMockMethodCallHandler(channel, null);
        await root.delete(recursive: true);
      });

      await prepareFacilitatorApp();
      final cipher = HiveAesCipher(Hive.generateSecureKey());
      final box = await Hive.openBox<dynamic>(
        'dedicated_feature_test',
        encryptionCipher: cipher,
      );
      await box.put('pending-approval', {'operation_id': 'exact-retry'});
      await box.close();
      final restored = await Hive.openBox<dynamic>(
        'dedicated_feature_test',
        encryptionCipher: cipher,
      );
      expect(restored.get('pending-approval'), {'operation_id': 'exact-retry'});
      expect(
        File('${support.path}/dedicated_feature_test.hive').existsSync(),
        isTrue,
      );
      expect(Hive.isBoxOpen('authBox'), isFalse);
      expect(Hive.isBoxOpen('settings'), isFalse);
      expect(await legacy.readAsString(), 'legacy teacher sentinel');
      expect(File('${support.path}/authbox.hive').existsSync(), isFalse);
    },
  );
}
