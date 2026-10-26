import 'dart:async';

import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_academics/src/wellbeing/private_report_image.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'exam_fixture.dart';

Future<void> show(
  WidgetTester tester,
  FakeExamRepository repo, {
  double width = 390,
}) async {
  tester.view.physicalSize = Size(width, 844);
  tester.view.devicePixelRatio = 1;
  tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
  await tester.pumpWidget(
    MaterialApp(
      home: ExamScreen(repository: repo, staff: repo.staff),
    ),
  );
  await tester.pumpAndSettle();
}

Future<void> tap(WidgetTester tester, Finder finder) async {
  await tester.scrollUntilVisible(
    finder,
    220,
    scrollable: find.byType(Scrollable).first,
  );
  await tester.ensureVisible(finder);
  await tester.tap(finder);
  await tester.pumpAndSettle();
}

void main() {
  tearDown(() {});
  for (final width in [390.0, 1280.0]) {
    testWidgets(
      'teacher reads provisional confidence and retries one exact review at $width',
      (tester) async {
        final repo = FakeExamRepository()..unknownReview = true;
        await show(tester, repo, width: width);
        await tap(tester, find.text('Synthetic learner 1'));
        expect(find.text('PRIVATE-FEEDBACK-CANARY'), findsOneWidget);
        expect(find.textContaining('73.0%'), findsOneWidget);
        await tap(tester, find.text('Confirm or correct provisional feedback'));
        expect(repo.writes, isEmpty);
        await tester.enterText(
          find.widgetWithText(TextField, 'Verified marks'),
          '0.75',
        );
        await tester.enterText(
          find.widgetWithText(TextField, 'Review reason'),
          'Teacher checked the equal parts.',
        );
        await tap(tester, find.text('Confirm or correct provisional feedback'));
        expect(repo.writes.length, 1);
        await tap(tester, find.text('Retry exact saved request'));
        expect(repo.writes.length, 2);
        expect(repo.reviewed[0], repo.reviewed[1]);
        expect(repo.writes[0].body, repo.writes[1].body);
        expect(tester.takeException(), isNull);
        await tester.pumpWidget(const SizedBox.shrink());
      },
    );
  }
  testWidgets(
    'paused photo inference leaves learner library and deterministic plan usable',
    (tester) async {
      final repo = FakeExamRepository()
        ..staff = false
        ..enabled = false;
      await show(tester, repo);
      await tap(tester, find.text('Fractions licensed paper'));
      expect(find.textContaining('PRIVATE-STAFF-MEMO-CANARY'), findsNothing);
      expect(find.text('Choose working photo'), findsNothing);
      expect(
        find.textContaining('Photo feedback is currently unavailable'),
        findsOneWidget,
      );
      await tester.tap(find.byType(Checkbox).first);
      await tester.pumpAndSettle();
      await tap(tester, find.byType(DropdownButtonFormField<String>).first);
      await tester.tap(find.text('Published exam · 2026-12-02').last);
      await tester.pumpAndSettle();
      await tap(tester, find.text('Create study plan'));
      expect(repo.planWrites.single.paperVersionIds, [examVersion]);
      expect(repo.planWrites.single.examTimetableId, 4);
      expect(find.text('2026-12-01 · 20 minutes'), findsOneWidget);
      expect(tester.takeException(), isNull);
      await tester.pumpWidget(const SizedBox.shrink());
    },
  );
  testWidgets(
    'background erases working and photo while exact opaque review status can recover',
    (tester) async {
      final repo = FakeExamRepository()..unknownReview = true;
      await show(tester, repo);
      await tap(tester, find.text('Synthetic learner 1'));
      await tester.enterText(
        find.widgetWithText(TextField, 'Review reason'),
        'Private reason erased on background.',
      );
      await tap(tester, find.text('Confirm or correct provisional feedback'));
      expect(repo.writes.length, 1);
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
      await tester.pump();
      expect(find.text('PRIVATE-WORKING-CANARY'), findsNothing);
      expect(find.byType(PrivateReportImage), findsNothing);
      expect(find.text('Private reason erased on background.'), findsNothing);
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await tester.pumpAndSettle();
      expect(repo.receiptCalls, 1);
      expect(repo.writes.length, 1);
      expect(find.text('PRIVATE-FEEDBACK-CANARY'), findsOneWidget);
      await tester.pumpWidget(const SizedBox.shrink());
    },
  );
  testWidgets(
    'a receipt404 racing an in-flight commit preserves the original review UUID after background',
    (tester) async {
      final repo = FakeExamRepository()
        ..unknownReview = true
        ..receiptNotRecorded = true;
      await show(tester, repo);
      await tap(tester, find.text('Synthetic learner 1'));
      await tester.enterText(
        find.widgetWithText(TextField, 'Review reason'),
        'Exact submitted private review',
      );
      await tap(tester, find.text('Confirm or correct provisional feedback'));
      final original = repo.writes.single;
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
      await tester.pump();
      expect(find.text('Exact submitted private review'), findsNothing);
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await tester.pumpAndSettle();
      expect(repo.receiptCalls, 1);
      expect(repo.writes.length, 1);
      expect(find.text('Prepare licensed paper'), findsNothing);
      await tap(tester, find.text('Retry exact saved request'));
      expect(repo.writes.length, 2);
      expect(repo.writes.last.operation, original.operation);
      expect(repo.writes.last.body, original.body);
      await tester.pumpWidget(const SizedBox.shrink());
    },
  );
  testWidgets('expired foreground working is removed without another request', (
    tester,
  ) async {
    final repo = FakeExamRepository()
      ..expiry = DateTime.now().toUtc().add(const Duration(seconds: 30));
    await show(tester, repo);
    await tap(tester, find.text('Synthetic learner 1'));
    expect(find.text('PRIVATE-WORKING-CANARY'), findsOneWidget);
    await tester.pump(const Duration(seconds: 31));
    expect(find.text('PRIVATE-WORKING-CANARY'), findsNothing);
    expect(find.byType(PrivateReportImage), findsNothing);
    expect(find.text('This private working has expired.'), findsOneWidget);
    await tester.pumpWidget(const SizedBox.shrink());
  });
  testWidgets('late prior-generation memo cannot reappear after background', (
    tester,
  ) async {
    final repo = FakeExamRepository()
      ..delayedPaper = Completer<ExamCoachPaperVersion>();
    await show(tester, repo);
    await tester.tap(find.text('Fractions licensed paper'));
    await tester.pump();
    tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
    await tester.pump();
    repo.delayedPaper!.complete(ExamCoachPaperVersion.fromJson(examPaper()));
    await tester.pump();
    expect(find.textContaining('PRIVATE-STAFF-MEMO-CANARY'), findsNothing);
    await tester.pumpWidget(const SizedBox.shrink());
  });
  testWidgets(
    'read-only paper reader cannot prepare, publish or confirm working',
    (tester) async {
      final repo = FakeExamRepository()..readonly = true;
      await show(tester, repo);
      expect(find.text('Prepare licensed paper'), findsNothing);
      await tap(tester, find.text('Fractions licensed paper'));
      expect(find.text('Prepare revised draft'), findsNothing);
      await tap(tester, find.text('Synthetic learner 1'));
      expect(
        find.text('Confirm or correct provisional feedback'),
        findsNothing,
      );
      expect(repo.writes, isEmpty);
      await tester.pumpWidget(const SizedBox.shrink());
    },
  );
}
