import 'dart:async';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_academics/src/discipline/discipline_repository.dart';
import 'package:eschool_academics/src/discipline/discipline_screen.dart';
import 'package:eschool_academics/src/discipline/family_discipline_screen.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

Map<String, dynamic> disciplineDefinition() => {
  'counter_scope': 'same_infraction',
  'reset': 'school_year',
  'rolling_days': null,
  'levels': [
    {
      'code': 'school_notice',
      'name': 'School notice',
      'threshold': 1,
      'kind': 'sanction',
      'sanctions': ['notice'],
    },
    {
      'code': 'hearing',
      'name': 'Principal hearing',
      'threshold': 2,
      'kind': 'hearing',
      'sanctions': <String>[],
    },
  ],
  'sanctions': [
    {'code': 'notice', 'name': 'School notice', 'kind': 'notice'},
  ],
  'infractions': [
    {'code': 'late', 'name': 'Late arrival', 'base_level': 'school_notice'},
  ],
};
Map<String, dynamic> disciplineMetadata({
  int id = 9,
  int revision = 1,
  String status = 'confirmed',
}) => {
  'id': id,
  'learner_id': 11,
  'learner_name': 'Asha',
  'session_year_id': 7,
  'grade_level_id': 5,
  'recorded_by': 12,
  'occurred_on': '2026-02-10',
  'infraction_code': 'late',
  'level_code': 'school_notice',
  'sanction_code': 'notice',
  'status': status,
  'hearing_decision': null,
  'revision': revision,
  'published': true,
};
Map<String, dynamic> disciplineDetail({
  int id = 9,
  int revision = 1,
  String status = 'confirmed',
}) => {
  ...disciplineMetadata(id: id, revision: revision, status: status),
  'description': 'Published factual description',
  'staff_note': 'Private school follow-up',
  'decision_reason': null,
  'family_reason': null,
  'basis': {
    'definition': disciplineDefinition(),
    'infraction_name': 'Late arrival',
    'window_start': '2026-01-01',
    'occurrence_count': 1,
  },
};

class DisciplineFake implements DisciplineRepository {
  @override
  bool isActive = true;
  @override
  int readerId = 12;
  bool? writable = true;
  bool unknownOnce = false, deny = false, head = false, failRefresh = false;
  String recordState = 'confirmed';
  bool omitSelectedYear = false;
  int configurations = 0, writes = 0, revision = 1;
  final intents = <DisciplineIntent>[];
  Completer<DisciplineRecordDetail>? waiting;
  @override
  Future<DisciplineConfiguration> configuration() async {
    configurations++;
    return DisciplineConfiguration.fromJson({
      'session_year_id': 7,
      'today': '2026-02-10',
      'can_manage': head,
      'can_hear': head,
      'grade_ids': head ? [5] : [],
      'grades': [
        {'id': 5, 'name': 'Grade 5'},
      ],
      'years': [
        {'id': 7, 'name': '2026'},
      ],
      if (writable != null) 'writable': writable,
      'framework': {
        'id': 1,
        'session_year_id': 7,
        'title': 'School framework',
        'revision': 1,
        'definition': disciplineDefinition(),
      },
    });
  }

  @override
  Future<DisciplineRecordQueue> records(int year, {int page = 1}) async {
    if (deny) throw ApiException('Withdrawn', statusCode: 403);
    if (failRefresh && writes > 0) {
      throw ApiException('Read failed after confirmed save', statusCode: 0);
    }
    return DisciplineRecordQueue.fromJson({
      'session_year_id': year,
      'page': page,
      'last_page': 1,
      'records': [disciplineMetadata(revision: revision)],
    });
  }

