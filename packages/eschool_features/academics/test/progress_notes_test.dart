import 'dart:async';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_academics/src/progress_notes/progress_repository.dart';
import 'package:eschool_academics/src/progress_notes/progress_notes_screen.dart';
import 'package:eschool_academics/src/progress_notes/family_progress_screen.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

Map<String, dynamic> metadata({
  String visibility = 'staff',
  int author = 12,
  int learner = 11,
}) => {
  'id': 9,
  'learner_id': learner,
  'subject_id': null,
  'session_year_id': 7,
  'author_id': author,
  'author_name': 'Synthetic staff',
  'visibility': visibility,
  'revision': 1,
  'archived': false,
  'updated_at': '2026-10-25T01:00:00Z',
};

class NotesFake implements ProgressRepository {
  NotesFake({this.clinical = false, this.principal = false});
  final bool clinical, principal;
  @override
  int schoolId = 1, readerId = 12;
  @override
  bool isActive = true;
  int configurationCalls = 0, bodyReads = 0, rosterCalls = 0, noteLists = 0;
  int periodsCalls = 0, familyCalls = 0, creates = 0;
  bool unknownOnce = false, denyHistory = false;
  final intents = <Map<String, dynamic>>[];
  Completer<LearnerNoteDetail>? delayed;
  @override
  Future<ProgressConfiguration> configuration() async {
    configurationCalls++;
    return ProgressConfiguration.fromJson({
      'session_year_id': 7,
      'can_manage_bands': principal,
      'can_assign_counselling': principal,
      'can_read_caseload': clinical,
      'settings': {
        'high_bp': 8000,
        'secure_bp': 6500,
        'minimum_coverage_bp': 2500,
        'revision': 0,
      },
      'restricted_staff': [
        {'id': 12, 'name': 'Clinical staff'},
      ],
      'years': [
        {'id': 7, 'name': '2026'},
        {'id': 6, 'name': '2025'},
      ],
      'terms': [
        {'id': 8, 'name': 'Term 1'},
      ],
      'groups': clinical || principal
          ? []
          : [
              {
                'id': 3,
                'name': 'Blue group',
                'subject_id': 4,
                'subject_name': 'Mathematics',
                'grade_name': 'Grade 5',
                'session_year_id': 7,
              },
            ],
    });
  }

  @override
  Future<ProgressRoster> roster(int group, {int page = 1}) async {
    rosterCalls++;
    return ProgressRoster.fromJson({
      'group_id': group,
      'current_page': 1,
      'last_page': 1,
      'total': 1,
      'learners': [
        {'learner_id': 11, 'display_name': 'Synthetic learner'},
      ],
    });
  }

  ProgressLearnerList learners() => ProgressLearnerList.fromJson({
    'current_page': 1,
    'last_page': 1,
    'total': 1,
    'learners': [
      {'learner_id': 11, 'display_name': 'Synthetic learner'},
    ],
  });
  @override
  Future<ProgressLearnerList> caseload({int page = 1}) async => learners();
  @override
  Future<ProgressLearnerList> assignmentLearners({int page = 1}) async =>
      learners();
  @override
  Future<LearnerNoteList> notes(
    int learner,
    int year,
    String visibility, {
    int? subject,
    int after = 0,
  }) async {
    noteLists++;
    return LearnerNoteList.fromJson({
      'notes': [metadata(visibility: visibility, learner: learner)],
      'next_after': null,
    });
  }

  @override
  Future<LearnerNoteDetail> note(int id, int learner, String visibility) async {
    bodyReads++;
    if (delayed != null) return delayed!.future;
    return LearnerNoteDetail.fromJson({
      ...metadata(visibility: visibility, learner: learner),
      'body': 'Private synthetic body <script>plain text</script>',
    });
  }

  @override
  Future<LearnerNoteHistory> history(int id) async {
    if (denyHistory) throw ApiException('Withdrawn', statusCode: 403);
    return LearnerNoteHistory(noteId: id, revisions: []);
  }

