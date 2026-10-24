import 'dart:convert';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_test/flutter_test.dart';
import 'marks_test.dart' as fixtures;

Future<void> pumpMarks(
  WidgetTester tester,
  fixtures.FakeMarks repository,
  Size size, {
  double scale = 1,
  Widget? screen,
  bool settle = true,
}) async {
  tester.view.physicalSize = size;
  tester.view.devicePixelRatio = 1;
  addTearDown(tester.view.reset);
  rootBundle.evict('assets/languages/en.json');
  final messenger = tester.binding.defaultBinaryMessenger;
  messenger.setMockMessageHandler('flutter/assets', (message) async {
    final key = utf8.decode(message!.buffer.asUint8List());
    if (key == 'assets/languages/en.json') {
      return ByteData.sublistView(
        utf8.encode('{"defaultErrorMessage":"Please try again"}'),
      );
    }
    if (key.endsWith('.svg')) {
      return ByteData.sublistView(
        utf8.encode(
          '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"><path d="M16 4L8 12l8 8"/></svg>',
        ),
      );
    }
    return messenger.delegate.send('flutter/assets', message);
  });
  addTearDown(() => messenger.setMockMessageHandler('flutter/assets', null));
  await tester.pumpWidget(
    MaterialApp(
      theme: AppTheme.light(),
      locale: const Locale('en'),
      supportedLocales: const [Locale('en')],
      localizationsDelegates: const [
        AppLocalizationDelegate([
          AppLanguage(languageCode: 'en', languageName: 'English'),
        ]),
      ],
      builder: (context, navigator) => AppShell(
        bottomNavigationHeightPercentage: 0.08,
        errorMessageKeyFromCode: (_) => 'defaultErrorMessage',
        openStudyMaterial: (_, _) {},
        studyMaterialButton: (_, _) => const SizedBox(),
        child: MediaQuery(
          data: MediaQuery.of(
            context,
          ).copyWith(textScaler: TextScaler.linear(scale)),
          child: navigator!,
        ),
      ),
      home:
          screen ??
          MarkCaptureScreen.withRepository(
            repository,
            group: 1,
            term: 1,
            title: 'Maths 7A · Term 1',
          ),
    ),
  );
  if (settle) {
    await tester.pumpAndSettle();
  } else {
    await tester.pump();
  }
}

void main() {
  for (final size in [
    const Size(390, 844),
    const Size(1024, 900),
    const Size(1280, 900),
  ]) {
    testWidgets(
      'mark capture fits ${size.width.toInt()} and preserves exact saved input',
      (tester) async {
        final repository = fixtures.FakeMarks();
        await pumpMarks(tester, repository, size);
        expect(find.text('Learner 11'), findsOneWidget);
        final fields = find.byType(TextField);
        expect(fields, findsNWidgets(4));
        await tester.enterText(fields.first, '5.01');
        await tester.pump();
        await tester.tap(find.widgetWithText(FilledButton, 'Save marks'));
        await tester.pumpAndSettle();
        expect(
          repository.writes.single.rows.single.questionMarks.first.mark,
          '5.01',
        );
        expect(find.text('Marks saved'), findsOneWidget);
        expect(tester.takeException(), isNull);
      },
    );
  }
  testWidgets(
    'desktop Tab traverses columns and Enter moves to the next learner',
    (tester) async {
      final repository = fixtures.FakeMarks();
      await pumpMarks(tester, repository, const Size(1280, 900));
      final fields = find.byType(TextField);
      await tester.tap(fields.first);
      await tester.pump();
      await tester.sendKeyEvent(LogicalKeyboardKey.tab);
      await tester.pump();
      expect(
        tester.widget<TextField>(fields.at(1)).focusNode!.hasFocus,
        isTrue,
      );
      await tester.testTextInput.receiveAction(TextInputAction.next);
      await tester.pump();
      expect(
        tester.widget<TextField>(fields.at(3)).focusNode!.hasFocus,
        isTrue,
      );
      expect(tester.takeException(), isNull);
    },
  );
  for (final size in [const Size(390, 844), const Size(1280, 900)]) {
    testWidgets('Enter traverses 35 virtual learners at ${size.width}', (
      tester,
    ) async {
      final repository = fixtures.FakeMarks()
        ..current = fixtures.markMatrix(learnerCount: 35);
      await pumpMarks(tester, repository, size);
      await tester.tap(find.byType(TextField).first);
      for (var index = 0; index < 34; index++) {
        await tester.testTextInput.receiveAction(TextInputAction.next);
        await tester.pumpAndSettle();
        final focused = tester
            .widgetList<TextField>(find.byType(TextField))
            .where((field) => field.focusNode?.hasFocus == true)
            .single;
        expect(
          focused.decoration!.labelText,
          contains('Learner ${index + 12} ·'),
        );
      }
      expect(tester.takeException(), isNull);
    });
  }
  testWidgets('locked and moderated rows cannot be edited through the screen', (
    tester,
  ) async {
    final repository = fixtures.FakeMarks()
      ..current = fixtures.markMatrix(locked: true);
    await pumpMarks(tester, repository, const Size(390, 844));
    expect(find.byType(TextField), findsNothing);
    expect(
      tester
          .widget<FilledButton>(find.widgetWithText(FilledButton, 'Save marks'))
          .onPressed,
      isNull,
    );
    expect(find.text('This term is locked for capture.'), findsOneWidget);
    expect(tester.takeException(), isNull);
  });
  testWidgets('large text remains usable without render overflow', (
    tester,
  ) async {
    await pumpMarks(
      tester,
      fixtures.FakeMarks(),
      const Size(1280, 1000),
      scale: 2,
    );
    expect(find.text('Learner 11'), findsOneWidget);
    expect(tester.takeException(), isNull);
  });
}
