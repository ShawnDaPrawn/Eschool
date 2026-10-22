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