  @override
  Future<LearnerNoteDetail> create(int learner, LearnerNoteCreate input) async {
    creates++;
    intents.add(input.toJson());
    if (unknownOnce && creates == 1) {
      throw TimeoutException('Synthetic unknown dispatch');
    }
    return LearnerNoteDetail.fromJson({
      ...metadata(visibility: input.visibility.value),
      'body': input.body,
    });
  }

  @override
  Future<LearnerNoteAssignments> assignments(int learner) async =>
      LearnerNoteAssignments(learnerId: learner, assignments: []);
  @override
  Future<AcademicReleasePeriodsData> periods(int learner) async {
    periodsCalls++;
    return AcademicReleasePeriodsData.fromJson({
      'learner_id': learner,
      'periods': [
        {
          'session_year_id': 7,
          'year_name': '2026',
          'term_id': 8,
          'term_name': 'Term 1',
          'released_at': '2026-10-25T01:00:00Z',
        },
      ],
    });
  }

  @override
  Future<ProgressFamilyBands> familyBands(
    int learner,
    int year,
    int? term,
  ) async {
    familyCalls++;
    return ProgressFamilyBands.fromJson({
      'audience': 'family',
      'learner_id': learner,
      'results': [
        {
          'subject_name': 'Mathematics',
          'grade_name': 'Grade 5',
          'release_id': 2,
          'subject_id': 4,
          'grade_level_id': 5,
          'session_year_id': 7,
          'term_id': 8,
          'mode': 'running',
          'provisional': true,
          'rating_level': null,
          'released_at': '2026-10-25T01:00:00Z',
          'released_percentage_bp': null,
          'band': 'insufficient_evidence',
          'percentage_bp': null,
          'coverage_bp': 0,
          'alerts_eligible': false,
          'settings_revision': 0,
        },
      ],
    });
  }

  @override
  dynamic noSuchMethod(Invocation invocation) => super.noSuchMethod(invocation);
}

Future<void> frame(WidgetTester tester, Widget child, double width) async {
  tester.view.physicalSize = Size(width, 1000);
  tester.view.devicePixelRatio = 1;
  addTearDown(tester.view.resetPhysicalSize);
  addTearDown(tester.view.resetDevicePixelRatio);
  await tester.pumpWidget(
    MaterialApp(theme: ThemeData(useMaterial3: true), home: child),
  );
  await tester.pumpAndSettle();
}

Future<void> chooseLearner(WidgetTester tester) async {
  await tester.tap(find.byType(DropdownButtonFormField<int>).at(1));
  await tester.pumpAndSettle();
  await tester.tap(find.text('Synthetic learner').last);
  await tester.pumpAndSettle();
}

