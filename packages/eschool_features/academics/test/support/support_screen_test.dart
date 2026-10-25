import 'package:eschool_academics/eschool_academics.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'support_test_repository.dart';

Finder field(String label) => find.byWidgetPredicate(
  (w) => w is TextField && w.decoration?.labelText == label,
);
Future<void> show(WidgetTester tester, FakeSupport repo) async {
  await tester.pumpWidget(MaterialApp(home: SupportScreen(repository: repo)));
  await tester.pumpAndSettle();
}

Future<void> tapButton(WidgetTester tester, String label) async {
  tester.testTextInput.hide();
  await tester.pumpAndSettle();
  final button = find.widgetWithText(FilledButton, label);
  await tester.ensureVisible(button);
  await tester.pumpAndSettle();
  await tester.tap(button);
  await tester.pumpAndSettle();
}

void main() {
  for (final width in [390.0, 820.0, 1280.0]) {
    testWidgets(
      'teacher instructions use no clinical requests and fit $width',
      (tester) async {
        tester.view.physicalSize = Size(width, 1000);
        tester.view.devicePixelRatio = 1;
        addTearDown(tester.view.resetPhysicalSize);
        addTearDown(tester.view.resetDevicePixelRatio);
        final repo = FakeSupport()
          ..audiences = ['teacher']
          ..hasPlan = true;
        await show(tester, repo);
        expect(find.text('Approved quiet seating'), findsOneWidget);
        expect(find.text('Private clinical needs'), findsNothing);
        expect(find.text('SNA1'), findsNothing);
        expect(repo.reads, ['access', 'instructions']);
        expect(tester.takeException(), isNull);
      },
    );
  }
  testWidgets('principal appointments never fetch clinical case bodies', (
    tester,
  ) async {
    final repo = FakeSupport()..audiences = ['principal'];
    await show(tester, repo);
    expect(find.text('Named Counsellor'), findsWidgets);
    expect(repo.reads, ['access', 'configuration:principal', 'assignments']);
    expect(find.text('Private clinical needs'), findsNothing);
    expect(tester.takeException(), isNull);
  });
  testWidgets('read-only professional can read a plan but cannot change it', (
    tester,
  ) async {
    final repo = FakeSupport()
      ..writable = false
      ..hasPlan = true;
    await show(tester, repo);
    await tester.tap(find.text('Synthetic Learner'));
    await tester.pumpAndSettle();
    expect(find.text('Private clinical needs'), findsOneWidget);
    expect(
      find.widgetWithText(FilledButton, 'Save support plan'),
      findsNothing,
    );
    expect(repo.writes, isEmpty);
  });
  testWidgets(
    'unknown result locks the private draft and retries identical UUID payload',
    (tester) async {
      final repo = FakeSupport()..failWrite = true;
      await show(tester, repo);
      await tester.tap(find.byType(DropdownButtonFormField<int>));
      await tester.pumpAndSettle();
      await tester.tap(find.text('Synthetic Learner').last);
      await tester.pumpAndSettle();
      await tester.enterText(field('SIAS stage'), 'SNA1');
      await tester.enterText(field('Support needs'), 'Private unsent draft');
      await tester.enterText(
        field('Accommodations'),
        'Private draft accommodations',
      );
      await tapButton(tester, 'Save support plan');
      expect(repo.writes, hasLength(1));
      expect(tester.widget<TextField>(field('Support needs')).readOnly, isTrue);
      await tapButton(tester, 'Retry same change');
      expect(repo.writes, hasLength(2));
      expect(repo.writes[1], repo.writes[0]);
      expect(find.textContaining('result is not confirmed'), findsNothing);
    },
  );
  testWidgets(
    'resume hides a private draft and clears it after permission withdrawal',
    (tester) async {
      final repo = FakeSupport();
      await show(tester, repo);
      await tester.enterText(field('Support needs'), 'Private unsent draft');
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
      await tester.pump();
      expect(find.text('Private unsent draft'), findsNothing);
      repo.deny = true;
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await tester.pumpAndSettle();
      expect(find.byType(TextField), findsNothing);
      expect(find.textContaining('Your access changed'), findsOneWidget);
    },
  );
  testWidgets('adding a review retains separate unsaved plan edits', (
    tester,
  ) async {
    final repo = FakeSupport()..hasPlan = true;
    await show(tester, repo);
    await tester.tap(find.text('Synthetic Learner'));
    await tester.pumpAndSettle();
    await tester.enterText(field('Support needs'), 'Unsent case edit');
    await tester.ensureVisible(field('Review outcome'));
    await tester.enterText(field('Review outcome'), 'Actual human review');
    await tapButton(tester, 'Add review');
    expect(
      tester.widget<TextField>(field('Support needs')).controller!.text,
      'Unsent case edit',
    );
    expect(repo.writes, hasLength(1));
    expect(
      tester.widget<TextField>(field('Review outcome')).controller!.text,
      isEmpty,
    );
  });
}
