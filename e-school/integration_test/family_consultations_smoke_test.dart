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
      '${parent ? 'parent' : 'learner'} casts one anonymous account vote and erases revoked ballot',
      (tester) async {
        final account = parent ? 21 : 11;
        final fixture = ConsultationSmokeFixture(accountId: account);
        final api = await startSmokeApp(tester, {
          ...(parent ? parentOfTwoRoutes() : learnerRoutes()),
          ...fixture.routes,
          'GET ${parent ? 'parent' : 'student'}/get-notification': ok({
            'data': [
              {
                'id': 71,
                'title': 'School consultation available',
                'type': 'consultations.opened',
                'message':
                    'Open school consultations to review an available ballot.',
                'date': '2026-10-25T10:00:00Z',
                'send_to': parent ? 4 : 3,
                'image': null,
              },
            ],
            'current_page': 1,
            'last_page': 1,
            'total': 1,
          }),
          'GET v2/me/context': ok({
            ...academicContextFixture(role: parent ? 'Parent' : 'Student'),
            'user': {
              'id': account,
              'first_name': 'Synthetic',
              'last_name': 'Mokoena',
              'name': 'Synthetic Mokoena',
              'email': parent ? parentEmail : 'learner@example.test',
              'image': null,
              'roles': [parent ? 'Parent' : 'Student'],
            },
            'feature_flags': {'module_switches': true},
            'modules': [
              for (final key in [
                'school',
                'people',
                'academics',
                'communication',
                'consultations',
                'web_parent',
                'web_learner',
              ])
                {'key': key, 'read_only': false},
            ],
          }),
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
          await tapWhenReady(tester, find.byIcon(Icons.settings));
          await tapWhenReady(tester, find.text('Notifications'));
        } else {
          await tapWhenReady(tester, find.byIcon(Icons.notifications));
        }
        await pumpUntilFound(
          tester,
          find.text('School consultation available'),
        );
        await tapWhenReady(
          tester,
          find.byKey(const ValueKey('consultation-notice-71')),
        );
        await pumpUntilFound(
          tester,
          find.text('Synthetic school consultation'),
        );
        await tapWhenReady(tester, find.text('Synthetic school consultation'));
        await pumpUntilFound(
          tester,
          find.text('Synthetic private consultation instructions.'),
        );
        await tapWhenReady(
          tester,
          find.byKey(const ValueKey('consultation-option-11')),
        );
        final answer = find.byKey(const ValueKey('consultation-answer-4'));
        await tester.ensureVisible(answer);
        await tester.pumpAndSettle();
        await tester.enterText(answer, 'Synthetic private family answer');
        await tapWhenReady(tester, find.text('Record my response'));
        await pumpUntilFound(
          tester,
          find.text(
            'Your response is recorded. You can participate only once.',
          ),
        );
        expect(fixture.votes, 1);
        expect(fixture.lastVote!.keys, ['answers']);
        expect(
          find.text('Synthetic private family answer', skipOffstage: false),
          findsNothing,
        );
        fixture.revoked = true;
        tester.binding.handleAppLifecycleStateChanged(
          AppLifecycleState.inactive,
        );
        await tester.pump();
        expect(
          find.text('Synthetic private consultation instructions.'),
          findsNothing,
        );
        tester.binding.handleAppLifecycleStateChanged(
          AppLifecycleState.resumed,
        );
        await pumpUntilFound(
          tester,
          find.text('Your consultation access changed. Reopen this screen.'),
        );
        final requests = api.requests.where(
          (r) => r.path.contains('/consultations'),
        );
        expect(requests, isNotEmpty);
        expect(
          requests.every(
            (r) =>
                r.headers['X-Account-Id'] == '$account' &&
                r.headers['X-School-Id'] == '1',
          ),
          isTrue,
        );
        expect(tester.takeException(), isNull);
        await tester.pumpWidget(const SizedBox.shrink());
      },
    );
  }
}
