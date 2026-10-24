import 'dart:async';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_academics/testing.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'mark_widgets_test.dart' show pumpMarks;
import 'marks_test.dart' show FakeMarks;
import 'timetable_test.dart';

void main() {
  for (final size in [
    const Size(390, 844),
    const Size(1024, 900),
    const Size(1280, 900),
  ]) {
    testWidgets(
      'family week fits ${size.width.toInt()} without staff controls',
      (tester) async {
        final repository = FakeTimetable()
          ..current = AcademicTimetableWeekData.fromJson(
            timetableWeekFixture(),
          );
        await pumpMarks(
          tester,
          FakeMarks(),
          size,
          screen: TimetableScreen.withRepository(
            repository,
            learner: 11,
            title: 'Thandi · Timetable',
          ),
        );
        expect(find.text('Room 101'), findsOneWidget);
        expect(find.text('Sipho Nkosi'), findsOneWidget);
        expect(find.text('Place lesson'), findsNothing);
        expect(find.text('Edit lesson'), findsNothing);
        expect(repository.configurations, 0);
        expect(tester.takeException(), isNull);
      },
    );
  }
  testWidgets('the editor shows a room clash before enabling save', (
    tester,
  ) async {
    final repository = FakeTimetable()..clashes = true;
    await pumpMarks(
      tester,
      FakeMarks(),
      const Size(390, 844),
      screen: TimetableScreen.withRepository(repository, title: 'Timetable'),
    );
    await tester.tap(find.text('Edit lesson'));
    await tester.pumpAndSettle();
    final save = find.widgetWithText(FilledButton, 'Save timetable');
    expect(tester.widget<FilledButton>(save).onPressed, isNull);
    await tester.tap(find.text('Preview placement'));
    await tester.pumpAndSettle();
    expect(find.text('Resolve these clashes before saving.'), findsOneWidget);
    expect(tester.widget<FilledButton>(save).onPressed, isNull);
    expect(repository.commits, isEmpty);
    repository.clashes = false;
    await tester.tap(find.text('Preview placement'));
    await tester.pumpAndSettle();
    expect(tester.widget<FilledButton>(save).onPressed, isNotNull);
    await tester.tap(save);
    await tester.pumpAndSettle();
    expect(repository.commits.single.fingerprint, 'a' * 64);
    expect(find.text('Timetable saved'), findsOneWidget);
    expect(tester.takeException(), isNull);
  });
  testWidgets('large text keeps week cards usable', (tester) async {
    final repository = FakeTimetable()
      ..current = AcademicTimetableWeekData.fromJson(timetableWeekFixture());
    await pumpMarks(
      tester,
      FakeMarks(),
      const Size(1280, 1000),
      scale: 2,
      screen: TimetableScreen.withRepository(
        repository,
        learner: 11,
        title: 'Timetable',
      ),
    );
    expect(find.text('Room 101'), findsOneWidget);
    expect(tester.takeException(), isNull);
  });
  testWidgets(
    'a late next-period response cannot show the previous childs room',
    (tester) async {
      final repository = FakeTimetable();
      final old = repository.nextResponse =
          Completer<AcademicTimetableNextData>();
      final child = ValueNotifier<int>(11);
      addTearDown(child.dispose);
      await pumpMarks(
        tester,
        FakeMarks(),
        const Size(390, 844),
        settle: false,
        screen: ValueListenableBuilder<int>(
          valueListenable: child,
          builder: (_, id, _) => Scaffold(
            body: NextPeriodCard(
              repository: repository,
              scope: 'school7:$id',
              learner: id,
            ),
          ),
        ),
      );
      await tester.pump();
      final latest = repository.nextResponse =
          Completer<AcademicTimetableNextData>();
      child.value = 12;
      await tester.pump();
      latest.complete(
        AcademicTimetableNextData.fromJson({
          'lesson': timetableLessonFixture(room: 'Room 202'),
        }),
      );
      await tester.pumpAndSettle();
      expect(find.text('Room 202 · Main'), findsOneWidget);
      old.complete(
        AcademicTimetableNextData.fromJson({
          'lesson': timetableLessonFixture(),
        }),
      );
      await tester.pumpAndSettle();
      expect(find.text('Room 101 · Main'), findsNothing);
      await tester.pumpWidget(
        const SizedBox(),
      ); // Dispose the home refresh timer.
      expect(tester.takeException(), isNull);
    },
  );
}
