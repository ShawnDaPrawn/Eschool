import 'dart:async';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'fake_recognition.dart';

Future<void> home(
  WidgetTester tester,
  FakeRecognition repo, {
  int learner = 2,
}) async {
  await tester.pumpWidget(
    MaterialApp(
      home: Scaffold(
        body: RecognitionHomeSummary(repository: repo, learnerId: learner),
      ),
    ),
  );
  await tester.pumpAndSettle();
}

Future<void> event(
  WidgetTester tester,
  FakeRecognition repo, {
  int school = 1,
}) async {
  repo.events.add(RecognitionInvalidation(schoolId: school));
  await tester.pump();
  await tester.pump(const Duration(milliseconds: 160));
  await tester.pumpAndSettle();
}

void award(FakeRecognition repo) {
  repo.total = 10;
  repo.revision = 2;
  repo.entries.insert(
    0,
    RecognitionPointEntry(
      id: 2,
      houseId: 3,
      houseName: 'Mountain',
      categoryName: 'Helping',
      points: 5,
      reason: 'Private source evidence never shown on home',
      onDate: '2026-02-10',
      public: false,
      reversesId: null,
    ),
  );
}

void main() {
  for (final width in [390.0, 820.0, 1280.0]) {
    testWidgets(
      'home counter and own new point moment refresh within two seconds at $width',
      (tester) async {
        tester.view.physicalSize = Size(width, 1000);
        tester.view.devicePixelRatio = 1;
        addTearDown(tester.view.resetPhysicalSize);
        addTearDown(tester.view.resetDevicePixelRatio);
        final repo = FakeRecognition();
        await home(tester, repo);
        expect(find.text('5 house points'), findsOneWidget);
        expect(find.byType(SnackBar), findsNothing);
        award(repo);
        await event(tester, repo);
        expect(find.text('10 house points'), findsOneWidget);
        expect(find.text('5 house points · Mountain'), findsOneWidget);
        expect(
          find.text('Private source evidence never shown on home'),
          findsNothing,
        );
        await event(tester, repo);
        expect(find.byKey(const ValueKey('home-house-moment')), findsOneWidget);
        expect(tester.takeException(), isNull);
        await tester.pumpWidget(const SizedBox.shrink());
      },
    );
  }
  testWidgets(
    'another school event never fetches and a current refusal clears the counter',
    (tester) async {
      final repo = FakeRecognition();
      await home(tester, repo);
      final reads = repo.reads;
      await event(tester, repo, school: 2);
      expect(repo.reads, reads);
      repo.deny = true;
      await event(tester, repo);
      expect(find.text('5 house points'), findsNothing);
      expect(
        find.text(
          'House points are unavailable. Refresh to check your access.',
        ),
        findsOneWidget,
      );
      await tester.pumpWidget(const SizedBox.shrink());
    },
  );
  testWidgets('background clears counters and moment before a refused resume', (
    tester,
  ) async {
    final repo = FakeRecognition();
    await home(tester, repo);
    award(repo);
    await event(tester, repo);
    tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
    await tester.pumpAndSettle();
    expect(find.text('10 house points'), findsNothing);
    expect(find.byType(SnackBar), findsNothing);
    repo.deny = true;
    tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
    await tester.pumpAndSettle();
    expect(find.text('10 house points'), findsNothing);
    await tester.pumpWidget(const SizedBox.shrink());
  });
  testWidgets(
    'a late old child response cannot restore counters after account replacement',
    (tester) async {
      final old = FakeRecognition()
        ..delayedHistory = Completer<RecognitionPointHistory>();
      final pending = old.delayedHistory!;
      await tester.pumpWidget(
        MaterialApp(
          home: Scaffold(
            body: RecognitionHomeSummary(repository: old, learnerId: 2),
          ),
        ),
      );
      await tester.pump();
      final current = FakeRecognition()
        ..accountId = 21
        ..total = 27;
      await home(tester, current, learner: 3);
      pending.complete(
        RecognitionPointHistory(items: old.entries, nextBefore: null),
      );
      await tester.pumpAndSettle();
      expect(find.text('27 house points'), findsOneWidget);
      expect(find.text('5 house points'), findsNothing);
      expect(find.byType(SnackBar), findsNothing);
      await tester.pumpWidget(const SizedBox.shrink());
    },
  );
  test(
    'live adapter forwards source references and reconnects as invalidations only',
    () async {
      final events = StreamController<LiveInvalidation>.broadcast(),
          refreshes = StreamController<void>.broadcast();
      final received = <RecognitionInvalidation>[];
      final subscription = recognitionLiveChanges(
        events.stream,
        refreshes: refreshes.stream,
        schoolId: 1,
      ).listen(received.add);
      events.add(
        LiveInvalidation.fromJson({
          'type': 'recognition.changed',
          'school_id': 1,
          'revision': 1,
          'session_year_id': 7,
          'learner_id': 2,
          'source_id': 3,
          'kind': 'point',
        }, expectedSchoolId: 1),
      );
      refreshes.add(null);
      await Future<void>.delayed(Duration.zero);
      expect(received, hasLength(2));
      expect(received.first.learnerId, 2);
      expect(received.last.learnerId, isNull);
      expect(received.every((event) => event.schoolId == 1), true);
      await subscription.cancel();
      await events.close();
      await refreshes.close();
    },
  );
}
