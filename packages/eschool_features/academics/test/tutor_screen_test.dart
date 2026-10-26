import 'dart:async';
import 'dart:ui' as ui;
import 'dart:typed_data';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'tutor_fixture.dart';

Future<void> tapTutor(WidgetTester tester, String label) async {
  final target = find.text(label).last;
  await tester.ensureVisible(target);
  await tester.pumpAndSettle();
  await tester.tap(target);
  await tester.pumpAndSettle();
}

Future<void> mountTutor(
  WidgetTester tester,
  FakeTutorRepository repo, {
  bool Function()? write,
}) async {
  await tester.pumpWidget(
    MaterialApp(
      home: TutorScreen(
        repository: repo,
        initialSession: tutorSession,
        canWrite: write,
      ),
    ),
  );
  await tester.pumpAndSettle();
}

Future<void> question(WidgetTester tester) async {
  final field = find.byType(TextField).first;
  await tester.ensureVisible(field);
  await tester.enterText(field, 'How do fractions work?');
  await tester.enterText(
    find.byType(TextField).last,
    'I drew two equal parts.',
  );
}

class _ParentSummary implements TutorSummaryRepository {
  bool active = true;
  int reads = 0;
  @override
  bool get isActive => active;
  @override
  int get schoolId => 1;
  @override
  int get readerId => 7;
  @override
  Future<ContentLibrary> subjects(int learner, {int page = 1}) async =>
      ContentLibrary.fromJson({
        'learner_id': learner,
        'page': page,
        'last_page': 1,
        'items': [
          {
            'id': 1,
            'version_id': 5,
            'title': 'Fractions',
            'summary': 'Approved content',
            'subject_id': 4,
            'subject_name': 'Mathematics',
            'published_at': '2026-10-25T00:00:00Z',
          },
        ],
      });
  @override
  Future<TutorParentSummary> summary(int learner, int subject) async {
    reads++;
    return TutorParentSummary.fromJson({
      'school_id': 1,
      'reader_id': 7,
      'learner_id': learner,
      'subject_id': subject,
      'subject_name': 'Mathematics',
      'learning_sessions': 2,
      'learning_steps': 3,
      'suggestion_key': 'Ask your child to explain one worked example.',
      'contains_raw_chat': false,
    });
  }
}

