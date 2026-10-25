import 'package:eschool/ui/screens/home/homeScreen.dart';
import 'package:eschool/ui/screens/parentHomeScreen.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';

import 'support/fixtures.dart';
import 'support/smoke.dart';

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  for (final parent in [false, true]) {
    testWidgets(
      '${parent ? 'Parent' : 'Student'} practice entry and owned study boundaries',
      (tester) async {
        final fixture = PracticeProtocolFixture(staff: false, reader: 11);
        fixture.offlineLease = const Duration(seconds: 15);
        final api = await startSmokeApp(tester, {
          ...(parent ? parentOfTwoRoutes() : learnerRoutes()),
          'GET v2/me/context': ok({
            ...academicContextFixture(role: parent ? 'Parent' : 'Student'),
            'user': {
              'id': parent ? 21 : 11,
              'first_name': 'Synthetic',
              'last_name': 'Learner',
              'name': 'Synthetic Learner',
              'email': parent ? parentEmail : 'learner@example.test',
              'image': null,
              'roles': [parent ? 'Parent' : 'Student'],
            },
            'approval': {'status': 'approved', 'approved_by_count': 1},
            'feature_flags': {'module_switches': true},
            'modules': [
              for (final module in [
                'school',
                'people',
                'academics',
                'daily_practice',
                'web_learner',
                'web_parent',
              ])
                {'key': module, 'read_only': false},
            ],
          }),
          ...fixture.routes(),
        });
        await tapWhenReady(
          tester,
          find.text(parent ? 'Login as Parent' : 'Login as Student'),
        );
        await pumpUntilFound(
          tester,
          find.text(
            parent ? 'Email or handle' : 'Student number, GR number or handle',
          ),
        );
        await enterField(tester, 0, parent ? parentEmail : grNumber);
        await enterField(tester, 1, password);
        await tapWhenReady(
          tester,
          find.widgetWithText(CustomRoundedButton, 'Sign in'),
        );
        await pumpUntilFound(
          tester,
          find.byType(parent ? ParentHomeScreen : HomeScreen),
        );
        if (parent) {
          expect(find.text('Daily practice'), findsNothing);
          expect(
            api.requests.where((r) => r.path.contains('/learning/practice/')),
            isEmpty,
          );
        } else {
          await tapWhenReady(tester, find.text('Daily practice'));
          await pumpUntilFound(tester, find.text('Mathematics · Grade 7'));
          await tapWhenReady(tester, find.text('Mathematics · Grade 7'));
          await pumpUntilFound(tester, find.text('One half as a decimal?'));
          expect(find.text('Divide one by two to get 0.5.'), findsNothing);
          await tapWhenReady(tester, find.text('Save for offline'));
          await pumpUntilFound(
            tester,
            find.text('Encrypted practice copy saved for offline use.'),
          );
          final field = find.byWidgetPredicate(
            (w) =>
                w is TextField &&
                w.decoration?.labelText == 'Your decimal answer',
          );
          await tester.enterText(field, '.5');
          await tapWhenReady(tester, find.text('Check answer'));
          await pumpUntilFound(tester, find.text('Correct'));
          expect(find.text('Divide one by two to get 0.5.'), findsOneWidget);
          expect(fixture.reviewed, isFalse);
          await tester.pump(const Duration(seconds: 16));
          await tester.pump();
          expect(find.text('One half as a decimal?'), findsNothing);
          await tapWhenReady(tester, find.text('Open saved practice'));
          await pumpUntilFound(tester, find.text('Sync saved answers (1)'));
          await tapWhenReady(tester, find.text('Sync saved answers (1)'));
          await pumpUntilFound(tester, find.text('Answers synchronized.'));
          expect(fixture.reviewed, isTrue);
          expect(
            api.requests
                .where((r) => r.path.contains('/learning/practice/'))
                .every(
                  (r) =>
                      r.headers['X-School-Id'] == '1' &&
                      r.headers['X-Account-Id'] == '11',
                ),
            isTrue,
          );
          fixture.granted = false;
          tester.binding.handleAppLifecycleStateChanged(
            AppLifecycleState.inactive,
          );
          await tester.pump();
          expect(find.text('One half as a decimal?'), findsNothing);
          tester.binding.handleAppLifecycleStateChanged(
            AppLifecycleState.resumed,
          );
          await pumpUntilFound(
            tester,
            find.text('Reopen practice to check your access.'),
          );
        }
        expect(tester.takeException(), isNull);
      },
    );
  }
}
