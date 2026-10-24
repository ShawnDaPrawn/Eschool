import 'dart:io';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:hive/hive.dart';
import 'content_offline_test.dart' show OfflineContentRepository, fresh;

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  late Directory directory;
  setUp(() async {
    directory = await Directory.systemTemp.createTemp(
      'academic-content-private-',
    );
    Hive.init(directory.path);
    FlutterSecureStorage.setMockInitialValues({});
  });
  tearDown(() async {
    await SecureContentStore.purge();
    await Hive.close();
    await directory.delete(recursive: true);
  });
  test(
    'explicit Markdown copy survives restart with no plaintext title or body on disk',
    () async {
      final repo = OfflineContentRepository();
      late ContentReaderCache cache;
      cache = ContentReaderCache(
        repo,
        clock: () => repo.time,
        store: () =>
            SecureContentStore.open(12, 1, active: () => cache.isActive),
      );
      await cache.save(fresh(repo.time), requestedAt: repo.time);
      cache.close();
      await Hive.close();
      final bytes = await File(
        '${directory.path}/academic_content_cache_v1.hive',
      ).readAsBytes();
      expect(String.fromCharCodes(bytes), isNot(contains('Fractions')));
      expect(
        String.fromCharCodes(bytes),
        isNot(contains('One half equals two quarters.')),
      );
      late ContentReaderCache reopened;
      reopened = ContentReaderCache(
        repo,
        clock: () => repo.time,
        store: () =>
            SecureContentStore.open(12, 1, active: () => reopened.isActive),
      );
      expect((await reopened.read(11, 11))!.reader.title, 'Fractions');
      reopened.close();
    },
  );
  test(
    'concurrent store writers cannot exceed the physical copy bound',
    () async {
      final first = await SecureContentStore.open(12, 1, active: () => true);
      final second = await SecureContentStore.open(12, 1, active: () => true);
      await Future.wait([
        for (var i = 0; i < 30; i++)
          (i.isEven ? first : second).write('$i', {
            'body': 'Synthetic copy $i',
          }),
      ]);
      expect((await first.keys()).length, 20);
    },
  );

  test(
    'school and account changes erase old keys and old generations cannot write after logout',
    () async {
      var active = true;
      final old = await SecureContentStore.open(12, 1, active: () => active);
      await old.write('private', {'body': 'Synthetic confidential lesson'});
      active = false;
      final school = await SecureContentStore.open(12, 2, active: () => true);
      expect(await school.keys(), isEmpty);
      await school.write('new', {'body': 'New school copy'});
      await expectLater(
        SecureContentStore.open(12, 1, active: () => active),
        throwsStateError,
      );
      expect(await school.keys(), ['new']);
      final staging = await SecureContentStore.open(
        12,
        2,
        active: () => true,
        environment: 'https://staging.example/api/v2/',
      );
      expect(await staging.keys(), isEmpty);
      await expectLater(
        old.write('late', {'body': 'Old copy'}),
        throwsStateError,
      );
      final other = await SecureContentStore.open(13, 2, active: () => true);
      expect(await other.keys(), isEmpty);
      await other.write('new-account', {'body': 'Another account'});
      await SecureContentStore.purge();
      final signedIn = await SecureContentStore.open(13, 2, active: () => true);
      expect(await signedIn.keys(), isEmpty);
      await expectLater(
        other.write('late', {'body': 'Same-account prior generation'}),
        throwsStateError,
      );
    },
  );
}
