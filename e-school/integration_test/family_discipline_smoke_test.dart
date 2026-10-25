import 'package:eschool/ui/screens/parentHomeScreen.dart';
import 'package:eschool/ui/screens/home/homeScreen.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter/material.dart';
import 'package:integration_test/integration_test.dart';

import 'support/fixtures.dart';
import 'support/smoke.dart';

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  for (final parentProfile in [false, true]) {
    testWidgets(
      '${parentProfile ? 'parent' : 'learner'} reads only published own discipline, acknowledges only as guardian and erases revoked history',
      (tester) async {
        var revoked = false, acknowledged = false;
        final account = parentProfile ? 21 : 11;
        final api = await startSmokeApp(tester, {
          ...(parentProfile ? parentOfTwoRoutes() : learnerRoutes()),
          'GET v2/me/context': ok({
            ...academicContextFixture(
              role: parentProfile ? 'Parent' : 'Student',
            ),
            'user': {
              'id': account,
              'first_name': 'Synthetic',
              'last_name': 'Mokoena',
              'name': 'Synthetic Mokoena',
              'email': parentProfile ? parentEmail : 'learner@example.test',
              'image': null,
              'roles': [parentProfile ? 'Parent' : 'Student'],
            },
            'feature_flags': {'module_switches': true, 'mastery': true},
            'modules': [
              for (final key in [
                'school',
                'people',
                'academics',
                'mastery',
                'discipline',
                'web_parent',
                'web_learner',
              ])
                {'key': key, 'read_only': false},
            ],
            if (parentProfile)
              'children': [
                for (final (id, name) in [(11, 'Thandi'), (12, 'Sipho')])
                  {
                    'id': id,
                    'user_id': id,
                    'first_name': name,
                    'last_name': 'Mokoena',
                    'name': '$name Mokoena',
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
          'GET v2/discipline/family/learners/11': (_) => revoked
              ? const FakeResponse({'message': 'Scope withdrawn'}, status: 403)
              : ok({
                  'learner_id': 11,
                  'session_year_id': 7,
                  'page': 1,
                  'last_page': 1,
                  'years': [
                    {'id': 7, 'name': '2026'},
                  ],
                  'records': [
                    {
                      'id': 9,
                      'occurred_on': '2026-10-25',
                      'description':
                          'Published synthetic family discipline fact',
                      'infraction': 'Observed school fact',
                      'level': 'School notice',
                      'sanction': 'School notice',
                      'status': 'confirmed',
                      'family_reason': null,
                      'revision': 1,
                      'acknowledged': acknowledged,
                      'detentions': <Object>[],
                    },
                  ],
                }),
          'POST v2/discipline/family/records/9/acknowledge': (_) {
            acknowledged = true;
            return ok({
              'record_id': 9,
              'revision': 1,
              'acknowledged_at': '2026-10-25T12:00:00Z',
            });
          },
        });
        await tapWhenReady(
          tester,
          find.text(parentProfile ? 'Login as Parent' : 'Login as Student'),
        );
        await pumpUntilFound(
          tester,
          find.text(
            parentProfile
                ? 'Email or handle'
                : 'Student number, GR number or handle',
          ),
        );
        await enterField(tester, 0, parentProfile ? parentEmail : grNumber);
        await enterField(tester, 1, password);
        await tapWhenReady(
          tester,
          find.widgetWithText(CustomRoundedButton, 'Sign in'),
        );
        await pumpUntilFound(
          tester,
          find.byType(parentProfile ? ParentHomeScreen : HomeScreen),
        );
        await tapWhenReady(tester, find.text('School discipline'));
        await pumpUntilFound(tester, find.byType(FamilyDisciplineScreen));
        await pumpUntilFound(
          tester,
          find.text('Published synthetic family discipline fact'),
        );
        if (parentProfile) {
          await tapWhenReady(
            tester,
            find.text('Acknowledge reading this revision'),
          );
          await pumpUntilFound(
            tester,
            find.text('You acknowledged this revision'),
          );
        } else {
          expect(find.text('Acknowledge reading this revision'), findsNothing);
        }
        revoked = true;
        tester.binding.handleAppLifecycleStateChanged(
          AppLifecycleState.inactive,
        );
        await tester.pump();
        expect(
          find.text('Published synthetic family discipline fact'),
          findsNothing,
        );
        tester.binding.handleAppLifecycleStateChanged(
          AppLifecycleState.resumed,
        );
        await pumpUntilFound(
          tester,
          find.text('Reopen school discipline to check your access.'),
        );
        expect(
          find.text('Published synthetic family discipline fact'),
          findsNothing,
        );
        final requests = api.requests
            .where((r) => r.uri.path.contains('/discipline/'))
            .toList();
        expect(requests, isNotEmpty);
        expect(
          requests.every(
            (r) =>
                r.headers['X-School-Id'] == '1' &&
                r.headers['X-Account-Id'] == '$account',
          ),
          isTrue,
        );
        expect(requests.every((r) => r.uri.path.contains('/family/')), isTrue);
        expect(
          requests.any((r) => r.uri.path.contains('/learners/12')),
          isFalse,
        );
        expect(requests.any((r) => r.uri.path.contains('/groups/')), isFalse);
        expect(tester.takeException(), isNull);
      },
    );
  }
}
