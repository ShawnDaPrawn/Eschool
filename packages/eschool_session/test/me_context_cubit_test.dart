import 'dart:async';

import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';

import 'fixtures.dart';

class FakeMeContextRepository implements MeContextRepository {
  MeContext? answer;
  Object? error;
  int calls = 0;

  @override
  Future<MeContext?> fetchContext() async {
    calls++;
    if (error != null) {
      throw error!;
    }
    return answer;
  }
}

class DeferredContexts implements MeContextRepository {
  final answers = List.generate(3, (_) => Completer<MeContext?>());
  int calls = 0;
  @override
  Future<MeContext?> fetchContext() => answers[calls++].future;
}

class HeldCache extends InMemoryMeContextCache {
  final started = Completer<void>();
  final release = Completer<void>();
  bool holdWrite = false, holdClear = false;
  @override
  Future<void> write(MeContext context, {DateTime? fetchedAt}) async {
    if (holdWrite) {
      holdWrite = false;
      started.complete();
      await release.future;
    }
    await super.write(context, fetchedAt: fetchedAt);
  }

  @override
  Future<void> clear() async {
    if (holdClear) {
      holdClear = false;
      started.complete();
      await release.future;
    }
    await super.clear();
  }
}

void main() {
  final MeContext tara = MeContext.fromJson(contextJson);
  late FakeMeContextRepository repository;
  late InMemoryMeContextCache cache;
  late MeContextCubit cubit;
  late DateTime now;

  setUp(() {
    repository = FakeMeContextRepository();
    cache = InMemoryMeContextCache();
    now = DateTime(2026, 10, 22, 9);
    cubit = MeContextCubit(
      repository: repository,
      cache: cache,
      clock: () => now,
    );
  });

  tearDown(() => cubit.close());

  group('session generation and cache ordering', () {
    for (final cookie in [false, true]) {
      test(
        'initial ${cookie ? 'cookie' : 'bearer'} school resolution retains fresh and offline context',
        () async {
          int? school;
          final source = FakeMeContextRepository()..answer = tara;
          final kept = InMemoryMeContextCache();
          final subject = MeContextCubit(
            repository: source,
            cache: kept,
            contextIdentity: () => SessionIdentity(
              generation: 1,
              token: cookie ? '' : 'synthetic-token',
              schoolId: school,
              browserSession: cookie ? 'opaque-cookie' : null,
            ),
          );
          await subject.load();
          school = tara.currentSchoolId;
          expect(await subject.refreshIfStale(), false);
          expect(source.calls, 1);
          expect(subject.context, tara);
          source.error = ApiException('offline');
          await subject.load();
          expect(subject.state, MeContextFailure('offline', context: tara));
          expect(kept.read(), tara);
          await subject.close();
        },
      );
    }
    for (final replacement in ['school', 'credentials', 'browser']) {
      test(
        'initial resolution never hides a real $replacement replacement',
        () async {
          int? school;
          var generation = 1, browser = 'opaque-cookie';
          final source = FakeMeContextRepository()..answer = tara;
          final kept = InMemoryMeContextCache();
          final subject = MeContextCubit(
            repository: source,
            cache: kept,
            contextIdentity: () => SessionIdentity(
              generation: generation,
              token: '',
              schoolId: school,
              browserSession: browser,
            ),
          );
          await subject.load();
          school = replacement == 'school' ? 2 : tara.currentSchoolId;
          if (replacement == 'credentials') generation++;
          if (replacement == 'browser') browser = 'replacement-cookie';
          source.error = ApiException('offline');
          final changed = subject.refreshIfStale();
          expect(subject.context, isNull);
          expect(await changed, true);
          expect(kept.read(), isNull);
          expect(subject.context, isNull);
          await subject.close();
        },
      );
    }
    MeContext person(int id) => MeContext.fromJson({
      ...contextJson,
      'user': {...contextJson['user'] as Map, 'id': id},
    });
    test('replacement refresh ignores the old fresh-cache timestamp', () async {
      var identity = 'parent A';
      final source = FakeMeContextRepository()..answer = person(12);
      final subject = MeContextCubit(
        repository: source,
        cache: InMemoryMeContextCache(),
        contextIdentity: () => identity,
      );
      await subject.load();
      identity = 'parent B';
      source.answer = person(99);
      final refreshed = subject.refreshIfStale();
      expect(subject.context, isNull);
      expect(await refreshed, isTrue);
      expect(subject.context?.user?.id, 99);
      await subject.close();
    });
    test(
      'school replacement erases a held old write even if the new fetch fails and the cubit restarts',
      () async {
        var identity = 1;
        final deferred = DeferredContexts();
        final kept = HeldCache()..holdWrite = true;
        final subject = MeContextCubit(
          repository: deferred,
          cache: kept,
          contextIdentity: () => identity,
        );
        final old = subject.load();
        deferred.answers[0].complete(person(12));
        await kept.started.future;
        identity = 2;
        final replacement = subject.load();
        expect(subject.context, isNull);
        kept.release.complete();
        await old;
        await Future<void>.delayed(Duration.zero);
        deferred.answers[1].completeError(ApiException('offline'));
        await replacement;
        expect(kept.read(), isNull);
        await subject.close();
        final offline = FakeMeContextRepository()
          ..error = ApiException('offline');
        final restarted = MeContextCubit(
          repository: offline,
          cache: kept,
          contextIdentity: () => identity,
        );
        final loading = restarted.load();
        expect(restarted.context, isNull);
        await loading;
        expect(restarted.context, isNull);
        await restarted.close();
      },
    );
    for (final completion in ['success', 'unsupported', 'failure']) {
      test(
        'cleared $completion cannot replace a new session or its load',
        () async {
          final deferred = DeferredContexts();
          final kept = InMemoryMeContextCache();
          final subject = MeContextCubit(repository: deferred, cache: kept);
          final old = subject.load();
          await Future<void>.delayed(Duration.zero);
          await subject.clear();
          final replacement = subject.load();
          await Future<void>.delayed(Duration.zero);
          expect(deferred.calls, 2);
          if (completion == 'failure') {
            deferred.answers[0].completeError(ApiException('old failure'));
          } else {
            deferred.answers[0].complete(
              completion == 'success' ? person(12) : null,
            );
          }
          await old;
          final joined = subject.load();
          expect(deferred.calls, 2);
          expect(subject.state, isA<MeContextLoading>());
          expect(subject.context, isNull);
          deferred.answers[1].complete(person(99));
          await Future.wait([replacement, joined]);
          expect(subject.context?.user?.id, 99);
          expect(kept.stored?.user?.id, 99);
          await subject.close();
        },
      );
    }
    test(
      'school identity change starts a separate load without cached fallback',
      () async {
        var identity = 1;
        final deferred = DeferredContexts();
        final subject = MeContextCubit(
          repository: deferred,
          cache: InMemoryMeContextCache(),
          contextIdentity: () => identity,
        );
        final old = subject.load();
        await Future<void>.delayed(Duration.zero);
        identity = 2;
        final next = subject.load();
        await Future<void>.delayed(Duration.zero);
        expect(deferred.calls, 2);
        deferred.answers[1].complete(person(99));
        await next;
        deferred.answers[0].complete(person(12));
        await old;
        expect(subject.context?.user?.id, 99);
        await subject.close();
      },
    );
    for (final write in [true, false]) {
      test(
        'cache ${write ? 'write' : 'clear'} cannot overtake replacement',
        () async {
          final deferred = DeferredContexts();
          final kept = HeldCache()..stored = person(12);
          final subject = MeContextCubit(repository: deferred, cache: kept);
          Future<void>? old;
          if (write) {
            kept.holdWrite = true;
            old = subject.load();
            await Future<void>.delayed(Duration.zero);
            deferred.answers[0].complete(person(12));
            await kept.started.future;
          } else {
            kept.holdClear = true;
          }
          final cleared = subject.clear();
          if (!write) await kept.started.future;
          final next = subject.load();
          expect(subject.context, isNull);
          await Future<void>.delayed(Duration.zero);
          expect(deferred.calls, write ? 1 : 0);
          kept.release.complete();
          await cleared;
          await Future<void>.delayed(Duration.zero);
          deferred.answers[write ? 1 : 0].complete(person(99));
          await next;
          if (old != null) await old;
          expect(kept.stored?.user?.id, 99);
          expect(subject.context?.user?.id, 99);
          await subject.close();
        },
      );
    }
  });

  test('starts with nothing', () {
    expect(cubit.state, const MeContextInitial());
    expect(cubit.context, isNull);
  });

  test('loads the context and keeps it for next time', () async {
    repository.answer = tara;

    await cubit.load();

    expect(cubit.state, MeContextLoaded(tara));
    expect(cache.stored, tara);
  });

  test('shows the kept context while loading', () async {
    cache.stored = tara;
    repository.answer = tara;
    final states = <MeContextState>[];
    final subscription = cubit.stream.listen(states.add);

    await cubit.load();
    await subscription.cancel();

    expect(states.first, MeContextLoading(context: tara));
    expect(states.first.context, tara);
  });

  test(
    'an older server without the endpoint leaves the app as it was',
    () async {
      cache.stored = tara;
      repository.answer = null;

      await cubit.load();

      expect(cubit.state, const MeContextUnavailable());
      expect(cubit.context, isNull);
      expect(cache.stored, isNull);
    },
  );

  test('a failed call keeps the context from last time', () async {
    cache.stored = tara;
    repository.error = ApiException('301');

    await cubit.load();

    expect(cubit.state, MeContextFailure('301', context: tara));
    expect(cubit.context, tara);
  });

  test('sign-out forgets the context', () async {
    repository.answer = tara;
    await cubit.load();

    await cubit.clear();

    expect(cubit.state, const MeContextInitial());
    expect(cache.stored, isNull);
  });

  test('keeps when the context was fetched', () async {
    repository.answer = tara;

    await cubit.load();

    expect(cache.storedAt, now);
    expect(cubit.fetchedAt, now);
  });

  test('asks once when asked twice at the same time', () async {
    repository.answer = tara;

    await Future.wait([cubit.load(), cubit.load()]);

    expect(repository.calls, 1);
  });

  group('refreshIfStale', () {
    test('leaves a context of a few minutes alone', () async {
      repository.answer = tara;
      await cubit.load();
      now = now.add(const Duration(minutes: 4));

      expect(await cubit.refreshIfStale(), isFalse);
      expect(repository.calls, 1);
    });

    test('fetches one older than a few minutes again', () async {
      repository.answer = tara;
      await cubit.load();
      now = now.add(const Duration(minutes: 6));

      expect(await cubit.refreshIfStale(), isTrue);
      expect(repository.calls, 2);
    });

    test('fetches when there is none, or the last try failed', () async {
      repository.error = ApiException('301');
      expect(await cubit.refreshIfStale(), isTrue);

      repository.error = null;
      repository.answer = tara;
      expect(await cubit.refreshIfStale(), isTrue);
      expect(cubit.state, MeContextLoaded(tara));
    });

    test('does not ask an older server again on every resume', () async {
      repository.answer = null;
      await cubit.load();

      expect(await cubit.refreshIfStale(), isFalse);
      now = now.add(const Duration(minutes: 6));
      expect(await cubit.refreshIfStale(), isTrue);
    });
  });

  testWidgets('coming back to the app refreshes an old context', (
    tester,
  ) async {
    repository.answer = tara;
    await tester.runAsync(cubit.load);
    var signedIn = true;
    var noticed = false;
    await tester.pumpWidget(
      BlocProvider.value(
        value: cubit,
        child: MeContextRefresher(
          isSignedIn: (_) => signedIn,
          takeChangeNotice: () async {
            final bool was = noticed;
            noticed = false;
            return was;
          },
          child: const SizedBox(),
        ),
      ),
    );

    Future<void> resume() async {
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.paused);
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await tester.pumpAndSettle();
    }

    await resume();
    expect(repository.calls, 1);

    now = now.add(const Duration(minutes: 10));
    await resume();
    expect(repository.calls, 2);

    // A context.changed push came while the app was away: fetch at once.
    noticed = true;
    await resume();
    expect(repository.calls, 3);

    // Nobody signed in: nothing is fetched.
    signedIn = false;
    now = now.add(const Duration(minutes: 10));
    await resume();
    expect(repository.calls, 3);
  });
}
