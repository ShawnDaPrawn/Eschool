import 'dart:convert';
import 'dart:typed_data';
import 'dart:async';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'reporting_fake.dart';

Finder field(String label) => find.byWidgetPredicate(
  (w) => w is TextField && w.decoration?.labelText == label,
);
Future<void> seek(WidgetTester tester, Finder target) async {
  tester.testTextInput.hide();
  FocusManager.instance.primaryFocus?.unfocus();
  await tester.pumpAndSettle();
  final scroll = find.byType(Scrollable).first;
  await tester.drag(scroll, const Offset(0, 100000));
  await tester.pumpAndSettle();
  for (int i = 0; target.evaluate().isEmpty && i < 30; i++) {
    await tester.drag(scroll, const Offset(0, -300));
    await tester.pumpAndSettle();
  }
  await tester.ensureVisible(target);
  await tester.pumpAndSettle();
}

Future<void> enter(WidgetTester tester, String label, String value) async {
  await seek(tester, field(label));
  await tester.enterText(field(label), value);
  await tester.pumpAndSettle();
}

Future<void> tap(WidgetTester tester, String text) async {
  final button = find.widgetWithText(FilledButton, text);
  await seek(tester, button);
  await tester.tap(button);
  await tester.pumpAndSettle();
}

Future<void> consent(WidgetTester tester, String label) async {
  final check = find.widgetWithText(CheckboxListTile, label);
  await seek(tester, check);
  await tester.tap(check);
  await tester.pumpAndSettle();
}

Future<void> show(
  WidgetTester tester,
  FakeReporting repo, {
  Future<bool> Function(Uri)? opener,
  Future<Uint8List?> Function()? selector,
}) async {
  await tester.pumpWidget(
    MaterialApp(
      home: IncidentReportingScreen(
        repository: repo,
        reporterName: 'Synthetic Parent',
        learnerId: 3,
        learnerName: 'Synthetic Child',
        openAnonymous: opener,
        selectPhoto: selector,
      ),
    ),
  );
  await tester.pumpAndSettle();
}

Future<void> chooseCategory(WidgetTester tester) async {
  final choice = find.byType(DropdownButtonFormField<int>);
  await seek(tester, choice);
  await tester.tap(choice);
  await tester.pumpAndSettle();
  await tester.tap(find.text('Safety concern').last);
  await tester.pumpAndSettle();
}

