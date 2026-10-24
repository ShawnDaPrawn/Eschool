import 'dart:async';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter_test/flutter_test.dart';
import 'content_cubit_test.dart' show FakeContentRepository;

ContentReader fresh(
  DateTime time, {
  int learner = 11,
  int version = 11,
  int item = 10,
  int school = 1,
  int account = 12,
}) => ContentReader.fromJson({
  ...contentReaderFixture(learner: learner, school: school, account: account),
  'id': version,
  'content_item_id': item,
  'server_time': time.toIso8601String(),
  'offline_until': time.add(const Duration(days: 1)).toIso8601String(),
});

class OfflineContentRepository extends FakeContentRepository {
  DateTime time = DateTime.utc(2026, 10, 24, 9);
  Set<int> allowed = {11};
  int validations = 0;
  Future<ContentSavedAccess> Function()? check;
  @override
  Future<ContentSavedAccess> savedAccess(int learner, Set<int> versions) async {
    validations++;
    if (error != null) throw error!;
    return check != null
        ? await check!()
        : ContentSavedAccess(
            schoolId: schoolId,
            readerId: readerId,
            learnerId: learner,
            allowedVersionIds: allowed.intersection(versions),
            serverTime: time,
            offlineUntil: time.add(const Duration(days: 1)),
          );
  }

  @override
  Future<ContentReader> reader(int learner, int version) async {
    if (error != null) throw error!;
    return fetchReader != null
        ? await fetchReader!()
        : fresh(time, learner: learner, version: version);
  }
}

