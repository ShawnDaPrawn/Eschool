import 'dart:async';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'fake_recognition.dart';

Future<void> show(
  WidgetTester tester,
  FakeRecognition repo, {
  bool family = true,
}) async {
  await tester.pumpWidget(
    MaterialApp(
      home: RecognitionScreen(
        repository: repo,
        modules: const ['house_points', 'awards'],
        family: family,
        learnerId: family ? 2 : null,
        learnerName: family ? 'Synthetic learner' : null,
      ),
    ),
  );
  await tester.pumpAndSettle();
}

Future<void> tap(WidgetTester tester, Finder finder) async {
  if (finder.evaluate().isEmpty) {
    await tester.scrollUntilVisible(
      finder,
      250,
      scrollable: find.byType(Scrollable).first,
      maxScrolls: 15,
    );
  }
  await tester.ensureVisible(finder);
  await tester.pumpAndSettle();
  await tester.tap(finder);
  await tester.pumpAndSettle();
}

Future<void> issueForm(WidgetTester tester, FakeRecognition repo) async {
  await show(tester, repo, family: false);
  await tap(tester, find.text('Synthetic learner'));
  final category = find.byType(DropdownButtonFormField<int>);
  await tap(tester, category);
  await tap(tester, find.text('Helping').last);
  final reason = find.widgetWithText(TextFormField, 'Reason for points');
  await tester.ensureVisible(reason);
  await tester.enterText(reason, 'Private new contribution');
  tester.testTextInput.hide();
  await tester.pumpAndSettle();
}

void main() {
  for (final width in [390.0, 820.0, 1280.0]) {
    testWidgets(
      'family counters and permanent awards remain separate at $width',
      (tester) async {
        tester.view.physicalSize = Size(width, 1000);
        tester.view.devicePixelRatio = 1;
        addTearDown(tester.view.resetPhysicalSize);
        addTearDown(tester.view.resetDevicePixelRatio);
        final repo = FakeRecognition();
        await show(tester, repo);
        await tap(tester, find.byType(DropdownButtonFormField<String>));
        await tap(tester, find.text('Permanent awards').last);
        expect(find.text('Synthetic service award'), findsOneWidget);
        expect(
          find.textContaining('Private existing point evidence'),
          findsNothing,
        );
        expect(find.text('Award points'), findsNothing);
        expect(tester.takeException(), isNull);
        await tester.pumpWidget(const SizedBox.shrink());
      },
    );
  }
  testWidgets(
    'unknown point outcome retries immutable UUID/body and applies once',
    (tester) async {
      final repo = FakeRecognition()..offlineOnce = true;
      await issueForm(tester, repo);
      await tap(tester, find.widgetWithText(FilledButton, 'Award points'));
      expect(repo.writes, hasLength(1));
      await tap(
        tester,
        find.widgetWithText(SnackBarAction, 'Retry exact change'),
      );
      expect(repo.writes, hasLength(2));
      expect(repo.writes[0], repo.writes[1]);
      expect(repo.operations, hasLength(1));
      expect(find.text('Retry exact point change'), findsNothing);
      await tester.pumpWidget(const SizedBox.shrink());
    },
  );
  testWidgets(
    'background and denied resume erase private rows and typed drafts',
    (tester) async {
      final repo = FakeRecognition();
      await issueForm(tester, repo);
      expect(find.text('Private new contribution'), findsOneWidget);
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
      await tester.pump();
      expect(find.text('Private new contribution'), findsNothing);
      expect(
        find.textContaining('Private existing point evidence'),
        findsNothing,
      );
      repo.deny = true;
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await tester.pumpAndSettle();
      expect(
        find.text('Your recognition access changed. Reopen this screen.'),
        findsOneWidget,
      );
      expect(repo.writes, isEmpty);
      await tester.pumpWidget(const SizedBox.shrink());
    },
  );
  testWidgets(
    'same-school private invalidation celebrates only fresh owned points',
    (tester) async {
      final repo = FakeRecognition();
      await show(tester, repo);
      expect(find.textContaining('5 house points'), findsNothing);
      repo.addMoment();
      await tester.pump();
      await tester.pump(const Duration(milliseconds: 200));
      await tester.pumpAndSettle();
      expect(find.textContaining('5 house points'), findsOneWidget);
      final reads = repo.reads;
      repo.events.add(
        const RecognitionInvalidation(schoolId: 99, learnerId: 2),
      );
      repo.events.add(
        const RecognitionInvalidation(schoolId: 1, learnerId: 99),
      );
      await tester.pump(const Duration(milliseconds: 300));
      expect(repo.reads, reads);
      await tester.pumpWidget(const SizedBox.shrink());
    },
  );
  testWidgets('late prior-view refusal cannot erase current permanent awards', (
    tester,
  ) async {
    final repo = FakeRecognition();
    await show(tester, repo);
    final pending = Completer<RecognitionPointHistory>();
    repo.delayedHistory = pending;
    repo.events.add(const RecognitionInvalidation(schoolId: 1, learnerId: 2));
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 200));
    await tap(tester, find.byType(DropdownButtonFormField<String>));
    await tap(tester, find.text('Permanent awards').last);
    pending.completeError(ApiException('Old view withdrawn', statusCode: 403));
    await tester.pumpAndSettle();
    expect(find.text('Synthetic service award'), findsOneWidget);
    expect(
      find.text('Your recognition access changed. Reopen this screen.'),
      findsNothing,
    );
    await tester.pumpWidget(const SizedBox.shrink());
  });
  testWidgets('read-only staff retains history without an enabled issue form', (
    tester,
  ) async {
    final repo = FakeRecognition()..writable = false;
    await show(tester, repo, family: false);
    await tap(tester, find.text('Synthetic learner'));
    expect(find.text('Award points'), findsNothing);
    expect(repo.writes, isEmpty);
    await tester.pumpWidget(const SizedBox.shrink());
  });
  testWidgets(
    'same element account replacement rejects a late previous private payload',
    (tester) async {
      final old = FakeRecognition(),
          pending = Completer<RecognitionPointHistory>();
      old.delayedHistory = pending;
      Widget page(FakeRecognition repo, String name) => MaterialApp(
        home: RecognitionScreen(
          key: const ValueKey('recognition'),
          repository: repo,
          modules: const ['house_points', 'awards'],
          family: true,
          learnerId: 2,
          learnerName: name,
        ),
      );
      await tester.pumpWidget(page(old, 'Old learner'));
      await tester.pump();
      await tester.pump();
      final current = FakeRecognition()
        ..schoolId = 2
        ..accountId = 99;
      await tester.pumpWidget(page(current, 'New learner'));
      await tester.pumpAndSettle();
      pending.complete(
        RecognitionPointHistory.fromJson({
          'items': [
            {
              'id': 66,
              'house_id': 3,
              'house_name': 'Old house',
              'category_name': 'Old category',
              'points': 5,
              'reason': 'Late previous private point payload',
              'on_date': '2026-02-10',
              'public': false,
              'reverses_id': null,
            },
          ],
          'next_before': null,
        }),
      );
      await tester.pumpAndSettle();
      expect(find.text('New learner'), findsOneWidget);
      expect(find.text('Old learner'), findsNothing);
      expect(
        find.textContaining('Late previous private point payload'),
        findsNothing,
      );
      expect(current.accessCalls, 1);
      await tester.pumpWidget(const SizedBox.shrink());
    },
  );
}