void main() {
  testWidgets(
    'checking a committed receipt clears the consumed human draft without resending',
    (tester) async {
      final repo = FakeTutorRepository()..loseSend = true;
      await mountTutor(tester, repo);
      await question(tester);
      await tapTutor(tester, 'Ask for a hint');
      repo.turns = [tutorTurnJson(operation: repo.writes.single.operation)];
      await tapTutor(tester, 'Check reply');
      expect(repo.writes, hasLength(1));
      await tester.scrollUntilVisible(
        find.text('Ask for a hint'),
        200,
        scrollable: find.byType(Scrollable).first,
      );
      for (final field in tester.widgetList<TextField>(
        find.byType(TextField),
      )) {
        expect(field.controller!.text, isEmpty);
      }
      expect(find.text('Check reply'), findsNothing);
    },
  );
  test('PNG/JPEG dimensions are bounded before any platform decoder', () {
    final png = Uint8List(33)
      ..setAll(0, [137, 80, 78, 71, 13, 10, 26, 10])
      ..setAll(12, 'IHDR'.codeUnits);
    final header = ByteData.sublistView(png);
    header.setUint32(8, 13);
    header.setUint32(16, 768);
    header.setUint32(20, 512);
    expect(tutorPhotoDimensions(png), (width: 768, height: 512));
    header.setUint32(16, 8192);
    header.setUint32(20, 8192);
    expect(() => tutorPhotoDimensions(png), throwsFormatException);
    expect(
      () =>
          tutorPhotoDimensions(Uint8List.fromList([255, 216, 255, 224, 0, 0])),
      throwsFormatException,
    );
    expect(
      tutorPhotoDimensions(
        Uint8List.fromList([
          255,
          216,
          255,
          192,
          0,
          11,
          8,
          0,
          20,
          0,
          10,
          1,
          1,
          17,
          0,
          255,
          217,
        ]),
      ),
      (width: 10, height: 20),
    );
  });
  testWidgets(
    'explicit crop approval and unknown upload recover one prepared receipt',
    (tester) async {
      late final Uint8List bytes;
      await tester.runAsync(() async {
        final recorder = ui.PictureRecorder();
        Canvas(recorder).drawRect(
          const Rect.fromLTWH(0, 0, 20, 20),
          Paint()..color = Colors.blue,
        );
        final picture = recorder.endRecording();
        final image = await picture.toImage(20, 20);
        bytes = (await image.toByteData(
          format: ui.ImageByteFormat.png,
        ))!.buffer.asUint8List();
        image.dispose();
        picture.dispose();
      });
      final repo = FakeTutorRepository()..loseUpload = true;
      await tester.pumpWidget(
        MaterialApp(
          home: TutorScreen(
            repository: repo,
            initialSession: tutorSession,
            selectPhoto: () async => bytes,
          ),
        ),
      );
      await tester.pumpAndSettle();
      await tester.ensureVisible(find.text('Choose a working photo'));
      await tester.tap(find.text('Choose a working photo'));
      await tester.pump();
      await tester.runAsync(
        () => Future<void>.delayed(const Duration(milliseconds: 30)),
      );
      await tester.pumpAndSettle();
      expect(
        tester
            .widget<FilledButton>(
              find.widgetWithText(FilledButton, 'Use this working crop'),
            )
            .onPressed,
        isNull,
      );
      final area = find.byKey(const Key('tutor-crop-area'));
      await tester.ensureVisible(area);
      await tester.pumpAndSettle();
      final rectangle = tester.getRect(area);
      await tester.dragFrom(
        rectangle.topLeft + const Offset(10, 10),
        Offset(rectangle.width * .7, rectangle.height * .7),
      );
      await tester.pumpAndSettle();
      await tapTutor(
        tester,
        'I checked this selection contains only my working.',
      );
      await tapTutor(tester, 'Use this working crop');
      expect(repo.uploads, 1);
      await tapTutor(tester, 'Check reply');
      expect(repo.uploads, 1);
      expect(
        find.text('Your approved working crop is ready for this turn.'),
        findsOneWidget,
      );
      expect(find.text('Retry this working upload'), findsNothing);
    },
  );
  for (final width in [390.0, 820.0, 1280.0]) {
    testWidgets('Student hints and next question fit width $width', (
      tester,
    ) async {
      tester.view.physicalSize = Size(width, 1000);
      tester.view.devicePixelRatio = 1;
      addTearDown(tester.view.resetPhysicalSize);
      addTearDown(tester.view.resetDevicePixelRatio);
      final repo = FakeTutorRepository();
      await mountTutor(tester, repo);
      await question(tester);
      await tapTutor(tester, 'Ask for a hint');
      expect(repo.writes, hasLength(1));
      expect(find.text('Compare the equal parts.'), findsOneWidget);
      expect(find.text('Which part did you shade?'), findsOneWidget);
      expect(tester.takeException(), isNull);
    });
  }
  testWidgets(
    'uncertain send retries exact request; background recovers owner draft without local body',
    (tester) async {
      final repo = FakeTutorRepository()..loseSend = true;
      await mountTutor(tester, repo);
      await question(tester);
      await tapTutor(tester, 'Ask for a hint');
      final first = repo.writes.single;
      expect(find.text('Retry the same turn'), findsOneWidget);
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
      await tester.pump();
      expect(find.text('How do fractions work?'), findsNothing);
      expect(find.byType(TextField), findsNothing);
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await tester.pumpAndSettle();
      repo.loseSend = false;
      await tapTutor(tester, 'Retry the same turn');
      expect(repo.writes.last.operation, first.operation);
      expect(repo.writes.last.body, first.body);
      expect(find.text('Retry the same turn'), findsNothing);
    },
  );
  testWidgets(
    'prepared crop can be removed to release bounded server capacity',
    (tester) async {
      final repo = FakeTutorRepository()..photos = [tutorReceiptJson()];
      await mountTutor(tester, repo);
      await tapTutor(tester, 'Remove unused working crop');
      expect(repo.revokes, 1);
      expect(repo.photos, isEmpty);
    },
  );
  testWidgets('read-only year leaves conversation visible and cannot send', (
    tester,
  ) async {
    final repo = FakeTutorRepository()..turns = [tutorTurnJson()];
    await mountTutor(tester, repo, write: () => false);
    expect(find.text('Compare the equal parts.'), findsOneWidget);
    await tester.scrollUntilVisible(
      find.text('Ask for a hint'),
      200,
      scrollable: find.byType(Scrollable).first,
    );
    final button = tester.widget<FilledButton>(
      find.widgetWithText(FilledButton, 'Ask for a hint'),
    );
    expect(button.onPressed, isNull);
    expect(repo.writes, isEmpty);
  });
  testWidgets(
    'a late old conversation cannot paint after background and revocation',
    (tester) async {
      final repo = FakeTutorRepository()
        ..delayedRead = Completer<TutorSessionDetail>();
      await tester.pumpWidget(
        MaterialApp(
          home: TutorScreen(repository: repo, initialSession: tutorSession),
        ),
      );
      await tester.pump();
      await tester.pump();
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
      await tester.pump();
      repo.active = false;
      repo.turns = [tutorTurnJson()];
      repo.delayedRead!.complete(repo.detail());
      await tester.pumpAndSettle();
      expect(find.text('Compare the equal parts.'), findsNothing);
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await tester.pump();
      expect(find.text('Compare the equal parts.'), findsNothing);
    },
  );
  testWidgets('Parent sees only curricular aggregate, erased on background', (
    tester,
  ) async {
    final repo = _ParentSummary();
    await tester.pumpWidget(
      MaterialApp(home: TutorSummaryScreen(repository: repo, learnerId: 9)),
    );
    await tester.pumpAndSettle();
    await tapTutor(tester, 'Mathematics');
    expect(find.text('Completed learning steps'), findsOneWidget);
    expect(find.text('3'), findsOneWidget);
    expect(find.byType(TextField), findsNothing);
    expect(repo.reads, 1);
    tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
    await tester.pump();
    expect(find.text('3'), findsNothing);
    repo.active = false;
    tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
    await tester.pump();
    expect(find.text('3'), findsNothing);
  });
  test('school pause and billing changes have explicit friendly messages', () {
    for (final reason in [
      'school_inference_paused',
      'budget_currency_changed',
    ]) {
      expect(tutorFailureText(reason), contains('administrator'));
    }
  });
}
