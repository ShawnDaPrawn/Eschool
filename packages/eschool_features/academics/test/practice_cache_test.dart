import 'dart:convert';
import 'dart:io';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:hive/hive.dart';
import 'practice_fixture.dart';

class FailingPracticeStore extends MemoryContentStore {
  bool failWrite = false;
  @override
  Future<void> write(String key, Map<String, dynamic> value) async {
    if (failWrite) {
      throw const FileSystemException('Synthetic unavailable disk');
    }
    await super.write(key, value);
  }
}

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  late FakePracticeRepository repo;
  late FailingPracticeStore store;
  late PracticeCache cache;
  late DateTime clock;
  bool writable = true;
  setUp(() {
    repo = FakePracticeRepository();
    store = FailingPracticeStore();
    clock = practiceTime;
    writable = true;
    cache = PracticeCache(
      repo,
      store: () async => store,
      clock: () => clock,
      canQueue: () => writable,
    );
  });
  tearDown(() => cache.close());
  test(
    'exact decimal feedback preserves precision and rejects expressions or unknown options',
    () {
      for (final value in ['.5', '00.500000', '0.5']) {
        expect(LocalPracticeAnswers.number(value), '0.5');
      }
      expect(LocalPracticeAnswers.number('-0.000000'), '0');
      for (final value in [
        '1/2',
        '5e-1',
        '0,5',
        '0.1234567',
        '1234567890123',
      ]) {
        expect(() => LocalPracticeAnswers.number(value), throwsFormatException);
      }
    },
  );
  test(
    'explicit own copy and immutable pending answer survive fresh cache instances',
    () async {
      expect(await cache.read(11, 10), isNull);
      await cache.save(practiceSet(), requestedAt: clock, writable: true);
      final answer = practiceAnswer();
      await cache.queue(answer);
      await cache.queue(answer);
      final other = PracticeCache(
        repo,
        store: () async => store,
        clock: () => clock,
        canQueue: () => true,
      );
      expect((await other.read(11, 10))!.pending.single.body, answer.body);
      expect(await other.read(12, 10), isNull);
      await expectLater(
        cache.queue(practiceAnswer(answer: '0.25')),
        throwsStateError,
      );
      expect((await cache.read(11, 10))!.pending.length, 1);
      other.close();
    },
  );
  test(
    'expired copy hides bodies but retains bounded exact sync after fresh authorization',
    () async {
      await cache.save(practiceSet(), requestedAt: clock, writable: true);
      await cache.queue(practiceAnswer());
      clock = clock.add(const Duration(days: 2));
      repo.time = clock;
      expect(await cache.read(11, 10), isNull);
      expect(await cache.saved(), isEmpty);
      expect((await cache.pending()).single.count, 1);
      final raw = await store.read((await store.keys()).single);
      expect(raw!['set'], isNull);
      final result = await cache.synchronize(11, 10);
      expect(result.completed, isTrue);
      expect(repo.accessReads, 1);
      expect(repo.dailyReads, 2);
      expect(repo.writes.single.body, practiceAnswer().body);
      expect(await cache.pending(), isEmpty);
    },
  );
  test(
    'unknown sync outcome preserves UUID and changed input cannot overwrite it',
    () async {
      await cache.save(practiceSet(), requestedAt: clock, writable: true);
      await cache.queue(practiceAnswer());
      repo.reviewError = const FileSystemException('Synthetic lost connection');
      await expectLater(
        cache.synchronize(11, 10),
        throwsA(isA<FileSystemException>()),
      );
      expect(
        (await cache.read(11, 10))!.pending.single.body,
        practiceAnswer().body,
      );
      repo.reviewError = null;
      await cache.synchronize(11, 10);
      expect(repo.writes.length, 2);
      expect(jsonEncode(repo.writes[0].body), jsonEncode(repo.writes[1].body));
    },
  );
  test(
    'missing or read-only offline grant cannot queue even when the profile allows writes',
    () async {
      await cache.save(practiceSet(), requestedAt: clock);
      expect((await cache.read(11, 10))!.writable, isFalse);
      await expectLater(cache.queue(practiceAnswer()), throwsStateError);
      await cache.synchronize(11, 10);
      expect((await cache.read(11, 10))!.writable, isTrue);
      await cache.queue(practiceAnswer());
      expect((await cache.read(11, 10))!.pending.length, 1);
    },
  );
  test(
    'read-only and revoked source cannot send saved review bodies',
    () async {
      await cache.save(practiceSet(), requestedAt: clock, writable: true);
      await cache.queue(practiceAnswer());
      writable = false;
      await expectLater(cache.queue(practiceAnswer()), throwsStateError);
      repo.writable = false;
      await cache.synchronize(11, 10);
      expect(repo.writes, isEmpty);
      repo.error = ApiException(
        ApiErrorCodes.defaultErrorMessageKey,
        statusCode: 403,
      );
      await expectLater(
        cache.synchronize(11, 10),
        throwsA(isA<ApiException>()),
      );
      expect(await store.keys(), isEmpty);
      expect(repo.writes, isEmpty);
    },
  );
  test(
    'clock rollback and seven-day retention erase copies without sending answers',
    () async {
      await cache.save(practiceSet(), requestedAt: clock, writable: true);
      await cache.queue(practiceAnswer());
      clock = clock.subtract(const Duration(seconds: 1));
      await expectLater(cache.read(11, 10), throwsFormatException);
      expect(await store.keys(), isEmpty);
      clock = practiceTime;
      await cache.save(practiceSet(), requestedAt: clock, writable: true);
      await cache.queue(practiceAnswer());
      clock = clock.add(const Duration(days: 8));
      await expectLater(cache.read(11, 10), throwsFormatException);
      expect(await store.keys(), isEmpty);
      expect(repo.writes, isEmpty);
    },
  );
  test('temporary disk failure preserves existing pending work', () async {
    await cache.save(practiceSet(), requestedAt: clock, writable: true);
    await cache.queue(practiceAnswer());
    store.failWrite = true;
    await expectLater(cache.read(11, 10), throwsA(isA<FileSystemException>()));
    store.failWrite = false;
    expect(
      (await cache.read(11, 10))!.pending.single.body,
      practiceAnswer().body,
    );
  });
  test(
    'fresh withdrawn group removes its copy and pending work before offline reopening',
    () async {
      await cache.save(practiceSet(), requestedAt: clock, writable: true);
      await cache.queue(practiceAnswer());
      await cache.retainGroups({99});
      expect(await cache.read(11, 10), isNull);
      expect(await cache.pending(), isEmpty);
      expect(repo.writes, isEmpty);
    },
  );
  test(
    'expired empty copies release capacity before the next download without losing pending work',
    () async {
      for (var i = 10; i < 30; i++) {
        await cache.save(
          practiceSet(id: i),
          requestedAt: clock,
          writable: true,
        );
      }
      await cache.queue(practiceAnswer());
      clock = clock.add(const Duration(days: 2));
      repo.time = clock;
      await cache.save(
        practiceSet(id: 99, time: clock),
        requestedAt: clock,
        writable: true,
      );
      expect((await store.keys()).length, 2);
      expect((await cache.pending()).single.set, 10);
      expect(await cache.read(11, 10), isNull);
      expect((await cache.read(11, 99))!.set.id, 99);
    },
  );
  test('closed context cannot read or write a previous scope', () async {
    await cache.save(practiceSet(), requestedAt: clock, writable: true);
    cache.close();
    await expectLater(cache.read(11, 10), throwsStateError);
    await expectLater(cache.queue(practiceAnswer()), throwsStateError);
  });
  group('production encrypted store', () {
    late Directory directory;
    setUp(() async {
      directory = await Directory.systemTemp.createTemp(
        'academic-practice-private-',
      );
      Hive.init(directory.path);
      FlutterSecureStorage.setMockInitialValues({});
    });
    tearDown(() async {
      await SecurePracticeStore.purge();
      await Hive.close();
      await directory.delete(recursive: true);
    });
    test(
      'restart retains pending answer with no plaintext question/answer on disk',
      () async {
        cache.close();
        cache = PracticeCache(
          repo,
          store: () =>
              SecurePracticeStore.open(12, 1, active: () => cache.isActive),
          clock: () => clock,
          canQueue: () => true,
        );
        await cache.save(practiceSet(), requestedAt: clock, writable: true);
        await cache.queue(practiceAnswer());
        cache.close();
        await Hive.close();
        final bytes = await File(
          '${directory.path}/academic_practice_cache_v1.hive',
        ).readAsBytes();
        expect(
          String.fromCharCodes(bytes),
          isNot(contains('One half as a decimal?')),
        );
        expect(
          String.fromCharCodes(bytes),
          isNot(contains('Divide one by two')),
        );
        cache = PracticeCache(
          repo,
          store: () =>
              SecurePracticeStore.open(12, 1, active: () => cache.isActive),
          clock: () => clock,
          canQueue: () => true,
        );
        expect(
          (await cache.read(11, 10))!.pending.single.body,
          practiceAnswer().body,
        );
      },
    );
    test(
      'known revocation destroys its durable key even after its route closed',
      () async {
        cache.close();
        cache = PracticeCache(
          repo,
          store: () =>
              SecurePracticeStore.open(12, 1, active: () => cache.isActive),
          clock: () => clock,
          canQueue: () => true,
          onRevoke: () => SecurePracticeStore.revokeScope(12, 1),
        );
        await cache.save(practiceSet(), requestedAt: clock, writable: true);
        await cache.queue(practiceAnswer());
        cache.close();
        await expectLater(cache.revoke(), throwsStateError);
        final reopened = await SecurePracticeStore.open(
          12,
          1,
          active: () => true,
        );
        expect(await reopened.keys(), isEmpty);
      },
    );
    test(
      'late revocation erases only its original scope even after route disposal',
      () async {
        final first = await SecurePracticeStore.open(12, 1, active: () => true);
        await first.write('old', {'body': 'Synthetic learner question'});
        await SecurePracticeStore.revokeScope(12, 1);
        await expectLater(first.read('old'), throwsStateError);
        final second = await SecurePracticeStore.open(
          12,
          2,
          active: () => true,
        );
        await second.write('new', {'body': 'Replacement school question'});
        await SecurePracticeStore.revokeScope(12, 1);
        expect(
          (await second.read('new'))!['body'],
          'Replacement school question',
        );
      },
    );
    test(
      'quota rejects additional copy without evicting pending answers; scope change destroys old key',
      () async {
        var active = true;
        final first = await SecurePracticeStore.open(
          12,
          1,
          active: () => active,
        );
        for (var i = 0; i < 20; i++) {
          await first.write('$i', {'body': 'Synthetic pending answer $i'});
        }
        await expectLater(
          first.write('21', {'body': 'extra'}),
          throwsStateError,
        );
        expect((await first.keys()).length, 20);
        active = false;
        final second = await SecurePracticeStore.open(
          12,
          2,
          active: () => true,
        );
        expect(await second.keys(), isEmpty);
        await expectLater(
          first.write('old', {'body': 'late'}),
          throwsStateError,
        );
        await second.write('new', {'body': 'new'});
        await SecurePracticeStore.purge();
        await expectLater(second.read('new'), throwsStateError);
      },
    );
  });
}
