import 'dart:async';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_academics/src/progress_notes/progress_notes_screen.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'progress_notes_test.dart' as fixture;

class CalendarNotes extends fixture.NotesFake {
  bool bandDelayed = false, legacy = false;
  final requests = <int>[];
  final waits = <int, Completer<ProgressStaffBand>>{};
  @override
  Future<ProgressConfiguration> configuration() async {
    final config = (await super.configuration()).toJson();
    config['terms'] = [
      {'id': 9, 'name': 'Other grade first term'},
      {'id': 8, 'name': 'Term 1'},
      {'id': 10, 'name': 'Term 2'},
    ];
    final blue = Map<String, dynamic>.from(
      (config['groups'] as List).first as Map,
    );
    if (!legacy) blue['term_ids'] = [8, 10];
    config['groups'] = [
      blue,
      {
        ...blue,
        'id': 4,
        'name': 'Green group',
        'grade_name': 'Grade 8',
        if (!legacy) 'term_ids': [9],
      },
    ];
    return ProgressConfiguration.fromJson(config);
  }

  ProgressStaffBand band(int term, int percent) => ProgressStaffBand.fromJson({
    'audience': 'staff',
    'learner_id': 11,
    'subject_id': 4,
    'session_year_id': 7,
    'term_id': term,
    'snapshot_id': term + 100,
    'provisional': true,
    'band': percent >= 8000 ? 'high' : 'support',
    'percentage_bp': percent,
    'coverage_bp': 10000,
    'alerts_eligible': true,
    'settings_revision': 0,
  });
  @override
  Future<ProgressStaffBand> staffBand(int group, int learner, int term) async {
    requests.add(term);
    if ((group == 3 && ![8, 10].contains(term)) || (group == 4 && term != 9)) {
      throw ApiException('Wrong grade term', statusCode: 404);
    }
    if (bandDelayed) {
      return (waits[term] = Completer<ProgressStaffBand>()).future;
    }
    return band(term, term == 10 ? 9000 : 3000);
  }
}

Future<void> chooseTerm(WidgetTester tester, String name) async {
  final field = find.byType(DropdownButtonFormField<int>).last;
  await tester.ensureVisible(field);
  await tester.tap(field);
  await tester.pumpAndSettle();
  await tester.tap(find.text(name).last);
  await tester.pumpAndSettle();
}

Future<void> showNotes(WidgetTester tester, CalendarNotes repo) async {
  await fixture.frame(
    tester,
    ProgressNotesScreen(repository: repo, canRead: () => true),
    820,
  );
  await fixture.chooseLearner(tester);
}

void main() {
  testWidgets('default term belongs to selected grade and resets with group', (
    tester,
  ) async {
    final repo = CalendarNotes();
    await showNotes(tester, repo);
    expect(repo.requests, [8]);
    await tester.ensureVisible(find.byType(DropdownButtonFormField<int>).first);
    await tester.tap(find.byType(DropdownButtonFormField<int>).first);
    await tester.pumpAndSettle();
    await tester.tap(find.text('Green group · Mathematics').last);
    await tester.pumpAndSettle();
    await fixture.chooseLearner(tester);
    expect(repo.requests.last, 9);
    expect(tester.takeException(), isNull);
  });
  testWidgets('late prior-term success cannot overwrite newer selected band', (
    tester,
  ) async {
    final repo = CalendarNotes()..bandDelayed = true;
    await showNotes(tester, repo);
    await chooseTerm(tester, 'Term 2');
    repo.waits[10]!.complete(repo.band(10, 9000));
    await tester.pumpAndSettle();
    expect(find.text('90.00%'), findsOneWidget);
    repo.waits[8]!.complete(repo.band(8, 3000));
    await tester.pumpAndSettle();
    expect(find.text('90.00%'), findsOneWidget);
    expect(find.text('30.00%'), findsNothing);
  });
  testWidgets(
    'late prior-term refusal cannot withdraw current authorized notes',
    (tester) async {
      final repo = CalendarNotes();
      await showNotes(tester, repo);
      await tester.ensureVisible(find.text('Synthetic staff'));
      await tester.tap(find.text('Synthetic staff'));
      await tester.pumpAndSettle();
      expect(find.textContaining('Private synthetic body'), findsOneWidget);
      repo.bandDelayed = true;
      await chooseTerm(tester, 'Term 2');
      await chooseTerm(tester, 'Term 1');
      repo.waits[8]!.complete(repo.band(8, 9000));
      await tester.pumpAndSettle();
      repo.waits[10]!.completeError(
        ApiException('Old scope refusal', statusCode: 403),
      );
      await tester.pumpAndSettle();
      expect(find.textContaining('Private synthetic body'), findsOneWidget);
      expect(find.text('90.00%'), findsOneWidget);
    },
  );
  testWidgets('refusal for current term still clears private note text', (
    tester,
  ) async {
    final repo = CalendarNotes();
    await showNotes(tester, repo);
    await tester.ensureVisible(find.text('Synthetic staff'));
    await tester.tap(find.text('Synthetic staff'));
    await tester.pumpAndSettle();
    repo.bandDelayed = true;
    await chooseTerm(tester, 'Term 2');
    repo.waits[10]!.completeError(
      ApiException('Current scope refused', statusCode: 403),
    );
    await tester.pumpAndSettle();
    expect(find.textContaining('Private synthetic body'), findsNothing);
  });
  testWidgets('older server without scoped terms leaves notes usable', (
    tester,
  ) async {
    final repo = CalendarNotes()..legacy = true;
    await showNotes(tester, repo);
    expect(repo.requests, isEmpty);
    await tester.ensureVisible(find.text('Synthetic staff'));
    await tester.tap(find.text('Synthetic staff'));
    await tester.pumpAndSettle();
    expect(find.textContaining('Private synthetic body'), findsOneWidget);
  });
}
