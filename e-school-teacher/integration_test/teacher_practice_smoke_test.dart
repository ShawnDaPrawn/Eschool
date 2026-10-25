import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_teacher/ui/screens/home/homeScreen.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter/material.dart';
import 'package:integration_test/integration_test.dart';

import 'support/fixtures.dart';
import 'support/smoke.dart';

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets(
    'teacher approves a question revision and erases practice after source withdrawal',
    (tester) async {
      final fixture = PracticeProtocolFixture();
      final api = await startSmokeApp(tester, {
        ...teacherRoutes(),
        'GET v2/me/context': ok({
          ...academicContextFixture(),
          'user': {
            'id': 12,
            'first_name': 'Teacher',
            'last_name': 'Demo',
            'name': 'Teacher Demo',
            'email': teacherEmail,
            'image': null,
            'roles': ['Teacher'],
          },
          'feature_flags': {'module_switches': true, 'mastery': true},
          'modules': [
            for (final key in [
              'school',
              'people',
              'academics',
              'mastery',
              'daily_practice',
              'web_teacher',
            ])
              {'key': key, 'read_only': false},
          ],
        }),
        ...fixture.routes(),
      });
      await pumpUntilFound(tester, find.text('Email or handle'));
      await enterField(tester, 0, teacherEmail);
      await enterField(tester, 1, password);
      await tapWhenReady(
        tester,
        find.widgetWithText(CustomRoundedButton, 'Sign in'),
      );
      await pumpUntilFound(tester, find.byType(HomeScreen));
      await tapWhenReady(tester, find.text('Practice question bank'));
      await tapWhenReady(tester, find.text('One half as a decimal?'));
      await pumpUntilFound(tester, find.text('Published topic'));
      final explanation = find.byWidgetPredicate(
        (w) =>
            w is TextField && w.decoration?.labelText == 'Worked explanation',
      );
      await tester.scrollUntilVisible(
        explanation,
        200,
        scrollable: find.byType(Scrollable).first,
      );
      await tester.enterText(
        explanation,
        'A teacher-reviewed worked explanation.',
      );
      await tester.scrollUntilVisible(
        find.text('Approve question revision'),
        200,
        scrollable: find.byType(Scrollable).first,
      );
      await tapWhenReady(tester, find.text('Approve question revision'));
      await pumpUntilFound(tester, find.text('Question revision approved.'));
      expect(fixture.lastApproval?['expected_revision'], 1);
      expect(
        (fixture.lastApproval?['definition'] as Map)['explanation'],
        'A teacher-reviewed worked explanation.',
      );
      fixture.granted = false;
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
      await tester.pump();
      expect(find.text('One half as a decimal?'), findsNothing);
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await pumpUntilFound(
        tester,
        find.text('Reopen practice to check your access.'),
      );
      final requests = api.requests
          .where((r) => r.uri.path.contains('/learning/practice/'))
          .toList();
      expect(requests, isNotEmpty);
      expect(
        requests.every(
          (r) =>
              r.headers['X-School-Id'] == '1' &&
              r.headers['X-Account-Id'] == '12',
        ),
        isTrue,
      );
      expect(requests.any((r) => r.uri.path.contains('/subjects/')), isFalse);
      final writes = requests.where((r) => r.method != 'GET').toList();
      expect(writes.length, 1);
      expect(writes.single.method, 'POST');
      expect(writes.single.uri.path.endsWith('/groups/7/bank/21'), isTrue);
      expect(tester.takeException(), isNull);
    },
  );
}