void main() {
  late OfflineContentRepository repo;
  late MemoryContentStore store;
  late ContentReaderCache cache;
  late DateTime clock;
  setUp(() {
    repo = OfflineContentRepository();
    store = MemoryContentStore();
    clock = repo.time;
    cache = ContentReaderCache(
      repo,
      store: () async => store,
      clock: () => clock,
    );
  });
  tearDown(() => cache.close());
  test(
    'only an explicit verified copy can be read and another child stays isolated',
    () async {
      expect(await cache.read(11, 11), isNull);
      await cache.save(fresh(clock), requestedAt: clock);
      expect((await cache.read(11, 11))!.reader.markdown, contains('One half'));
      expect(await cache.read(12, 11), isNull);
      expect(await cache.list(12), isEmpty);
      repo.schoolId = 2;
      expect(await cache.read(11, 11), isNull);
    },
  );
  test(
    'expiry uses original request time and purges clock rollback and corruption',
    () async {
      final reader = fresh(clock);
      await cache.save(reader, requestedAt: clock);
      clock = clock.add(const Duration(hours: 23));
      expect(await cache.read(11, 11), isNotNull);
      clock = clock.subtract(const Duration(minutes: 1));
      expect(await cache.read(11, 11), isNull);
      clock = repo.time;
      await cache.save(reader, requestedAt: clock);
      clock = clock.add(const Duration(days: 1));
      expect(await cache.read(11, 11), isNull);
      expect(await store.keys(), isEmpty);
      clock = repo.time;
      await cache.save(reader, requestedAt: clock);
      final key = (await store.keys()).single,
          raw = (await store.read((await store.keys()).single))!;
      raw['reader']['markdown'] = 'tampered';
      await store.write(key, raw);
      expect(await cache.read(11, 11), isNull);
      expect(await store.keys(), isEmpty);
    },
  );
  test('a delayed save cannot extend the server or request lease', () async {
    final started = clock, reader = fresh(clock);
    clock = clock.add(const Duration(hours: 12));
    await cache.save(reader, requestedAt: started);
    expect(
      (await cache.read(11, 11))!.expiresAt,
      started.add(const Duration(days: 1)),
    );
    clock = clock.add(const Duration(hours: 12));
    await expectLater(
      cache.save(reader, requestedAt: started),
      throwsStateError,
    );
  });
  test(
    'cache has a strict twenty-copy limit and eviction leaves recent copies',
    () async {
      for (var i = 1; i <= 21; i++) {
        await cache.save(
          fresh(clock, version: i, item: i),
          requestedAt: clock,
        );
        clock = clock.add(const Duration(seconds: 1));
      }
      expect((await store.keys()).length, 20);
      expect(await cache.read(11, 1), isNull);
      expect(await cache.read(11, 21), isNotNull);
    },
  );
  test(
    'fresh batch authority removes withdrawal and does not resurrect a removed copy',
    () async {
      await cache.save(fresh(clock), requestedAt: clock);
      repo.allowed = {};
      await cache.revalidate(11);
      expect(await cache.read(11, 11), isNull);
      repo.allowed = {11};
      await cache.save(fresh(clock), requestedAt: clock);
      final pending = Completer<ContentSavedAccess>();
      repo.check = () => pending.future;
      final checking = cache.revalidate(11);
      await Future<void>.delayed(Duration.zero);
      await cache.remove(11, 11);
      pending.complete(
        ContentSavedAccess(
          schoolId: 1,
          readerId: 12,
          learnerId: 11,
          allowedVersionIds: {11},
          serverTime: clock,
          offlineUntil: clock.add(const Duration(days: 1)),
        ),
      );
      await checking;
      expect(await cache.read(11, 11), isNull);
    },
  );
  test('wrong batch identity cannot renew saved copies', () async {
    await cache.save(fresh(clock), requestedAt: clock);
    repo.check = () async => ContentSavedAccess(
      schoolId: 2,
      readerId: 12,
      learnerId: 11,
      allowedVersionIds: {11},
      serverTime: clock,
      offlineUntil: clock.add(const Duration(days: 1)),
    );
    await expectLater(cache.revalidate(11), throwsStateError);
  });
  test(
    'only transport loss permits offline reader; HTTP refusal clears the copy',
    () async {
      await cache.save(fresh(clock), requestedAt: clock);
      final reader = ContentReaderCubit(
        repo,
        learner: 11,
        version: 11,
        cache: cache,
      );
      addTearDown(reader.close);
      repo.error = ApiException('offline', isTransportFailure: true);
      await reader.load();
      expect(reader.state.offline, true);
      expect(reader.state.reader, isNotNull);
      expect(repo.reads, 0);
      repo.error = ApiException('server error', statusCode: 500);
      await reader.load();
      expect(reader.state.reader, isNull);
      repo.error = ApiException('withdrawn', statusCode: 404);
      await reader.load();
      expect(reader.state.reader, isNull);
      expect(await cache.read(11, 11), isNull);
    },
  );
  test(
    'library checks saved authority before listing, then falls back to current-child copies',
    () async {
      await cache.save(fresh(clock), requestedAt: clock);
      await cache.save(fresh(clock, learner: 12), requestedAt: clock);
      final library = ContentLibraryCubit(repo, 11, cache: cache);
      addTearDown(library.close);
      await library.load();
      expect(repo.validations, 1);
      expect(library.state.offline, false);
      repo.error = ApiException('offline', isTransportFailure: true);
      await library.load();
      expect(library.state.offline, true);
      expect(library.state.items.length, 1);
      repo.error = ApiException('revoked', statusCode: 403);
      await library.load();
      expect(library.state.items, isEmpty);
      expect(await cache.list(11), isEmpty);
      expect(await cache.list(12), hasLength(1));
    },
  );
  test(
    'save rechecks online authority and expired visible offline body clears',
    () async {
      final reader = ContentReaderCubit(
        repo,
        learner: 11,
        version: 11,
        cache: cache,
      );
      addTearDown(reader.close);
      await reader.load();
      expect(await store.keys(), isEmpty);
      repo.error = ApiException('withdrawn', statusCode: 404);
      await reader.saveOffline();
      expect(reader.state.reader, isNull);
      expect(await store.keys(), isEmpty);
      repo.error = null;
      await reader.load();
      await reader.saveOffline();
      expect(reader.state.saved, true);
      repo.error = ApiException('offline', isTransportFailure: true);
      await reader.load();
      expect(reader.state.offline, true);
      clock = clock.add(const Duration(days: 1));
      await reader.load();
      expect(reader.state.reader, isNull);
      expect(await store.keys(), isEmpty);
    },
  );
  test(
    'late answer after cache closure cannot persist prior-context material',
    () async {
      final reader = ContentReaderCubit(
        repo,
        learner: 11,
        version: 11,
        cache: cache,
      );
      addTearDown(reader.close);
      await reader.load();
      final pending = Completer<ContentReader>();
      repo.fetchReader = () => pending.future;
      final saving = reader.saveOffline();
      cache.close();
      reader.clear();
      pending.complete(fresh(clock));
      await saving;
      expect(await store.keys(), isEmpty);
      expect(reader.state.reader, isNull);
    },
  );
}