  @override
  Future<DisciplineLearnerChoices> learners(
    int year, {
    int page = 1,
    String? search,
  }) async => DisciplineLearnerChoices.fromJson({
    'session_year_id': year,
    'page': page,
    'last_page': 1,
    'learners': [
      {'learner_id': 11, 'name': 'Asha', 'grade_level_id': 5},
    ],
  });
  @override
  Future<DisciplineRecordDetail> record(int id) async =>
      waiting?.future ??
      Future.value(
        DisciplineRecordDetail.fromJson(
          disciplineDetail(id: id, revision: revision, status: recordState),
        ),
      );
  @override
  Future<DisciplineRecordHistory> history(int id) async =>
      DisciplineRecordHistory.fromJson({
        'record_id': id,
        'versions': <dynamic>[],
      });
  @override
  Future<DisciplineDetentions> detentions(int year, {int page = 1}) async =>
      DisciplineDetentions.fromJson({
        'session_year_id': year,
        'page': page,
        'last_page': 1,
        'sessions': <dynamic>[],
      });
  @override
  Future<DisciplineFamilyHistory> family(
    int learner,
    int? year, {
    int page = 1,
  }) async {
    if (deny) throw ApiException('Withdrawn', statusCode: 403);
    return DisciplineFamilyHistory.fromJson({
      'learner_id': learner,
      'years': [
        if (!omitSelectedYear) {'id': 7, 'name': '2026'},
        {'id': 8, 'name': '2025'},
      ],
      'session_year_id': year ?? 7,
      'page': page,
      'last_page': 1,
      'records': [
        {
          'id': 9,
          'occurred_on': '2026-02-10',
          'description': 'Published family fact',
          'infraction': 'Late arrival',
          'level': 'School notice',
          'sanction': 'School notice',
          'status': 'confirmed',
          'family_reason': null,
          'revision': revision,
          'acknowledged': writes > 0 && !unknownOnce,
          'detentions': <dynamic>[],
        },
      ],
    });
  }

  @override
  Future<void> write(DisciplineIntent intent) async {
    writes++;
    intents.add(intent);
    if (unknownOnce && writes == 1) {
      throw ApiException('Unknown', statusCode: 0);
    }
    revision++;
    if (intent.action == DisciplineAction.hearing) recordState = 'confirmed';
  }

  @override
  dynamic noSuchMethod(Invocation invocation) => super.noSuchMethod(invocation);
}

Future<void> mountDiscipline(
  WidgetTester tester,
  DisciplineFake fake, {
  bool family = false,
  bool guardian = false,
  bool Function()? canRead,
}) async {
  await tester.pumpWidget(
    MaterialApp(
      home: family
          ? FamilyDisciplineScreen(
              repository: fake,
              learnerId: 11,
              guardian: guardian,
              canRead: canRead ?? () => true,
              canWrite: () => true,
            )
          : SchoolDisciplineScreen(
              repository: fake,
              canRead: canRead ?? () => true,
            ),
    ),
  );
  await tester.pumpAndSettle();
}

Future<void> tapVisible(WidgetTester tester, Finder finder) async {
  await tester.ensureVisible(finder);
  await tester.tap(finder);
  await tester.pumpAndSettle();
}

