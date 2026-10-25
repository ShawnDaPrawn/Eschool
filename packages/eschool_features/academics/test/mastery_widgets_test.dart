import 'package:eschool_academics/eschool_academics.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'mastery_test_repository.dart';

Future<void> frame(WidgetTester tester, Widget child, double width) async {
  tester.view.physicalSize = Size(width, 900);
  tester.view.devicePixelRatio = 1;
  addTearDown(tester.view.resetPhysicalSize);
  addTearDown(tester.view.resetDevicePixelRatio);
  await tester.pumpWidget(
    MaterialApp(theme: ThemeData(useMaterial3: true), home: child),
  );
  await tester.pumpAndSettle();
}

void main() {
  for (final width in [390.0, 820.0, 1280.0]) {
    for (final staff in [false, true]) {
      testWidgets(
        '${staff ? 'staff' : 'family'} root paths and evidence fit at $width',
        (tester) async {
          await frame(
            tester,
            MasteryScreen(
              repository: FakeMasteryRepository(),
              learner: staff ? null : 11,
            ),
            width,
          );
          expect(find.text('Fractions foundation'), findsOneWidget);
          expect(
            find.textContaining('Decimals current → Fractions foundation'),
            findsOneWidget,
          );
          expect(find.text('Grade 7'), findsOneWidget);
          expect(tester.takeException(), isNull);
          final tab = find.text('Topic evidence').first;
          await tester.tap(tab);
          await tester.pumpAndSettle();
          expect(find.textContaining('Level 0 / 4'), findsOneWidget);
          expect(find.textContaining('Not yet assessed'), findsOneWidget);
          expect(find.text('Recent questions: 0'), findsOneWidget);
          expect(find.text('Recent questions: 1'), findsOneWidget);
          expect(tester.takeException(), isNull);
        },
      );
    }
  }
  testWidgets(
    'hidden family evidence never appears as assessed zero or invented no-gap result',
    (tester) async {
      final data = MasteryView.fromJson(masteryFixture(visible: false));
      await frame(
        tester,
        Scaffold(
          body: SingleChildScrollView(
            child: MasteryOverview(view: data, showTopics: true),
          ),
        ),
        390,
      );
      expect(find.textContaining('Not shared by school'), findsNWidgets(2));
      expect(find.text('Evidence not shared'), findsNWidgets(2));
      expect(find.textContaining('Recent questions:'), findsNothing);
      expect(find.textContaining('Level 0'), findsNothing);
      expect(find.textContaining('No current gaps'), findsNothing);
      expect(tester.takeException(), isNull);
    },
  );
  testWidgets(
    'background hides private evidence and resumed revoked access clears it',
    (tester) async {
      final repo = FakeMasteryRepository();
      await frame(tester, MasteryScreen(repository: repo, learner: 11), 390);
      expect(find.text('Fractions foundation'), findsOneWidget);
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
      await tester.pump();
      expect(find.text('Fractions foundation'), findsNothing);
      repo.isActive = false;
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await tester.pumpAndSettle();
      expect(find.text('Fractions foundation'), findsNothing);
      expect(tester.takeException(), isNull);
    },
  );
  testWidgets(
    'replacing child repository never shows the old child while new view loads',
    (tester) async {
      final first = FakeMasteryRepository();
      await frame(tester, MasteryScreen(repository: first, learner: 11), 820);
      expect(find.text('Fractions foundation'), findsOneWidget);
      await tester.pumpWidget(
        MaterialApp(
          home: MasteryScreen(
            repository: FakeMasteryRepository()..visible = false,
            learner: 13,
          ),
        ),
      );
      await tester.pumpAndSettle();
      expect(find.text('Fractions foundation'), findsNothing);
      expect(
        find.text('Your school has not enabled mastery from released marks.'),
        findsOneWidget,
      );
      expect(tester.takeException(), isNull);
    },
  );
}