void main() {
  for (final width in [390.0, 820.0, 1280.0]) {
    testWidgets('staff private note workflow fits $width', (tester) async {
      final repo = NotesFake();
      await frame(
        tester,
        ProgressNotesScreen(repository: repo, canRead: () => true),
        width,
      );
      await chooseLearner(tester);
      await tester.tap(find.text('Synthetic staff'));
      await tester.pumpAndSettle();
      expect(find.textContaining('Private synthetic body'), findsOneWidget);
      expect(repo.bodyReads, 1);
      expect(tester.takeException(), isNull);
    });
  }
  testWidgets('principal assignment view never fetches note lists or bodies', (
    tester,
  ) async {
    final repo = NotesFake(principal: true);
    await frame(
      tester,
      ProgressNotesScreen(repository: repo, canRead: () => true),
      390,
    );
    await tester.tap(find.byType(DropdownButtonFormField<int>).first);
    await tester.pumpAndSettle();
    await tester.tap(find.text('Synthetic learner').last);
    await tester.pumpAndSettle();
    expect(find.textContaining('never counselling text'), findsOneWidget);
    expect(repo.bodyReads, 0);
    expect(repo.noteLists, 0);
    expect(repo.rosterCalls, 0);
    expect(tester.takeException(), isNull);
  });
  testWidgets('clinical-only role uses own caseload without teacher rosters', (
    tester,
  ) async {
    final repo = NotesFake(clinical: true);
    await frame(
      tester,
      ProgressNotesScreen(repository: repo, canRead: () => true),
      390,
    );
    expect(find.text('Teaching group'), findsNothing);
    expect(repo.rosterCalls, 0);
    await tester.tap(find.byType(DropdownButtonFormField<int>).first);
    await tester.pumpAndSettle();
    await tester.tap(find.text('Synthetic learner').last);
    await tester.pumpAndSettle();
    expect(repo.noteLists, 1);
    expect(repo.rosterCalls, 0);
    expect(tester.takeException(), isNull);
  });
  testWidgets('unknown note write retries an identical immutable intent', (
    tester,
  ) async {
    final repo = NotesFake()..unknownOnce = true;
    await frame(
      tester,
      ProgressNotesScreen(repository: repo, canRead: () => true),
      820,
    );
    await chooseLearner(tester);
    await tester.enterText(find.byType(TextField), 'Own synthetic draft');
    await tester.ensureVisible(find.text('Save note'));
    await tester.tap(find.text('Save note'));
    await tester.pumpAndSettle();
    expect(find.textContaining('save outcome is unknown'), findsOneWidget);
    expect(tester.widget<TextField>(find.byType(TextField)).readOnly, isTrue);
    await tester.ensureVisible(find.text('Retry same save'));
    await tester.tap(find.text('Retry same save'));
    await tester.pumpAndSettle();
    expect(repo.creates, 2);
    expect(repo.intents[0], repo.intents[1]);
    expect(tester.takeException(), isNull);
  });
  testWidgets(
    'background clears private text and late responses cannot restore it',
    (tester) async {
      final repo = NotesFake()..delayed = Completer<LearnerNoteDetail>();
      await frame(
        tester,
        ProgressNotesScreen(repository: repo, canRead: () => true),
        820,
      );
      await chooseLearner(tester);
      await tester.tap(find.text('Synthetic staff'));
      await tester.pump();
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
      await tester.pump();
      repo.delayed!.complete(
        LearnerNoteDetail.fromJson({
          ...metadata(),
          'body': 'Late confidential body',
        }),
      );
      await tester.pumpAndSettle();
      expect(find.text('Late confidential body'), findsNothing);
      repo.isActive = false;
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await tester.pumpAndSettle();
      expect(find.byType(TextField), findsNothing);
    },
  );
  testWidgets('fresh history refusal clears already displayed private text', (
    tester,
  ) async {
    final repo = NotesFake()..denyHistory = true;
    await frame(
      tester,
      ProgressNotesScreen(repository: repo, canRead: () => true),
      820,
    );
    await chooseLearner(tester);
    await tester.tap(find.text('Synthetic staff'));
    await tester.pumpAndSettle();
    await tester.ensureVisible(find.text('Revision history'));
    await tester.tap(find.text('Revision history'));
    await tester.pumpAndSettle();
    expect(find.textContaining('Private synthetic body'), findsNothing);
    expect(find.byType(TextField), findsNothing);
  });
  testWidgets(
    'family uses released bands and never requests staff or clinical data',
    (tester) async {
      final repo = NotesFake();
      await frame(
        tester,
        FamilyProgressScreen(
          repository: repo,
          learnerId: 11,
          canRead: () => true,
        ),
        390,
      );
      await tester.tap(find.byType(DropdownButtonFormField<int>));
      await tester.pumpAndSettle();
      await tester.tap(find.text('2026 · Term 1').last);
      await tester.pumpAndSettle();
      expect(find.text('Insufficient evidence'), findsOneWidget);
      expect(find.text('0.00%'), findsNothing);
      expect(repo.configurationCalls, 0);
      expect(repo.noteLists, 0);
      expect(repo.bodyReads, 0);
      expect(repo.familyCalls, 1);
      expect(tester.takeException(), isNull);
    },
  );
}
