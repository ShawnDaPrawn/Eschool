import 'dart:io';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:hive/hive.dart';
import 'package:eschool_academics/src/registers/register_store.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  late Directory directory;
  setUp(() async {
    directory = await Directory.systemTemp.createTemp(
      'academic-register-cache-',
    );
    Hive.init(directory.path);
    FlutterSecureStorage.setMockInitialValues({});
  });
  tearDown(() async {
    await SecureRegisterStore.purge();
    await Hive.close();
    await directory.delete(recursive: true);
  });
  test(
    'encrypted registers survive a box restart without plaintext names on disk',
    () async {
      final store = await SecureRegisterStore.open(7);
      await store.write('1:register:4', {
        'name': 'Private synthetic learner name',
        'queue': [
          {'operation_id': 'retained-operation'},
        ],
      });
      await Hive.close();
      final bytes = await File(
        '${directory.path}/academic_register_cache_v1.hive',
      ).readAsBytes();
      expect(
        String.fromCharCodes(bytes),
        isNot(contains('Private synthetic learner name')),
      );
      final reopened = await SecureRegisterStore.open(7);
      expect(
        (await reopened.read('1:register:4'))!['queue'][0]['operation_id'],
        'retained-operation',
      );
    },
  );
  test(
    'account changes and logout purge prior data and reject late old-account writes',
    () async {
      final old = await SecureRegisterStore.open(7);
      await old.write('1:register:4', {'private': 'Synthetic roster'});
      final other = await SecureRegisterStore.open(8);
      expect(await other.keys(), isEmpty);
      await expectLater(
        old.write('1:register:4', {'private': 'Late old roster'}),
        throwsStateError,
      );
      await other.write('2:register:5', {'private': 'Other account'});
      await SecureRegisterStore.purge();
      await expectLater(other.read('2:register:5'), throwsStateError);
      final signedBackIn = await SecureRegisterStore.open(7);
      expect(await signedBackIn.keys(), isEmpty);
    },
  );
}
