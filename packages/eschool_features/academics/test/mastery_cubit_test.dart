import 'dart:async';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter_test/flutter_test.dart';
import 'mastery_test_repository.dart';

void main() {
  test(
    'family chooses only offered own subjects and reloads selected subject',
    () async {
      final cubit = MasteryCubit(FakeMasteryRepository(), familyLearner: 11);
      addTearDown(cubit.close);
      await cubit.load();
      expect(cubit.state.view?.subjectId, 4);
      await cubit.subject(5);
      expect(cubit.state.view?.subjectId, 5);
      await cubit.subject(999);
      expect(cubit.state.view, isNull);
      expect(cubit.state.hidden, isTrue);
    },
  );
  test(
    'late response cannot restore data after lifecycle clear or school change',
    () async {
      for (final deactivate in [false, true]) {
        final repo = FakeMasteryRepository(),
            response = Completer<MasteryView>();
        repo.fetchFamily = (_, _) => response.future;
        final cubit = MasteryCubit(repo, familyLearner: 11);
        final loading = cubit.load();
        await Future<void>.delayed(Duration.zero);
        if (deactivate) {
          repo.isActive = false;
        } else {
          cubit.hide();
        }
        response.complete(MasteryView.fromJson(masteryFixture()));
        await loading;
        expect(cubit.state.view, isNull);
        expect(cubit.state.busy, isFalse);
        await cubit.close();
      }
    },
  );
  test('refused reload clears old evidence and retry can recover', () async {
    final repo = FakeMasteryRepository();
    final family = MasteryCubit(repo, familyLearner: 11);
    addTearDown(family.close);
    await family.load();
    expect(family.state.view, isNotNull);
    repo.error = ApiException('Revoked', statusCode: 403);
    await family.load();
    expect(family.state.view, isNull);
    expect(family.state.subjects, isNull);
    expect(family.state.error, contains('unavailable'));
    repo.error = null;
    await family.load();
    expect(family.state.view, isNotNull);
  });
  test(
    'staff roster pagination selects only the current page learner',
    () async {
      final repo = FakeMasteryRepository()..lastPage = 2;
      final cubit = MasteryCubit(repo);
      addTearDown(cubit.close);
      await cubit.load();
      expect(cubit.state.view?.learnerId, 11);
      await cubit.group(9, page: 2);
      expect(cubit.state.roster?.currentPage, 2);
      expect(cubit.state.view?.learnerId, 13);
      await cubit.learner(11);
      expect(cubit.state.view, isNull);
    },
  );
}
