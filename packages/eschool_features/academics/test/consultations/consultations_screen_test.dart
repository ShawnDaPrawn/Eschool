import 'dart:async';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'consultation_fixtures.dart';

Widget screen(FakeConsultationRepository repo) =>
    MaterialApp(home: ConsultationsScreen(repository: repo));
Future<void> tap(WidgetTester tester, String text) async {
  final target = find.text(text).first;
  await tester.ensureVisible(target);
  await tester.pumpAndSettle();
  await tester.tap(target);
  await tester.pumpAndSettle();
}

Future<void> open(WidgetTester tester, FakeConsultationRepository repo) async {
  await tester.pumpWidget(screen(repo));
  await tester.pumpAndSettle();
  await tap(tester, repo.title);
}

Future<void> answer(WidgetTester tester) async {
  final option = find.byKey(const ValueKey('consultation-option-11'));
  await tester.ensureVisible(option);
  await tester.tap(option);
  await tester.pumpAndSettle();
  final text = find.byKey(const ValueKey('consultation-answer-4'));
  await tester.ensureVisible(text);
  await tester.enterText(text, 'PRIVATE OWN ANSWER');
  await tester.pump();
}

void main() {
  for (final width in [390.0, 820.0, 1280.0]) {
    testWidgets('school-time ballot and one anonymous response fit $width', (
      tester,
    ) async {
      tester.view.physicalSize = Size(width, 1000);
      tester.view.devicePixelRatio = 1;
      addTearDown(tester.view.resetPhysicalSize);
      addTearDown(tester.view.resetDevicePixelRatio);
      final repo = FakeConsultationRepository();
      await open(tester, repo);
      expect(
        find.textContaining('2026-02-10 10:00 (Africa/Johannesburg)'),
        findsOneWidget,
      );
      await answer(tester);
      await tap(tester, 'Record my response');
      expect(repo.posts, hasLength(1));
      expect(repo.posts.single.last.text, 'PRIVATE OWN ANSWER');
      expect(
        find.text('PRIVATE OWN ANSWER', skipOffstage: false),
        findsNothing,
      );
      expect(
        find.text('Your response is recorded. You can participate only once.'),
        findsWidgets,
      );
      expect(find.byKey(const ValueKey('consultation-answer-4')), findsNothing);
      expect(tester.takeException(), isNull);
    });
  }
  testWidgets(
    'unknown committed submission checks participation without another vote',
    (tester) async {
      final repo = FakeConsultationRepository()..unknownOnce = true;
      await open(tester, repo);
      await answer(tester);
      await tap(tester, 'Record my response');
      expect(repo.posts, hasLength(1));
      await tester.drag(find.byType(ListView), const Offset(0, 1800));
      await tester.pumpAndSettle();
      expect(
        find.text(
          'Your submission outcome is unknown. Check participation before trying again.',
        ),
        findsOneWidget,
      );
      await tap(tester, 'Check whether my response was recorded');
      expect(repo.posts, hasLength(1));
      expect(find.byKey(const ValueKey('consultation-answer-4')), findsNothing);
      expect(
        find.text('PRIVATE OWN ANSWER', skipOffstage: false),
        findsNothing,
      );
      expect(tester.takeException(), isNull);
    },
  );
  testWidgets(
    'unknown uncommitted outcome retains own draft but requires a fresh participation read',
    (tester) async {
      final repo = FakeConsultationRepository()
        ..unknownOnce = true
        ..commitUnknown = false;
      await open(tester, repo);
      await answer(tester);
      await tap(tester, 'Record my response');
      expect(repo.posts, hasLength(1));
      await tap(tester, 'Check whether my response was recorded');
      expect(repo.posts, hasLength(1));
      expect(find.text('PRIVATE OWN ANSWER'), findsOneWidget);
      await tap(tester, 'Record my response');
      expect(repo.posts, hasLength(2));
      expect(tester.takeException(), isNull);
    },
  );
  testWidgets(
    'read-only preserves authorized questions and disables all voting controls',
    (tester) async {
      final repo = FakeConsultationRepository()..writable = false;
      await open(tester, repo);
      expect(find.text('Synthetic fixture time'), findsOneWidget);
      expect(find.text('This consultation is read only.'), findsOneWidget);
      final submit = find.widgetWithText(FilledButton, 'Record my response');
      await tester.ensureVisible(submit);
      expect(tester.widget<FilledButton>(submit).onPressed, isNull);
      expect(
        tester
            .widget<CheckboxListTile>(
              find.byKey(const ValueKey('consultation-option-11')),
            )
            .onChanged,
        isNull,
      );
      expect(repo.posts, isEmpty);
    },
  );
  testWidgets(
    'fresh permission refusal erases ballot instructions and own draft',
    (tester) async {
      final repo = FakeConsultationRepository();
      await open(tester, repo);
      await answer(tester);
      repo.deny = true;
      await tap(tester, 'Record my response');
      expect(find.text('Synthetic private instructions.'), findsNothing);
      expect(
        find.text('PRIVATE OWN ANSWER', skipOffstage: false),
        findsNothing,
      );
      expect(
        find.text('Your consultation access changed. Reopen this screen.'),
        findsOneWidget,
      );
      expect(repo.posts, isEmpty);
      expect(tester.takeException(), isNull);
    },
  );
  testWidgets(
    'background clears answers and resume starts with a fresh scoped list',
    (tester) async {
      final repo = FakeConsultationRepository();
      await open(tester, repo);
      await answer(tester);
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.hidden);
      await tester.pump();
      expect(
        find.text('PRIVATE OWN ANSWER', skipOffstage: false),
        findsNothing,
      );
      repo.title = 'Fresh authorized consultation';
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await tester.pumpAndSettle();
      expect(find.text('Fresh authorized consultation'), findsOneWidget);
      expect(repo.accessCalls, greaterThan(2));
      expect(tester.takeException(), isNull);
    },
  );
  testWidgets(
    'late ballot after the reader context changes never reaches the page',
    (tester) async {
      final repo = FakeConsultationRepository()..pendingBallot = Completer();
      await tester.pumpWidget(screen(repo));
      await tester.pumpAndSettle();
      await tester.tap(find.text(repo.title));
      await tester.pump();
      repo.active = false;
      repo.pendingBallot!.complete(repo.ballotValue());
      await tester.pumpAndSettle();
      expect(find.text('Synthetic private instructions.'), findsNothing);
      expect(tester.takeException(), isNull);
    },
  );
  testWidgets(
    'live aggregate view shows denominators and never unknown individual texts',
    (tester) async {
      final repo = FakeConsultationRepository()..live = true;
      await open(tester, repo);
      await tap(tester, 'View consultation results');
      expect(find.text('Responses recorded: 4'), findsOneWidget);
      expect(find.text('Morning: 2 / 3'), findsOneWidget);
      expect(
        find.text('UNAUTHORIZED OTHER PERSON ANSWER', skipOffstage: false),
        findsNothing,
      );
      expect(tester.takeException(), isNull);
    },
  );
}
