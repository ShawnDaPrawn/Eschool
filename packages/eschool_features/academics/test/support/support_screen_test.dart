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
  FocusManager.instance.primaryFocus?.unfocus();
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
  testWidgets('appointment choices survive different search and result pages', (
    tester,
  ) async {
    final repo = FakeSupport()
      ..audiences = ['principal']
      ..paginated = true;
    await show(tester, repo);
    await tester.tap(find.byType(DropdownButtonFormField<int>).first);
    await tester.pumpAndSettle();
    await tester.tap(find.text('Synthetic Learner').last);
    await tester.pumpAndSettle();
    await tester.enterText(
      field('Private appointment rationale'),
      'Reason for this learner and professional',
    );
    final next = find.widgetWithText(OutlinedButton, 'Next page');
    FocusManager.instance.primaryFocus?.unfocus();
    await tester.pumpAndSettle();
    await tester.scrollUntilVisible(
      next,
      500,
      scrollable: find.byType(Scrollable).first,
    );
    await tester.ensureVisible(next);
    await tester.pumpAndSettle();
    await tester.tap(next);
    await tester.pumpAndSettle();
    expect(find.text('Synthetic Learner'), findsWidgets);
    expect(
      tester
          .widget<TextField>(field('Private appointment rationale'))
          .controller!
          .text,
      'Reason for this learner and professional',
    );
    await tester.scrollUntilVisible(
      field('Find learner or staff member'),
      -500,
      scrollable: find.byType(Scrollable).first,
    );
    final person = find.byType(DropdownButtonFormField<int>).last;
    await tester.ensureVisible(person);
    await tester.pumpAndSettle();
    await tester.tap(person);
    await tester.pumpAndSettle();
    await tester.tap(find.text('Other professional').last);
    await tester.pumpAndSettle();
    await tester.enterText(
      field('Private appointment rationale'),
      'Reason for this learner and professional',
    );
    await tester.scrollUntilVisible(
      field('Find learner or staff member'),
      -500,
      scrollable: find.byType(Scrollable).first,
    );
    await tester.enterText(field('Find learner or staff member'), 'Other');
    FocusManager.instance.primaryFocus?.unfocus();
    await tester.pumpAndSettle();
    final search = find.byTooltip('Search');
    await tester.ensureVisible(search);
    await tester.pumpAndSettle();
    await tester.tap(search);
    await tester.pumpAndSettle();
    expect(find.text('Synthetic Learner'), findsWidgets);
    await tapButton(tester, 'Appoint professional');
    expect(repo.writes, hasLength(1));
    expect(repo.writes.single, contains('"learner_id":3'));
    expect(repo.writes.single, contains('"user_id":5'));
    expect(
      repo.writes.single,
      contains('Reason for this learner and professional'),
    );
  });
  testWidgets(
    'review schedule reconciles without overwriting a preserved plan edit',
    (tester) async {
      final repo = FakeSupport()..hasPlan = true;
      await show(tester, repo);
      await tester.tap(find.text('Synthetic Learner'));
      await tester.pumpAndSettle();
      await tester.enterText(field('Support needs'), 'Unsent case edit');
      final date = find.widgetWithText(
        OutlinedButton,
        'Next review date: Not set',
      );
      FocusManager.instance.primaryFocus?.unfocus();
      await tester.pumpAndSettle();
      await tester.ensureVisible(date);
      await tester.pumpAndSettle();
      await tester.tap(date);
      await tester.pumpAndSettle();
      await tester.tap(find.text('15'));
      await tester.tap(find.text('OK'));
      await tester.pumpAndSettle();
      await tester.ensureVisible(field('Review outcome'));
      await tester.enterText(field('Review outcome'), 'Actual human review');
      await tapButton(tester, 'Add review');
      expect(
        tester.widget<TextField>(field('Support needs')).controller!.text,
        'Unsent case edit',
      );
      expect(find.text('Review due: 2026-02-15'), findsOneWidget);
      await tapButton(tester, 'Save support plan');
      expect(repo.writes, hasLength(2));
      expect(repo.writes.last, contains('"revision":2'));
      expect(repo.writes.last, contains('"review_due_on":"2026-02-15"'));
    },
  );
}
