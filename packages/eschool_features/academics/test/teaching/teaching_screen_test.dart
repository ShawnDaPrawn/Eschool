import 'dart:async';
import 'dart:typed_data';
import 'dart:ui' as ui;

import 'package:eschool_academics/eschool_academics.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'teaching_fixture.dart';

Future<void> show(
  WidgetTester tester,
  FakeTeachingRepository repo, {
  bool insights = false,
  Future<Uint8List?> Function()? picker,
}) async {
  tester.view.physicalSize = const Size(390, 844);
  tester.view.devicePixelRatio = 1;
  tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
  await tester.pumpWidget(
    MaterialApp(
      home: TeachingScreen(
        repository: repo,
        insightsOnly: insights,
        selectPhoto: picker,
      ),
    ),
  );
  await tester.pumpAndSettle();
}

Future<void> tap(WidgetTester tester, Finder finder) async {
  await tester.scrollUntilVisible(
    finder,
    240,
    scrollable: find.byType(Scrollable).first,
  );
  await tester.pumpAndSettle();
  await tester.ensureVisible(finder);
  await tester.pumpAndSettle();
  await tester.tap(finder);
  await tester.pumpAndSettle();
}

Future<void> openDraft(
  WidgetTester tester, {
  String label = 'Three-level worksheet',
}) => tap(tester, find.text(label));

Future<Uint8List> workingImage(WidgetTester tester) async {
  late Uint8List bytes;
  await tester.runAsync(() async {
    final recorder = ui.PictureRecorder();
    Canvas(
      recorder,
    ).drawRect(const Rect.fromLTWH(0, 0, 20, 20), Paint()..color = Colors.blue);
    final picture = recorder.endRecording();
    final image = await picture.toImage(20, 20);
    bytes = (await image.toByteData(
      format: ui.ImageByteFormat.png,
    ))!.buffer.asUint8List();
    image.dispose();
    picture.dispose();
  });
  return bytes;
}

Future<void> chooseScript(WidgetTester tester) async {
  await tap(tester, find.text('New draft'));
  await tester.tap(find.byType(DropdownButtonFormField<String>).first);
  await tester.pumpAndSettle();
  await tester.tap(find.text('Script proposals').last);
  await tester.pumpAndSettle();
  final learner = find.widgetWithText(DropdownButtonFormField<int>, 'Learner');
  await tap(tester, learner);
  await tester.tap(find.text('Private learner canary').last);
  await tester.pumpAndSettle();
}

Future<void> startPicker(WidgetTester tester) async {
  final button = find.text('Choose working photo');
  await tester.scrollUntilVisible(
    button,
    240,
    scrollable: find.byType(Scrollable).first,
  );
  await tester.pumpAndSettle();
  await tester.ensureVisible(button);
  await tester.pumpAndSettle();
  await tester.tap(button);
  await tester.pump();
}

