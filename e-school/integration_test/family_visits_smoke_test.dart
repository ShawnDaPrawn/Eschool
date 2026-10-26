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
      '${parent ? 'guardian' : 'learner'} has only authorised meetings and collection requests',
      (tester) async {
        final account = parent ? 21 : 11,
            forms = FamilyVisitSmokeFixture(accountId: account);
        final api = await startSmokeApp(tester, {
          ...(parent ? parentOfTwoRoutes() : learnerRoutes()),
          ...forms.routes,
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
                'family_visits',
                'web_parent',
                'web_learner',
              ])
                {'key': key, 'read_only': false},
            ],
            if (parent)
              'children': [
                {
                  'id': 11,
                  'user_id': 11,
                  'first_name': 'Synthetic',
                  'last_name': 'Learner',
                  'name': 'Synthetic Learner',
                  'approved': true,
                  'image': null,
                  'school': {
                    'id': 1,
                    'name': 'Smoke Test School',
                    'short_name': null,
                    'crest_url': null,
                  },
                },
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
        if (!parent) {
          expect(find.text('Meetings and early collection'), findsNothing);
          expect(
            api.requests.where((r) => r.uri.path.contains('/family-visits/')),
            isEmpty,
          );
          return;
        }
        await tapWhenReady(tester, find.text('Meetings and early collection'));
        await tapWhenReady(tester, find.text('Book this meeting'));
        await pumpUntilFound(tester, find.text('Booked'));
        await tapWhenReady(tester, find.text('Cancel my booking'));
        await pumpUntilFound(tester, find.text('Available'));
        await tapWhenReady(tester, find.text('Early collection'));
        await pumpUntilFound(
          tester,
          find.byKey(const ValueKey('visit-collector')),
        );
        await tester.enterText(
          find.byKey(const ValueKey('visit-collector')),
          'Synthetic private collector',
        );
        await tester.enterText(
          find.byKey(const ValueKey('visit-reason')),
          'Synthetic private collection reason',
        );
        await tapWhenReady(tester, find.text('Request staff approval'));
        await tapWhenReady(tester, find.text('Retry original request'));
        await pumpUntilFound(tester, find.text('Awaiting staff approval'));
        expect(forms.effects, 3);
        final requests = forms.writes
            .where((r) => r['kind'] == 'request')
            .toList();
        expect(requests, hasLength(2));
        expect(requests.first['body'], requests.last['body']);
        expect(find.text('Record collection'), findsNothing);
        await tapWhenReady(tester, find.text('Cancel collection request'));
        await pumpUntilFound(tester, find.text('Cancelled'));
        expect(forms.effects, 4);
        forms.allowed = false;
        tester.binding.handleAppLifecycleStateChanged(
          AppLifecycleState.inactive,
        );
        await tester.pump();
        tester.binding.handleAppLifecycleStateChanged(
          AppLifecycleState.resumed,
        );
        await pumpUntilFound(
          tester,
          find.text('Your meeting access changed. Reopen this screen.'),
        );
        expect(find.text('Synthetic private meeting room'), findsNothing);
        expect(find.text('Synthetic private collector'), findsNothing);
        final privateRequests = api.requests.where(
          (r) => r.uri.path.contains('/family-visits/'),
        );
        expect(privateRequests, isNotEmpty);
        expect(
          privateRequests.every(
            (r) =>
                r.headers['X-School-Id'] == '1' &&
                r.headers['X-Account-Id'] == '$account',
          ),
          isTrue,
        );
        expect(tester.takeException(), isNull);
      },
    );
  }
}