void main() {
  for (final width in [390.0, 820.0, 1280.0]) {
    testWidgets('emergency guidance and both reporting choices fit $width', (
      tester,
    ) async {
      tester.view.physicalSize = Size(width, 1000);
      tester.view.devicePixelRatio = 1;
      addTearDown(tester.view.resetPhysicalSize);
      addTearDown(tester.view.resetDevicePixelRatio);
      final repo = FakeReporting();
      await show(tester, repo);
      expect(
        find.text('Speak to a trusted adult now if unsafe.'),
        findsOneWidget,
      );
      expect(find.text('Report anonymously'), findsOneWidget);
      expect(tester.takeException(), isNull);
    });
  }
  testWidgets('anonymous navigation sends no identity child or report code', (
    tester,
  ) async {
    final repo = FakeReporting();
    Uri? opened;
    await show(
      tester,
      repo,
      opener: (uri) async {
        opened = uri;
        return true;
      },
    );
    await tap(tester, 'Open anonymous reporting');
    expect(opened.toString(), 'https://school.example/school/synthetic/report');
    expect(opened!.query, isEmpty);
    expect(repo.submissions, isEmpty);
  });
  testWidgets(
    'new identified report needs explicit consent and child choice is optional',
    (tester) async {
      final repo = FakeReporting();
      await show(tester, repo);
      await chooseCategory(tester);
      await enter(tester, 'What happened?', 'Private named report');
      final send = find.widgetWithText(FilledButton, 'Send identified report');
      await seek(tester, send);
      expect(tester.widget<FilledButton>(send).onPressed, isNull);
      await consent(
        tester,
        'I agree to identify myself to the school safeguarding team for this report.',
      );
      await tap(tester, 'Send identified report');
      final input = jsonDecode(repo.submissions.single);
      expect(input['anonymous'], false);
      expect(input['identify_consent'], true);
      expect(input.containsKey('learner_id'), false);
      expect(input['secret'], matches(RegExp(r'^[a-f0-9]{64}$')));
      expect(repo.submissionCount, 1);
      expect(repo.anonymous, false);
    },
  );
  testWidgets(
    'unknown submission outcome retries exactly the original secret and body',
    (tester) async {
      final repo = FakeReporting()..offlineOnce = true;
      await show(tester, repo);
      await chooseCategory(tester);
      await enter(tester, 'What happened?', 'Private unknown report');
      await consent(tester, 'This concern is about: Synthetic Child');
      await consent(
        tester,
        'I agree to identify myself to the school safeguarding team for this report.',
      );
      await tap(tester, 'Send identified report');
      await tap(tester, 'Retry unchanged request');
      expect(repo.submissions.length, 2);
      expect(repo.submissions.first, repo.submissions.last);
      expect(repo.submissionCount, 1);
      expect(jsonDecode(repo.submissions.last)['learner_id'], 3);
    },
  );
  testWidgets(
    'read-only account reads existing report without offering new writes',
    (tester) async {
      final repo = FakeReporting()..writable = false;
      await show(tester, repo);
      await enter(tester, 'Private report code', repo.code);
      await tap(tester, 'Read my report');
      await seek(tester, find.text('Private reporter concern'));
      expect(find.text('Private reporter concern'), findsOneWidget);
      await seek(tester, find.widgetWithText(FilledButton, 'Send follow-up'));
      expect(
        tester
            .widget<FilledButton>(
              find.widgetWithText(FilledButton, 'Send follow-up'),
            )
            .onPressed,
        isNull,
      );
      expect(repo.submissions, isEmpty);
    },
  );
  testWidgets(
    'identification of an existing anonymous report needs its own consent',
    (tester) async {
      final repo = FakeReporting();
      await show(tester, repo);
      await enter(tester, 'Private report code', repo.code);
      await tap(tester, 'Read my report');
      final identify = find.widgetWithText(
        FilledButton,
        'Identify this report',
      );
      await seek(tester, identify);
      expect(tester.widget<FilledButton>(identify).onPressed, isNull);
      await consent(
        tester,
        'I agree to identify myself for this existing report.',
      );
      await tap(tester, 'Identify this report');
      expect(repo.identifications.length, 1);
      expect(repo.identifications.single['learner'], isNull);
      expect(repo.anonymous, false);
    },
  );
  testWidgets('background clears codes and bodies before denied resume', (
    tester,
  ) async {
    final repo = FakeReporting();
    await show(tester, repo);
    await enter(tester, 'Private report code', repo.code);
    await tap(tester, 'Read my report');
    await seek(tester, find.text('Private reporter concern'));
    tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
    await tester.pump();
    expect(find.text('Private reporter concern'), findsNothing);
    repo.deny = true;
    tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
    await tester.pumpAndSettle();
    expect(find.text('Private reporter concern'), findsNothing);
    expect(find.text(repo.code), findsNothing);
  });
  testWidgets(
    'photo unknown outcome retries identical bytes and operation without a second selection',
    (tester) async {
      final repo = FakeReporting()
        ..anonymous = false
        ..photoOfflineOnce = true;
      final bytes = base64Decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR4nGNQcGj4DwADRAHgMqq/hAAAAABJRU5ErkJggg==',
      );
      await show(tester, repo, selector: () async => bytes);
      await enter(tester, 'Private report code', repo.code);
      await tap(tester, 'Read my report');
      await tap(tester, 'Choose report photo');
      await tap(tester, 'Send selected photo');
      expect(repo.photoRequests.length, 1);
      await tap(tester, 'Retry unchanged request');
      expect(repo.photoRequests.length, 2);
      expect(repo.photoRequests[0], repo.photoRequests[1]);
      expect(repo.photos.length, 1);
      expect(tester.takeException(), isNull);
    },
  );
  testWidgets(
    'external picker background erases previous code and requires explicit fresh report selection',
    (tester) async {
      final repo = FakeReporting()..anonymous = false;
      final choice = Completer<Uint8List?>();
      await show(tester, repo, selector: () => choice.future);
      await enter(tester, 'Private report code', repo.code);
      await tap(tester, 'Read my report');
      await tap(tester, 'Choose report photo');
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
      await tester.pump();
      expect(find.text('Private reporter concern'), findsNothing);
      expect(find.text(repo.code), findsNothing);
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await tester.pumpAndSettle();
      choice.complete(Uint8List.fromList([1, 2, 3]));
      await tester.pumpAndSettle();
      expect(repo.photoRequests, isEmpty);
      expect(
        find.widgetWithText(FilledButton, 'Send selected photo'),
        findsNothing,
      );
      await enter(tester, 'Private report code', repo.code);
      await tap(tester, 'Read my report');
      await seek(
        tester,
        find.widgetWithText(FilledButton, 'Send selected photo'),
      );
      expect(repo.photoRequests, isEmpty);
    },
  );
  testWidgets(
    'revoked authority while picking drops selected bytes and private body',
    (tester) async {
      final repo = FakeReporting()..anonymous = false;
      final choice = Completer<Uint8List?>();
      await show(tester, repo, selector: () => choice.future);
      await enter(tester, 'Private report code', repo.code);
      await tap(tester, 'Read my report');
      await tap(tester, 'Choose report photo');
      repo.deny = true;
      choice.complete(Uint8List.fromList([1, 2, 3]));
      await tester.pumpAndSettle();
      expect(find.text('Private reporter concern'), findsNothing);
      expect(find.text(repo.code), findsNothing);
      expect(repo.photoRequests, isEmpty);
    },
  );
}
