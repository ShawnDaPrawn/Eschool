import 'dart:async';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'activity_test_repository.dart';

Future<void> frame(
  WidgetTester tester,
  FakeActivityRepository repo,
  double width,
) async {
  tester.view.physicalSize = Size(width, 900);
  tester.view.devicePixelRatio = 1;
  addTearDown(tester.view.resetPhysicalSize);
  addTearDown(tester.view.resetDevicePixelRatio);
  await tester.pumpWidget(
    MaterialApp(
      theme: ThemeData(useMaterial3: true),
      home: ActivityGroupsScreen(repository: repo),
    ),
  );
  await tester.pumpAndSettle();
}

Future<void> tap(WidgetTester tester, Finder finder) async {
  await tester.ensureVisible(finder);
  await tester.tap(finder);
  await tester.pumpAndSettle();
}

Finder field(String label) => find.byWidgetPredicate(
  (w) => w is TextField && w.decoration?.labelText == label,
);
Future<void> pause(WidgetTester tester) async {
  for (final state in [
    AppLifecycleState.inactive,
    AppLifecycleState.hidden,
    AppLifecycleState.paused,
  ]) {
    tester.binding.handleAppLifecycleStateChanged(state);
  }
  await tester.pump();
}

Future<void> resume(WidgetTester tester) async {
  for (final state in [
    AppLifecycleState.hidden,
    AppLifecycleState.inactive,
    AppLifecycleState.resumed,
  ]) {
    tester.binding.handleAppLifecycleStateChanged(state);
  }
  await tester.pumpAndSettle();
}