void main() {
  testWidgets('teacher reads a factual record and retained staff history', (
    tester,
  ) async {
    final fake = DisciplineFake();
    await mountDiscipline(tester, fake);
    await tapVisible(tester, find.text('Asha').first);
    expect(find.text('Private school follow-up'), findsOneWidget);
    expect(find.text('Immutable entry history'), findsOneWidget);
    expect(find.text('Save reasoned review'), findsNothing);
  });
  for (final writable in <bool?>[false, null]) {
    testWidgets('read-only $writable retains facts and disables mutation', (
      tester,
    ) async {
      final fake = DisciplineFake()..writable = writable;
      await mountDiscipline(tester, fake);
      expect(
        find.text('Discipline is read-only. Saved records remain available.'),
        findsOneWidget,
      );
      final button = find.widgetWithText(FilledButton, 'Record occurrence');
      await tester.ensureVisible(button);
      expect(tester.widget<FilledButton>(button).onPressed, isNull);
      expect(fake.writes, 0);
    });
  }
  testWidgets('unknown create retries immutable UUID and text exactly', (
    tester,
  ) async {
    final fake = DisciplineFake()..unknownOnce = true;
    await mountDiscipline(tester, fake);
    await tapVisible(tester, find.text('Asha').last);
    final description = find.byWidgetPredicate(
      (w) =>
          w is TextField &&
          w.decoration?.labelText == 'Factual description the family may read',
    );
    await tester.ensureVisible(description);
    await tester.enterText(description, 'Observed school fact');
    await tester.pump();
    await tapVisible(
      tester,
      find.widgetWithText(FilledButton, 'Record occurrence'),
    );
    expect(fake.writes, 1);
    expect(find.text('Retry exact operation'), findsOneWidget);
    await tapVisible(tester, find.text('Retry exact operation'));
    expect(fake.writes, 2);
    expect(fake.intents[0].body, fake.intents[1].body);
  });
  testWidgets('late private record is erased when app backgrounds', (
    tester,
  ) async {
    final fake = DisciplineFake()
      ..waiting = Completer<DisciplineRecordDetail>();
    await mountDiscipline(tester, fake);
    await tester.tap(find.text('Asha').first);
    await tester.pump();
    tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.paused);
    fake.waiting!.complete(DisciplineRecordDetail.fromJson(disciplineDetail()));
    await tester.pumpAndSettle();
    expect(find.text('Private school follow-up'), findsNothing);
    tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
    await tester.pumpAndSettle();
  });
  testWidgets(
    'learner family reader requests no staff source and cannot acknowledge',
    (tester) async {
      final fake = DisciplineFake();
      await mountDiscipline(tester, fake, family: true);
      expect(find.text('Published family fact'), findsOneWidget);
      expect(find.text('Private school follow-up'), findsNothing);
      expect(find.text('Acknowledge reading this revision'), findsNothing);
      expect(fake.configurations, 0);
    },
  );
  testWidgets('guardian acknowledges exact revision then fresh read', (
    tester,
  ) async {
    final fake = DisciplineFake();
    await mountDiscipline(tester, fake, family: true, guardian: true);
    await tapVisible(tester, find.text('Acknowledge reading this revision'));
    expect(fake.writes, 1);
    expect(fake.intents.single.body['expected_revision'], 1);
    expect(find.text('You acknowledged this revision'), findsOneWidget);
  });
  testWidgets('family ownership refusal clears retained factual history', (
    tester,
  ) async {
    final fake = DisciplineFake();
    await mountDiscipline(tester, fake, family: true, guardian: true);
    fake.deny = true;
    await tapVisible(tester, find.text('Reload saved history'));
    expect(find.text('Published family fact'), findsNothing);
    expect(
      find.text('Reopen school discipline to check your access.'),
      findsOneWidget,
    );
  });
  testWidgets(
    'a failed refresh never turns a confirmed save into an unknown write',
    (tester) async {
      final fake = DisciplineFake()..failRefresh = true;
      await mountDiscipline(tester, fake);
      await tapVisible(tester, find.text('Asha').last);
      final description = find.byWidgetPredicate(
        (w) =>
            w is TextField &&
            w.decoration?.labelText ==
                'Factual description the family may read',
      );
      await tester.ensureVisible(description);
      await tester.enterText(description, 'Observed confirmed source');
      await tester.pump();
      await tapVisible(
        tester,
        find.widgetWithText(FilledButton, 'Record occurrence'),
      );
      expect(fake.writes, 1);
      expect(find.text('Retry exact operation'), findsNothing);
      expect(
        find.text(
          'The save outcome is unknown. Retry the exact same operation before editing.',
        ),
        findsNothing,
      );
    },
  );
  testWidgets(
    'principal records a separate hearing decision with an explicit reason',
    (tester) async {
      final fake = DisciplineFake()
        ..head = true
        ..recordState = 'hearing';
      await mountDiscipline(tester, fake);
      await tapVisible(tester, find.text('Asha').first);
      expect(find.text('Save reasoned review'), findsNothing);
      final reason = find.byWidgetPredicate(
        (w) =>
            w is TextField &&
            w.decoration?.labelText == 'Principal decision reason',
      );
      await tester.ensureVisible(reason);
      await tester.enterText(reason, 'Principal reviewed the factual record');
      await tester.pump();
      await tapVisible(
        tester,
        find.widgetWithText(FilledButton, 'Record no further action'),
      );
      expect(fake.intents.single.action, DisciplineAction.hearing);
      expect(
        fake.intents.single.body['reason'],
        'Principal reviewed the factual record',
      );
      expect(fake.intents.single.body['decision'], 'no_action');
    },
  );
  testWidgets(
    'withdrawn historical year choices never leave an invalid dropdown selection',
    (tester) async {
      final fake = DisciplineFake()..omitSelectedYear = true;
      await mountDiscipline(tester, fake, family: true);
      final year = find.byType(DropdownButtonFormField<int>);
      expect(year, findsOneWidget);
      expect(tester.takeException(), isNull);
      await tapVisible(tester, year);
      await tapVisible(tester, find.text('2025').last);
      expect(tester.takeException(), isNull);
    },
  );
}
