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
      '${parent ? 'guardian' : 'learner'} reads only own supervised tutoring and service history',
      (tester) async {
        final account = parent ? 21 : 11,
            planner =
                PeerSmokeFixture(
                    reader: account,
                    persona: parent ? 'parent' : 'student',
                  )
                  ..status = 'completed'
                  ..minutes = 35;
        final api = await startSmokeApp(tester, {
          ...(parent ? parentOfTwoRoutes() : learnerRoutes()),
          ...planner.routes,
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
                'peer_tutoring',
                'mastery',
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
        await tapWhenReady(tester, find.text('Peer tutoring'));
        await pumpUntilFound(tester, find.text('Verified service minutes: 35'));
        expect(find.text('Synthetic supervised room'), findsOneWidget);
        expect(find.text('Schedule peer session'), findsNothing);
        expect(find.text('Confirm supervised service'), findsNothing);
        planner.allowed = false;
        tester.binding.handleAppLifecycleStateChanged(
          AppLifecycleState.inactive,
        );
        await tester.pump();
        tester.binding.handleAppLifecycleStateChanged(
          AppLifecycleState.resumed,
        );
        await pumpUntilFound(
          tester,
          find.text('Your peer tutoring access changed. Reopen this screen.'),
        );
        expect(find.text('Synthetic supervised room'), findsNothing);
        expect(find.text('Verified service minutes: 35'), findsNothing);
        final requests = api.requests.where(
          (r) => r.uri.path.contains('/peer-tutoring/'),
        );
        expect(requests, isNotEmpty);
        expect(
          requests.every(
            (r) =>
                r.headers['X-School-Id'] == '1' &&
                r.headers['X-Account-Id'] == '$account' &&
                r.method == 'GET',
          ),
          isTrue,
        );
        expect(planner.effects, 0);
        expect(tester.takeException(), isNull);
      },
    );
  }
}