void main() {
  for (final width in [390.0, 1024.0, 1280.0]) {
    for (final staff in [false, true]) {
      testWidgets(
        '${staff ? 'coach' : 'family'} group view at ${width.toInt()}px uses server permissions',
        (tester) async {
          final repo = FakeActivityRepository()..staff = staff;
          await frame(tester, repo, width);
          await tap(tester, find.text('Hockey'));
          expect(
            find.text('New session'),
            staff ? findsOneWidget : findsNothing,
          );
          expect(
            find.text('Open attendance'),
            staff ? findsOneWidget : findsNothing,
          );
          expect(repo.rosterReads, 0);
          await tester.scrollUntilVisible(
            find.text('Fixture note'),
            300,
            scrollable: find.byType(Scrollable).first,
          );
          expect(find.text('Fixture note'), findsOneWidget);
          expect(tester.takeException(), isNull);
        },
      );
    }
  }
  testWidgets('register records only explicitly selected learners', (
    tester,
  ) async {
    final repo = FakeActivityRepository();
    await frame(tester, repo, 390);
    await tap(tester, find.text('Hockey'));
    await tap(tester, find.text('Open attendance'));
    expect(find.text('Unrecorded'), findsNWidgets(2));
    await tap(tester, find.byKey(const ValueKey('attendance-17')));
    await tap(tester, find.text('late').last);
    await tester.enterText(field('Attendance reason'), 'Observed at practice');
    await tap(tester, find.text('Save activity'));
    expect(repo.writes.single['entries'], [
      {'learner_id': 17, 'status': 'late', 'revision': 0},
    ]);
    expect(tester.takeException(), isNull);
  });
  testWidgets(
    'session roster omits withdrawn learner and keeps valid classmates',
    (tester) async {
      final repo = FakeActivityRepository()
        ..rosterData = {
          ...activityRosterFixture(),
          'session_roster': {
            'session_id': 9,
            'group_id': 7,
            'session_revision': 1,
            'learners': [
              {
                'learner_id': 17,
                'learner_name': 'Asha Learner',
                'status': null,
                'revision': 0,
              },
            ],
          },
        };
      await frame(tester, repo, 390);
      await tap(tester, find.text('Hockey'));
      await tap(tester, find.text('Open attendance'));
      expect(find.text('Ben Learner'), findsNothing);
      expect(find.byKey(const ValueKey('attendance-17')), findsOneWidget);
      await tap(tester, find.byKey(const ValueKey('attendance-17')));
      await tap(tester, find.text('present').last);
      await tester.enterText(
        field('Attendance reason'),
        'Observed eligible classmates',
      );
      await tap(tester, find.text('Save activity'));
      expect(repo.writes.single['entries'], [
        {'learner_id': 17, 'status': 'present', 'revision': 0},
      ]);
    },
  );

  testWidgets(
    'retained captured learner remains available without a membership row',
    (tester) async {
      final repo = FakeActivityRepository()
        ..rosterData = {
          ...activityRosterFixture(),
          'session_roster': {
            'session_id': 9,
            'group_id': 7,
            'session_revision': 2,
            'learners': [
              {
                'learner_id': 19,
                'learner_name': 'Former Learner',
                'status': 'late',
                'revision': 3,
              },
            ],
          },
        };
      await frame(tester, repo, 390);
      await tap(tester, find.text('Hockey'));
      await tap(tester, find.text('Open attendance'));
      expect(find.byKey(const ValueKey('attendance-19')), findsOneWidget);
      expect(find.text('Asha Learner'), findsNothing);
      await tester.enterText(
        field('Attendance reason'),
        'Correction of retained record',
      );
      await tap(tester, find.text('Save activity'));
      expect(repo.writes.single['entries'], [
        {'learner_id': 19, 'status': 'late', 'revision': 3},
      ]);
      expect(repo.writes.single['revision'], 2);
    },
  );

  testWidgets('uncertain material save retries immutable UUID and payload', (
    tester,
  ) async {
    final repo = FakeActivityRepository()
      ..writeError = ApiException('timeout', isTransportFailure: true);
    await frame(tester, repo, 1024);
    await tap(tester, find.text('Hockey'));
    await tap(tester, find.text('New material'));
    await tester.enterText(field('Material title'), 'Private draft');
    await tester.enterText(field('Material Markdown'), 'UNSAVED safe notes');
    await tap(tester, find.text('Save activity'));
    expect(find.text('Retry same operation'), findsOneWidget);
    expect(
      (tester.widget<TextField>(field('Material Markdown'))).enabled,
      isFalse,
    );
    repo.writeError = null;
    await tap(tester, find.text('Retry same operation'));
    expect(repo.writes.length, 2);
    expect(repo.writes[0], repo.writes[1]);
    expect(tester.takeException(), isNull);
  });
  testWidgets(
    'resume hides private draft until renewed access and clears it on revocation',
    (tester) async {
      final repo = FakeActivityRepository();
      await frame(tester, repo, 390);
      await tap(tester, find.text('Hockey'));
      await tap(tester, find.text('New material'));
      await tester.enterText(
        field('Material Markdown'),
        'PRIVATE DRAFT TO CLEAR',
      );
      await pause(tester);
      await tester.pump();
      expect(find.text('PRIVATE DRAFT TO CLEAR'), findsNothing);
      repo.readError = ApiException('revoked', statusCode: 403);
      await resume(tester);
      await tester.pumpAndSettle();
      expect(
        find.text('PRIVATE DRAFT TO CLEAR', skipOffstage: false),
        findsNothing,
      );
      expect(find.text('Not available for this account'), findsOneWidget);
      expect(tester.takeException(), isNull);
    },
  );
  testWidgets(
    'transient resume failure keeps draft but blocks saving until refresh',
    (tester) async {
      final repo = FakeActivityRepository();
      await frame(tester, repo, 1280);
      await tap(tester, find.text('Hockey'));
      await tap(tester, find.text('New material'));
      await tester.enterText(
        field('Material Markdown'),
        'DRAFT SURVIVES OFFLINE',
      );
      repo.readError = ApiException('offline', isTransportFailure: true);
      await pause(tester);
      await resume(tester);
      await tester.pumpAndSettle();
      expect(find.text('DRAFT SURVIVES OFFLINE'), findsOneWidget);
      expect(
        (tester.widget<TextField>(field('Material Markdown'))).enabled,
        isFalse,
      );
      repo.readError = null;
      await tap(tester, find.text('Retry'));
      expect(find.text('DRAFT SURVIVES OFFLINE'), findsOneWidget);
      expect(
        (tester.widget<TextField>(field('Material Markdown'))).enabled,
        isTrue,
      );
    },
  );
  testWidgets('late response for a replaced account is discarded', (
    tester,
  ) async {
    final repo = FakeActivityRepository();
    await frame(tester, repo, 1024);
    final pending = Completer<ActivityGroupDetail>();
    repo.readDetail = () => pending.future;
    await tester.tap(find.text('Hockey'));
    await tester.pump();
    repo.isActive = false;
    pending.complete(ActivityGroupDetail.fromJson(activityDetailFixture()));
    await tester.pumpAndSettle();
    expect(find.text('Fixture note'), findsNothing);
    expect(find.text('Not available for this account'), findsOneWidget);
  });
  testWidgets(
    'resume keeps private text hidden while authorization is still pending',
    (tester) async {
      final repo = FakeActivityRepository();
      await frame(tester, repo, 390);
      await tap(tester, find.text('Hockey'));
      await tap(tester, find.text('New material'));
      await tester.enterText(
        field('Material Markdown'),
        'PRIVATE WHILE AUTH PENDING',
      );
      await pause(tester);
      final pending = Completer<ActivityGroupDetail>();
      repo.readDetail = () => pending.future;
      for (final state in [
        AppLifecycleState.hidden,
        AppLifecycleState.inactive,
        AppLifecycleState.resumed,
      ]) {
        tester.binding.handleAppLifecycleStateChanged(state);
      }
      await tester.pump();
      expect(find.text('PRIVATE WHILE AUTH PENDING'), findsNothing);
      pending.complete(ActivityGroupDetail.fromJson(activityDetailFixture()));
      await tester.pumpAndSettle();
      expect(find.text('PRIVATE WHILE AUTH PENDING'), findsOneWidget);
    },
  );
  testWidgets(
    'coach can plan a session using calendar date and explicit status',
    (tester) async {
      final repo = FakeActivityRepository();
      await frame(tester, repo, 390);
      await tap(tester, find.text('Hockey'));
      await tap(tester, find.text('New session'));
      await tester.enterText(field('Session name'), 'Morning practice');
      await tester.enterText(field('Venue'), 'Field A');
      await tester.enterText(field('Session date'), '2026-03-11');
      await tap(tester, find.text('Save activity'));
      expect(repo.writes.single['on_date'], '2026-03-11');
      expect(repo.writes.single['status'], 'scheduled');
      expect(repo.writes.single['revision'], 0);
      expect(tester.takeException(), isNull);
    },
  );
  testWidgets(
    'conflict retains draft and requires deliberate review before a new revision',
    (tester) async {
      final repo = FakeActivityRepository()
        ..writeError = ApiException('conflict', statusCode: 409);
      await frame(tester, repo, 1024);
      await tap(tester, find.text('Hockey'));
      await tap(tester, find.text('Edit material'));
      await tester.enterText(
        field('Material Markdown'),
        'MY CONFLICTING MATERIAL',
      );
      await tap(tester, find.text('Save activity'));
      expect(find.text('Review current saved values'), findsOneWidget);
      await tap(tester, find.text('Review current saved values'));
      expect(find.text('Current saved values'), findsOneWidget);
      expect(find.text('MY CONFLICTING MATERIAL'), findsOneWidget);
      await tap(tester, find.text('Keep my draft and use this revision'));
      repo.writeError = null;
      await tap(tester, find.text('Save activity'));
      expect(repo.writes.length, 2);
      expect(repo.writes[1]['markdown'], 'MY CONFLICTING MATERIAL');
      expect(
        repo.writes[1]['operation_id'],
        isNot(repo.writes[0]['operation_id']),
      );
    },
  );
}