void main() {
  testWidgets('a known school allowance refusal remains readable', (
    tester,
  ) async {
    const message =
        'The school AI allowance is used for today. Ask an administrator about the limit.';
    final repo = FakeTeachingRepository()..allowanceMessage = message;
    await show(tester, repo);
    await tap(tester, find.text('New draft'));
    await tester.enterText(
      find.byKey(const ValueKey('teaching-brief')),
      'Prepare fractions practice.',
    );
    await tester.pump();
    await tap(tester, find.byKey(const ValueKey('teaching-generate')));
    await tester.scrollUntilVisible(
      find.text(message),
      -240,
      scrollable: find.byType(Scrollable).first,
    );
    expect(find.text(message), findsOneWidget);
    expect(repo.reviewEffects, 0);
    await tester.pumpWidget(const SizedBox());
    tester.view.resetPhysicalSize();
    tester.view.resetDevicePixelRatio();
  });
  testWidgets(
    'approved photo upload recovers the same bytes and operation without compute',
    (tester) async {
      final bytes = await workingImage(tester);
      final repo = FakeTeachingRepository()..loseUpload = true;
      await show(tester, repo, picker: () async => bytes);
      await chooseScript(tester);
      await startPicker(tester);
      await tester.runAsync(
        () => Future<void>.delayed(const Duration(milliseconds: 30)),
      );
      await tester.pumpAndSettle();
      await tap(
        tester,
        find.text('Select the whole photo if it contains only working'),
      );
      await tap(tester, find.byType(CheckboxListTile));
      await tap(tester, find.text('Use this working crop'));
      expect(repo.uploads, hasLength(1));
      await tap(tester, find.text('Retry approved photo upload'));
      expect(repo.uploads, hasLength(2));
      expect(repo.uploads[0].crop.operation, repo.uploads[1].crop.operation);
      expect(repo.uploads[0].crop.bytes, repo.uploads[1].crop.bytes);
      expect(repo.uploads[0].learner, repo.uploads[1].learner);
      expect(repo.generations, 0);
      expect(tester.takeException(), isNull);
      await tester.pumpWidget(const SizedBox());
      tester.view.resetPhysicalSize();
      tester.view.resetDevicePixelRatio();
    },
  );
  for (final denied in [false, true]) {
    testWidgets(
      'native picker rechecks fresh access after inactive/resume (denied=$denied)',
      (tester) async {
        final bytes = await workingImage(tester);
        final result = Completer<Uint8List?>();
        final repo = FakeTeachingRepository();
        await show(tester, repo, picker: () => result.future);
        await chooseScript(tester);
        final initialChecks = repo.configurations;
        await startPicker(tester);
        tester.binding.handleAppLifecycleStateChanged(
          AppLifecycleState.inactive,
        );
        await tester.pump();
        expect(find.text('Private learner canary'), findsNothing);
        repo.denied = denied;
        tester.binding.handleAppLifecycleStateChanged(
          AppLifecycleState.resumed,
        );
        result.complete(bytes);
        await tester.pump();
        await tester.runAsync(
          () => Future<void>.delayed(const Duration(milliseconds: 30)),
        );
        await tester.pumpAndSettle();
        expect(
          find.byType(TutorWorkingCrop),
          denied ? findsNothing : findsOneWidget,
        );
        if (!denied) expect(repo.configurations, greaterThan(initialChecks));
        expect(repo.uploads, isEmpty);
        expect(repo.generations, 0);
        expect(tester.takeException(), isNull);
        await tester.pumpWidget(const SizedBox());
        tester.view.resetPhysicalSize();
        tester.view.resetDevicePixelRatio();
      },
    );
  }
  testWidgets(
    'line edits must be saved before approval; approval never publishes or records marks',
    (tester) async {
      final repo = FakeTeachingRepository()..current = teachingDetailJson();
      await show(tester, repo);
      await openDraft(tester);
      await tester.enterText(
        find.byKey(const ValueKey('teaching-edit-title')),
        'Teacher edited lesson',
      );
      await tester.pump();
      await tester.scrollUntilVisible(
        find.byKey(const ValueKey('teaching-approve')),
        240,
        scrollable: find.byType(Scrollable).first,
      );
      expect(
        tester
            .widget<OutlinedButton>(
              find.byKey(const ValueKey('teaching-approve')),
            )
            .onPressed,
        isNull,
      );
      await tap(tester, find.byKey(const ValueKey('teaching-save-edit')));
      expect(repo.sentReviews.single.body['action'], 'edit');
      await tap(tester, find.byKey(const ValueKey('teaching-approve')));
      expect(repo.current!['state'], 'approved');
      expect(repo.publications, 0);
      expect(repo.officialMarkWrites, 0);
      expect(repo.reviewEffects, 2);
      expect(tester.takeException(), isNull);
      await tester.pumpWidget(const SizedBox());
      tester.view.resetPhysicalSize();
      tester.view.resetDevicePixelRatio();
    },
  );
  testWidgets(
    'lost generation response is recovered with its original receipt and one compute',
    (tester) async {
      final repo = FakeTeachingRepository()..loseGeneration = true;
      await show(tester, repo);
      await tap(tester, find.text('New draft'));
      await tester.enterText(
        find.byKey(const ValueKey('teaching-brief')),
        'Prepare a fractions worksheet.',
      );
      await tester.pump();
      await tap(tester, find.byKey(const ValueKey('teaching-generate')));
      expect(repo.generations, 1);
      await tap(tester, find.text('Check saved request'));
      expect(repo.receiptReads, 1);
      expect(repo.generations, 1);
      expect(repo.reviewEffects, 0);
      expect(tester.takeException(), isNull);
      await tester.pumpWidget(const SizedBox());
      tester.view.resetPhysicalSize();
      tester.view.resetDevicePixelRatio();
    },
  );
  testWidgets('lost human review retries one immutable UUID with one effect', (
    tester,
  ) async {
    final repo = FakeTeachingRepository()
      ..current = teachingDetailJson()
      ..loseReview = true;
    await show(tester, repo);
    await openDraft(tester);
    await tap(tester, find.byKey(const ValueKey('teaching-approve')));
    expect(repo.reviewEffects, 1);
    await tap(tester, find.text('Recover original review'));
    expect(repo.sentReviews, hasLength(2));
    expect(repo.sentReviews[0].operation, repo.sentReviews[1].operation);
    expect(repo.sentReviews[0].body, repo.sentReviews[1].body);
    expect(repo.reviewEffects, 1);
    expect(repo.officialMarkWrites, 0);
    await tester.pumpWidget(const SizedBox());
    tester.view.resetPhysicalSize();
    tester.view.resetDevicePixelRatio();
  });
  testWidgets(
    'background erases private body and unsaved lines before fresh denied resume',
    (tester) async {
      final repo = FakeTeachingRepository()..current = teachingDetailJson();
      await show(tester, repo);
      await openDraft(tester);
      await tester.enterText(
        find.byKey(const ValueKey('teaching-edit-title')),
        'Unsaved private teaching canary',
      );
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
      await tester.pump();
      expect(find.text('Unsaved private teaching canary'), findsNothing);
      expect(find.text('Private worksheet canary'), findsNothing);
      expect(find.textContaining('Private learner'), findsNothing);
      repo.denied = true;
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await tester.pumpAndSettle();
      expect(find.text('Unsaved private teaching canary'), findsNothing);
      expect(find.text('Private worksheet canary'), findsNothing);
      expect(repo.generations, 0);
      expect(tester.takeException(), isNull);
      await tester.pumpWidget(const SizedBox());
      tester.view.resetPhysicalSize();
      tester.view.resetDevicePixelRatio();
    },
  );
  testWidgets(
    'late generation cannot paint after background or revoked resume',
    (tester) async {
      final delayed = Completer<TeachingDraftDetail>();
      final repo = FakeTeachingRepository()..delayedGeneration = delayed;
      await show(tester, repo);
      await tap(tester, find.text('New draft'));
      await tester.enterText(
        find.byKey(const ValueKey('teaching-brief')),
        'Private late request canary',
      );
      await tester.pump();
      await tester.scrollUntilVisible(
        find.byKey(const ValueKey('teaching-generate')),
        240,
        scrollable: find.byType(Scrollable).first,
      );
      await tester.tap(find.byKey(const ValueKey('teaching-generate')));
      await tester.pump();
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
      await tester.pump();
      delayed.complete(TeachingDraftDetail.fromJson(teachingDetailJson()));
      await tester.pump();
      expect(find.text('Private worksheet canary'), findsNothing);
      repo.denied = true;
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await tester.pumpAndSettle();
      expect(find.text('Private late request canary'), findsNothing);
      expect(find.text('Private worksheet canary'), findsNothing);
      expect(repo.generations, 1);
      await tester.pumpWidget(const SizedBox());
      tester.view.resetPhysicalSize();
      tester.view.resetDevicePixelRatio();
    },
  );
  testWidgets(
    'insights show unknown evidence independently and never generate',
    (tester) async {
      final repo = FakeTeachingRepository();
      await show(tester, repo, insights: true);
      await tester.tap(find.byType(DropdownButtonFormField<int>).last);
      await tester.pumpAndSettle();
      await tester.tap(find.text('Fractions assessment').last);
      await tester.pumpAndSettle();
      await tap(tester, find.text('View test insights'));
      expect(
        find.text('No question evidence yet. Missing marks are unknown.'),
        findsOneWidget,
      );
      expect(find.textContaining('Average Unknown'), findsOneWidget);
      expect(repo.generations, 0);
      expect(repo.reviewEffects, 0);
      expect(tester.takeException(), isNull);
      await tester.pumpWidget(const SizedBox());
      tester.view.resetPhysicalSize();
      tester.view.resetDevicePixelRatio();
    },
  );
  testWidgets(
    'unknown script mark cannot be confirmed even after the checkbox',
    (tester) async {
      final draft = teachingDetailJson(state: 'approved');
      draft['kind'] = 'script_marks';
      draft['definition'] = {
        'question_marks': [
          {
            'question_id': 8,
            'mark': null,
            'confidence_bp': 5000,
            'reason': 'Incomplete visible working.',
          },
        ],
      };
      draft['question_labels'] = [
        {'question_id': 8, 'number': '1', 'max_mark': '10.00'},
      ];
      final repo = FakeTeachingRepository()..current = draft;
      await show(tester, repo);
      await openDraft(tester, label: 'Script proposals');
      await tap(tester, find.byType(CheckboxListTile));
      final confirmation = find.widgetWithText(
        FilledButton,
        'Confirm official marks',
      );
      expect(tester.widget<FilledButton>(confirmation).onPressed, isNull);
      expect(repo.officialMarkWrites, 0);
      expect(tester.takeException(), isNull);
      await tester.pumpWidget(const SizedBox());
      tester.view.resetPhysicalSize();
      tester.view.resetDevicePixelRatio();
    },
  );
}
