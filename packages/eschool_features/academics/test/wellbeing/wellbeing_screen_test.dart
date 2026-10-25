import 'dart:convert';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'wellbeing_fake.dart';

Finder field(String name) => find.byWidgetPredicate(
  (w) => w is TextField && w.decoration?.labelText == name,
);
Future<void> seek(WidgetTester tester, Finder finder) async {
  tester.testTextInput.hide();
  FocusManager.instance.primaryFocus?.unfocus();
  await tester.pumpAndSettle();
  final scroll = find.byType(Scrollable).first;
  await tester.drag(scroll, const Offset(0, 100000));
  await tester.pumpAndSettle();
  for (int attempt = 0; finder.evaluate().isEmpty && attempt < 30; attempt++) {
    await tester.drag(scroll, const Offset(0, -300));
    await tester.pumpAndSettle();
  }
  await tester.ensureVisible(finder);
  await tester.pumpAndSettle();
}

Future<void> enter(WidgetTester tester, String label, String text) async {
  await seek(tester, field(label));
  await tester.enterText(field(label), text);
  await tester.pumpAndSettle();
}

Future<void> show(WidgetTester tester, FakeWellbeing repo) async {
  await tester.pumpWidget(MaterialApp(home: WellbeingScreen(repository: repo)));
  await tester.pumpAndSettle();
  await tester.tap(find.text('Synthetic Learner'));
  await tester.pumpAndSettle();
  await seek(tester, find.text('Private clinical baseline'));
}

Future<void> button(WidgetTester tester, String label) async {
  tester.testTextInput.hide();
  FocusManager.instance.primaryFocus?.unfocus();
  await tester.pumpAndSettle();
  final target = find.widgetWithText(FilledButton, label);
  await seek(tester, target);
  await tester.pumpAndSettle();
  await tester.tap(target);
  await tester.pumpAndSettle();
}

Future<void> choose<T>(WidgetTester tester, String label, String value) async {
  final target = find.byWidgetPredicate(
    (w) => w is DropdownButtonFormField<T> && w.decoration.labelText == label,
  );
  await seek(tester, target);
  await tester.tap(target);
  await tester.pumpAndSettle();
  await tester.tap(find.text(value).last);
  await tester.pumpAndSettle();
}

