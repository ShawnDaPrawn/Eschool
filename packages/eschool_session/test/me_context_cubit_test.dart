import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/eschool_session.dart';
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

  setUp(() {
    repository = FakeMeContextRepository();
    cache = InMemoryMeContextCache();
    cubit = MeContextCubit(repository: repository, cache: cache);
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
}
