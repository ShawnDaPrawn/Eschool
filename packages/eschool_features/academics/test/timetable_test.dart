import 'dart:async';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter_test/flutter_test.dart';

class FakeTimetable implements TimetableRepository {
  @override
  int get schoolId => 7;
  AcademicTimetableWeekData current = AcademicTimetableWeekData.fromJson(
    timetableWeekFixture(manage: true),
  );
  Future<AcademicTimetableWeekData> Function(String? starts)? loader;
  Completer<AcademicTimetablePreviewData>? pendingPreview;
  Completer<AcademicTimetableNextData>? nextResponse;
  int configurations = 0, groupReads = 0;
  bool clashes = false;
  int? requestedLearner;
  ApiException? commitError, previewError;
  final commits = <AcademicTimetableCommitWrite>[];
  final previews = <List<AcademicLessonWrite>>[];
  @override
  Future<AcademicTimetableWeekData> week({
    String? startsOn,
    int? year,
    int? learner,
  }) async {
    requestedLearner = learner;
    return loader == null ? current : loader!(startsOn);
  }

  @override
  Future<AcademicTimetableNextData> next({int? learner}) async =>
      nextResponse == null
      ? AcademicTimetableNextData.fromJson({'lesson': timetableLessonFixture()})
      : nextResponse!.future;
  @override
  Future<AcademicTimetableConfigurationData> configuration(int year) async {
    configurations++;
    return AcademicTimetableConfigurationData.fromJson(
      timetableConfigurationFixture(),
    );
  }

  @override
  Future<List<TeachingGroup>> groups(int year) async {
    groupReads++;
    return (timetableGroupsFixture()['groups'] as List)
        .map((row) => TeachingGroup.fromJson(Map<String, dynamic>.from(row)))
        .toList();
  }

  @override
  Future<AcademicTimetablePreviewData> preview(
    List<AcademicLessonWrite> rows,
  ) async {
    previews.add(rows);
    if (previewError != null) throw previewError!;
    if (pendingPreview != null) return pendingPreview!.future;
    return previewResult(rows);
  }

  AcademicTimetablePreviewData previewResult(List<AcademicLessonWrite> rows) =>
      AcademicTimetablePreviewData(
        rows: rows,
        conflicts: clashes
            ? [
                AcademicTimetableClash(
                  row: 0,
                  lessonId: 9,
                  date: '2026-02-10',
                  reasons: ['room'],
                ),
              ]
            : [],
        canSave: !clashes,
        fingerprint: 'a' * 64,
      );
  @override
  Future<AcademicTimetableCommitData> commit(
    AcademicTimetableCommitWrite input,
  ) async {
    commits.add(input);
    if (commitError != null) throw commitError!;
    return AcademicTimetableCommitData(lessons: current.lessons);
  }
}

void main() {
  test(
    'a family never fetches staff configuration or teaching groups',
    () async {
      final repository = FakeTimetable()
        ..current = AcademicTimetableWeekData.fromJson(timetableWeekFixture());
      final cubit = TimetableCubit(repository, learner: 11);
      addTearDown(cubit.close);
      await cubit.load();
      expect(repository.requestedLearner, 11);
      expect(repository.configurations, 0);
      expect(repository.groupReads, 0);
      cubit.edit(cubit.state.week!.lessons.first);
      expect(cubit.state.draft, isNull);
    },
  );
  test(
    'an edit keeps revisions and only a matching server preview enables save',
    () async {
      final repository = FakeTimetable();
      final cubit = TimetableCubit(repository);
      addTearDown(cubit.close);
      await cubit.load();
      cubit.edit(cubit.state.week!.lessons.first);
      expect(await cubit.save(), isFalse);
      await cubit.preview();
      expect(repository.previews.single.single.revision, 1);
      cubit.change({'weekday': 3});
      expect(cubit.state.preview, isNull);
      await cubit.preview();
      expect(await cubit.save(), isTrue);
      expect(repository.commits.single.fingerprint, 'a' * 64);
      expect(cubit.state.saved, isTrue);
    },
  );
  test(
    'a conflict preserves placement inputs and prevents blind resubmission',
    () async {
      final repository = FakeTimetable()
        ..commitError = ApiException(
          'Preview changed',
          statusCode: 409,
          isApiMessage: true,
        );
      final cubit = TimetableCubit(repository);
      addTearDown(cubit.close);
      await cubit.load();
      cubit.edit(cubit.state.week!.lessons.first);
      await cubit.preview();
      expect(await cubit.save(), isFalse);
      expect(cubit.state.draft!['room_id'], 7);
      expect(cubit.state.preview, isNull);
      expect(cubit.state.error, 'Preview changed');
      expect(await cubit.save(), isFalse);
      expect(repository.commits.length, 1);
    },
  );
  test('a late week response cannot replace the newly selected week', () async {
    final old = Completer<AcademicTimetableWeekData>();
    final repository = FakeTimetable()
      ..loader = (date) => date == '2026-02-09'
          ? old.future
          : Future.value(
              AcademicTimetableWeekData.fromJson(
                timetableWeekFixture(startsOn: '2026-02-16'),
              ),
            );
    final cubit = TimetableCubit(repository);
    addTearDown(cubit.close);
    final pending = cubit.load(startsOn: '2026-02-09');
    await cubit.load(startsOn: '2026-02-16');
    old.complete(AcademicTimetableWeekData.fromJson(timetableWeekFixture()));
    await pending;
    expect(cubit.state.week!.startsOn, '2026-02-16');
  });
  test(
    'revoked rights clear every private placement and configuration value',
    () async {
      final repository = FakeTimetable()
        ..previewError = ApiException(
          'Access changed',
          statusCode: 403,
          isApiMessage: true,
        );
      final cubit = TimetableCubit(repository);
      addTearDown(cubit.close);
      await cubit.load();
      cubit.edit(cubit.state.week!.lessons.first);
      await cubit.preview();
      expect(cubit.state.week, isNull);
      expect(cubit.state.configuration, isNull);
      expect(cubit.state.draft, isNull);
      expect(cubit.state.preview, isNull);
    },
  );
  test('a late preview cannot authorize a placement in a new week', () async {
    final repository = FakeTimetable()
      ..pendingPreview = Completer<AcademicTimetablePreviewData>();
    final cubit = TimetableCubit(repository);
    addTearDown(cubit.close);
    await cubit.load();
    cubit.edit(cubit.state.week!.lessons.first);
    final pending = cubit.preview();
    await cubit.load(startsOn: '2026-02-16');
    repository.pendingPreview!.complete(
      repository.previewResult(repository.previews.single),
    );
    await pending;
    expect(cubit.state.preview, isNull);
    expect(cubit.state.draft, isNull);
  });
}