void main() {
  testWidgets('private inbox roundtrip preserves draft and rechecks case', (
    tester,
  ) async {
    final repo = FakeWellbeing();
    await show(tester, repo);
    await enter(tester, 'Private case note', 'Unsent private note');
    await tester.tap(find.byTooltip('Private updates'));
    await tester.pumpAndSettle();
    expect(find.text('Unsent private note'), findsNothing);
    await tester.tap(find.text('Return to private area'));
    await tester.pumpAndSettle();
    await seek(tester, field('Private case note'));
    expect(
      tester.widget<TextField>(field('Private case note')).controller!.text,
      'Unsent private note',
    );
  });
  testWidgets('inbox return refuses withdrawn clinical source', (tester) async {
    final repo = FakeWellbeing();
    await show(tester, repo);
    await tester.tap(find.byTooltip('Private updates'));
    await tester.pumpAndSettle();
    repo.deny = true;
    await tester.tap(find.text('Return to private area'));
    await tester.pumpAndSettle();
    expect(find.text('Private clinical baseline'), findsNothing);
    expect(find.text('Private updates'), findsNothing);
  });

  for (final width in [390.0, 820.0, 1280.0]) {
    testWidgets('assigned private case reads and fits $width', (tester) async {
      tester.view.physicalSize = Size(width, 1100);
      tester.view.devicePixelRatio = 1;
      addTearDown(tester.view.resetPhysicalSize);
      addTearDown(tester.view.resetDevicePixelRatio);
      final repo = FakeWellbeing();
      await show(tester, repo);
      expect(find.text('Private clinical baseline'), findsOneWidget);
      expect(repo.reads, contains('professionals:2026-02-10'));
      expect(tester.takeException(), isNull);
    });
  }
  testWidgets('unknown outcome retries the identical immutable intent', (
    tester,
  ) async {
    final repo = FakeWellbeing()..offlineOnce = true;
    await show(tester, repo);
    await enter(tester, 'Private case note', 'Private new note');
    await button(tester, 'Save private case entry');
    expect(repo.writes.length, 1);
    expect(
      (tester.widget<TextField>(field('Private case note'))).enabled,
      false,
    );
    await button(tester, 'Retry unchanged request');
    expect(repo.writes.length, 2);
    expect(repo.writes.first, repo.writes.last);
    await seek(tester, find.text('Private new note'));
    expect(find.text('Private new note'), findsOneWidget);
  });
  testWidgets('conflict needs current-record comparison and a new operation', (
    tester,
  ) async {
    final repo = FakeWellbeing()..conflictOnce = true;
    await show(tester, repo);
    await enter(tester, 'Private case note', 'My unsent private draft');
    await button(tester, 'Save private case entry');
    await seek(tester, find.text('Current private clinical record'));
    expect(find.text('Current private clinical record'), findsOneWidget);
    await seek(tester, field('Private case note'));
    expect(
      tester.widget<TextField>(field('Private case note')).controller!.text,
      'My unsent private draft',
    );
    expect(
      tester
          .widget<FilledButton>(
            find.widgetWithText(FilledButton, 'Save private case entry'),
          )
          .onPressed,
      isNull,
    );
    final comparison = find.widgetWithText(
      CheckboxListTile,
      'I compared my draft with the current record and want to save it',
    );
    await seek(tester, comparison);
    await tester.tap(comparison);
    await tester.pumpAndSettle();
    await button(tester, 'Save private case entry');
    final a = jsonDecode(repo.writes.first), b = jsonDecode(repo.writes.last);
    expect(a['operation_id'], isNot(b['operation_id']));
    expect(b['revision'], 2);
    expect(b['body'], 'My unsent private draft');
  });
  testWidgets('read only keeps private history and disables mutation', (
    tester,
  ) async {
    final repo = FakeWellbeing()..writable = false;
    await show(tester, repo);
    expect(find.text('Private clinical baseline'), findsOneWidget);
    await seek(tester, field('Private case note'));
    expect(tester.widget<TextField>(field('Private case note')).enabled, false);
    expect(repo.writes, isEmpty);
  });
  testWidgets('background erases body and draft before a refused resume', (
    tester,
  ) async {
    final repo = FakeWellbeing();
    await show(tester, repo);
    await enter(tester, 'Private case note', 'Private unsent draft');
    tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
    await tester.pump();
    expect(find.text('Private clinical baseline'), findsNothing);
    expect(find.text('Private unsent draft'), findsNothing);
    repo.deny = true;
    tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
    await tester.pumpAndSettle();
    expect(find.text('Private clinical baseline'), findsNothing);
    expect(repo.writes, isEmpty);
    expect(find.textContaining('Your clinical access changed'), findsOneWidget);
  });
  testWidgets(
    'school-time appointment can be booked and cancelled through actual form',
    (tester) async {
      final repo = FakeWellbeing();
      await show(tester, repo);
      await choose<int>(
        tester,
        'Appointment professional',
        'Synthetic counsellor',
      );
      await enter(tester, 'Appointment location', 'Private appointment room');
      await button(tester, 'Book private appointment');
      final booked = jsonDecode(repo.writes.last);
      expect(booked['starts_at'], '2026-02-10T08:00:00.000Z');
      expect(booked['ends_at'], '2026-02-10T08:30:00.000Z');
      await seek(tester, find.byTooltip('Edit private appointment'));
      await tester.tap(find.byTooltip('Edit private appointment'));
      await tester.pumpAndSettle();
      await choose<String>(tester, 'Appointment status', 'cancelled');
      await button(tester, 'Update private appointment');
      expect(jsonDecode(repo.writes.last)['status'], 'cancelled');
      expect(repo.appointmentRows.single.status.value, 'cancelled');
      expect(tester.takeException(), isNull);
    },
  );
  testWidgets(
    'triage defaults to internal and sends the actual report revision',
    (tester) async {
      final repo = FakeWellbeing();
      await tester.pumpWidget(
        MaterialApp(home: WellbeingScreen(repository: repo)),
      );
      await tester.pumpAndSettle();
      await tester.tap(find.text('Synthetic incident'));
      await tester.pumpAndSettle();
      await enter(tester, 'Triage note or reply', 'Private internal response');
      final internal = find.widgetWithText(
        CheckboxListTile,
        'Keep this note internal to the safeguarding team',
      );
      await seek(tester, internal);
      expect(tester.widget<CheckboxListTile>(internal).value, true);
      await button(tester, 'Save triage response');
      final sent = jsonDecode(repo.writes.single);
      expect(sent['internal'], true);
      expect(sent['body'], 'Private internal response');
      expect(sent['revision'], 1);
    },
  );
  testWidgets(
    'search refresh cannot silently advance a private draft revision',
    (tester) async {
      final repo = FakeWellbeing();
      await show(tester, repo);
      await enter(tester, 'Private case note', 'Unsent private note');
      repo.revision = 2;
      repo.body = 'Newly changed private record';
      await button(tester, 'Search assigned professionals');
      final compare = find.widgetWithText(
        CheckboxListTile,
        'I compared my draft with the current record and want to save it',
      );
      await seek(tester, compare);
      expect(compare, findsOneWidget);
      await seek(tester, field('Private case note'));
      expect(
        tester.widget<TextField>(field('Private case note')).controller!.text,
        'Unsent private note',
      );
      await seek(
        tester,
        find.widgetWithText(FilledButton, 'Save private case entry'),
      );
      expect(
        tester
            .widget<FilledButton>(
              find.widgetWithText(FilledButton, 'Save private case entry'),
            )
            .onPressed,
        isNull,
      );
      expect(repo.writes, isEmpty);
    },
  );
  testWidgets(
    'latest report refresh cannot silently advance an unsent triage reply',
    (tester) async {
      final repo = FakeWellbeing();
      await tester.pumpWidget(
        MaterialApp(home: WellbeingScreen(repository: repo)),
      );
      await tester.pumpAndSettle();
      await tester.tap(find.text('Synthetic incident'));
      await tester.pumpAndSettle();
      await enter(tester, 'Triage note or reply', 'Unsent private triage');
      repo.revision = 2;
      final latest = find.widgetWithText(TextButton, 'Show latest entries');
      await seek(tester, latest);
      await tester.tap(latest);
      await tester.pumpAndSettle();
      final compare = find.widgetWithText(
        CheckboxListTile,
        'I compared my draft with the current record and want to save it',
      );
      await seek(tester, compare);
      expect(compare, findsOneWidget);
      await seek(tester, field('Triage note or reply'));
      expect(
        tester
            .widget<TextField>(field('Triage note or reply'))
            .controller!
            .text,
        'Unsent private triage',
      );
      expect(repo.writes, isEmpty);
    },
  );
}
