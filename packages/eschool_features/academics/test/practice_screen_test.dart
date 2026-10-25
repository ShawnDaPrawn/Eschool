import 'dart:io';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'practice_fixture.dart';

Future<void> tapPractice(WidgetTester tester, String text) async {
  final finder = find.text(text);
  final target = finder.last;
  if (finder.evaluate().isEmpty) {
    await tester.scrollUntilVisible(
      finder,
      200,
      scrollable: find.byType(Scrollable).first,
    );
  }
  await tester.ensureVisible(target);
  await tester.pumpAndSettle();
  await tester.tap(target);
  await tester.pumpAndSettle();
}

Future<void> student(
  WidgetTester tester,
  FakePracticeRepository repo, {
  bool Function()? read,
  bool Function()? write,
}) async {
  final store = MemoryContentStore();
  final cache = PracticeCache(
    repo,
    store: () async => store,
    canQueue: write ?? () => true,
  );
  await tester.pumpWidget(
    MaterialApp(
      home: PracticeScreen(
        repository: repo,
        cache: cache,
        canRead: read ?? () => true,
        canWrite: write ?? () => true,
      ),
    ),
  );
  await tester.pumpAndSettle();
}

void main() {
  testWidgets(
    'learner answers show worked feedback and official marks are never an operation',
    (tester) async {
      final repo = FakePracticeRepository();
      await student(tester, repo);
      await tapPractice(tester, 'Mathematics · Grade 7');
      expect(find.text('One half as a decimal?'), findsOneWidget);
      expect(find.text('Divide one by two to get 0.5.'), findsNothing);
      await tester.enterText(find.byType(TextField), '.5');
      await tapPractice(tester, 'Check answer');
      expect(repo.writes.single.action, PracticeAction.review);
      expect(repo.writes.single.body['answer'], '.5');
      expect(find.text('Correct'), findsOneWidget);
      expect(find.text('Divide one by two to get 0.5.'), findsOneWidget);
      expect(tester.takeException(), isNull);
    },
  );
  testWidgets(
    'unknown answer outcome retries the exact intent then clears it after confirmation',
    (tester) async {
      final repo = FakePracticeRepository()
        ..reviewError = const FileSystemException('Synthetic lost response');
      await student(tester, repo);
      await tapPractice(tester, 'Mathematics · Grade 7');
      await tester.enterText(find.byType(TextField), '.5');
      await tapPractice(tester, 'Check answer');
      expect(find.text('Retry exact operation'), findsOneWidget);
      final first = repo.writes.single;
      repo.reviewError = null;
      await tapPractice(tester, 'Retry exact operation');
      expect(repo.writes.length, 2);
      expect(repo.writes.last.body, first.body);
      expect(find.text('Retry exact operation'), findsNothing);
      expect(find.text('Correct'), findsOneWidget);
    },
  );
  testWidgets(
    'offline copy queues an exact answer without inference and syncs after fresh access',
    (tester) async {
      final repo = FakePracticeRepository()..time = DateTime.now().toUtc();
      await student(tester, repo);
      await tapPractice(tester, 'Mathematics · Grade 7');
      await tapPractice(tester, 'Save for offline');
      expect(
        find.text('Encrypted practice copy saved for offline use.'),
        findsOneWidget,
      );
      await tester.enterText(find.byType(TextField), '.5');
      await tapPractice(tester, 'Check answer');
      expect(repo.writes, isEmpty);
      expect(find.text('Correct'), findsOneWidget);
      final target = find.text('Sync saved answers (1)');
      if (target.evaluate().isEmpty) {
        await tester.scrollUntilVisible(
          target,
          200,
          scrollable: find.byType(Scrollable).first,
        );
      }
      await tester.ensureVisible(target);
      await tester.tap(target);
      await tester.pumpAndSettle();
      expect(repo.accessReads, 2);
      expect(repo.writes.length, 1);
      expect(find.text('Answers synchronized.'), findsOneWidget);
    },
  );
  testWidgets(
    'known read-only practice exposes questions without sending or queuing answers',
    (tester) async {
      final repo = FakePracticeRepository()..writable = false;
      await student(tester, repo, write: () => false);
      await tapPractice(tester, 'Mathematics · Grade 7');
      final button = tester.widget<FilledButton>(
        find.widgetWithText(FilledButton, 'Check answer'),
      );
      expect(button.onPressed, isNull);
      expect(repo.writes, isEmpty);
      expect(find.text('Divide one by two to get 0.5.'), findsNothing);
    },
  );
  testWidgets(
    'background and fresh source withdrawal erase rendered question bodies',
    (tester) async {
      final repo = FakePracticeRepository();
      await student(tester, repo);
      await tapPractice(tester, 'Mathematics · Grade 7');
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
      await tester.pumpAndSettle();
      expect(find.text('One half as a decimal?'), findsNothing);
      repo.error = ApiException(
        ApiErrorCodes.defaultErrorMessageKey,
        statusCode: 403,
      );
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await tester.pumpAndSettle();
      expect(
        find.text('Reopen practice to check your access.'),
        findsOneWidget,
      );
      expect(find.text('One half as a decimal?'), findsNothing);
    },
  );
  for (final width in [390.0, 820.0, 1280.0]) {
    testWidgets('teacher approves a real bounded question at width $width', (
      tester,
    ) async {
      tester.view.physicalSize = Size(width, 1000);
      tester.view.devicePixelRatio = 1;
      addTearDown(tester.view.resetPhysicalSize);
      addTearDown(tester.view.resetDevicePixelRatio);
      final repo = FakePracticeRepository()..staff = true;
      await tester.pumpWidget(
        MaterialApp(
          home: PracticeBankScreen(
            repository: repo,
            canRead: () => true,
            canWrite: () => true,
          ),
        ),
      );
      await tester.pumpAndSettle();
      await tapPractice(tester, 'New practice question');
      final dropdowns = find.byType(DropdownButtonFormField<int>);
      await tester.ensureVisible(dropdowns.at(1));
      await tester.tap(dropdowns.at(1));
      await tester.pumpAndSettle();
      await tester.tap(find.text('Fractions').last);
      await tester.pumpAndSettle();
      await tester.ensureVisible(dropdowns.at(2));
      await tester.tap(dropdowns.at(2));
      await tester.pumpAndSettle();
      await tester.tap(find.text('Approved fractions').last);
      await tester.pumpAndSettle();
      final textFields = find.byType(TextField);
      await tester.enterText(textFields.at(0), 'What is one half?');
      await tester.enterText(textFields.at(1), 'Divide one by two.');
      await tester.enterText(textFields.at(2), '.5\n0.500');
      await tapPractice(tester, 'Approve question revision');
      expect(repo.writes.single.action, PracticeAction.approve);
      expect(repo.writes.single.body['definition']['answers'], ['0.5']);
      expect(repo.writes.single.body['content_version_id'], 23);
      expect(find.text('Question revision approved.'), findsOneWidget);
      expect(tester.takeException(), isNull);
    });
  }
  testWidgets(
    'teacher unknown approval freezes its payload; exact retry remains unchanged',
    (tester) async {
      final repo = FakePracticeRepository()..staff = true;
      var fail = true;
      repo.approved = PracticeBankItem(
        id: 21,
        topicId: 22,
        revision: 3,
        active: true,
        contentVersionId: 23,
        estimatedSeconds: 95,
        definition: practiceSet().items.single.definition,
      );
      repo.approving = (intent) async {
        if (fail) throw const FileSystemException('Synthetic lost response');
        return PracticeApproval(
          id: 21,
          versionId: 31,
          revision: 4,
          active: true,
        );
      };
      await tester.pumpWidget(
        MaterialApp(
          home: PracticeBankScreen(
            repository: repo,
            canRead: () => true,
            canWrite: () => true,
          ),
        ),
      );
      await tester.pumpAndSettle();
      await tapPractice(tester, 'One half as a decimal?');
      await tapPractice(tester, 'Approve question revision');
      final first = repo.writes.single;
      expect(first.body['expected_revision'], 3);
      expect(first.body['estimated_seconds'], 95);
      expect(find.text('Retry exact operation'), findsOneWidget);
      fail = false;
      await tapPractice(tester, 'Retry exact operation');
      expect(repo.writes.last.body, first.body);
      expect(tester.takeException(), isNull);
    },
  );
}
