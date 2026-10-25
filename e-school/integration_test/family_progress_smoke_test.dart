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
      '${parentProfile ? 'parent' : 'learner'} opens own released progress, distinguishes unknown and clears revoked evidence',
      (tester) async {
        var revoked = false;
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
          'GET v2/growth/progress/learners/11/periods': (_) => revoked
              ? const FakeResponse({'message': 'Forbidden'}, status: 403)
              : ok({
                  'learner_id': 11,
                  'periods': [
                    {
                      'session_year_id': 7,
                      'year_name': '2026',
                      'term_id': 8,
                      'term_name': 'Term 1',
                      'released_at': '2026-10-25T01:00:00Z',
                    },
                  ],
                }),
          'GET v2/growth/progress/learners/11/bands': (_) => revoked
              ? const FakeResponse({'message': 'Forbidden'}, status: 403)
              : ok({
                  'audience': 'family',
                  'learner_id': 11,
                  'results': [
                    {
                      'subject_name': 'Mathematics',
                      'grade_name': 'Grade 7',
                      'release_id': 2,
                      'subject_id': 4,
                      'grade_level_id': 7,
                      'session_year_id': 7,
                      'term_id': 8,
                      'mode': 'running',
                      'provisional': true,
                      'rating_level': null,
                      'released_at': '2026-10-25T01:00:00Z',
                      'released_percentage_bp': null,
                      'band': 'insufficient_evidence',
                      'percentage_bp': null,
                      'coverage_bp': 0,
                      'alerts_eligible': false,
                      'settings_revision': 0,
                    },
                  ],
                }),
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
        await tapWhenReady(tester, find.text('Learning progress'));
        await pumpUntilFound(tester, find.byType(FamilyProgressScreen));
        final chooser = find.descendant(
          of: find.byType(FamilyProgressScreen),
          matching: find.byType(DropdownButtonFormField<int>),
        );
        await tapWhenReady(tester, chooser);
        await tester.pumpAndSettle();
        await tester.tap(find.text('2026 · Term 1').last);
        await pumpUntilFound(tester, find.text('Insufficient evidence'));
        expect(find.text('0.00%'), findsNothing);
        revoked = true;
        tester.binding.handleAppLifecycleStateChanged(
          AppLifecycleState.inactive,
        );
        await tester.pump();
        expect(find.text('Insufficient evidence'), findsNothing);
        tester.binding.handleAppLifecycleStateChanged(
          AppLifecycleState.resumed,
        );
        await pumpUntilFound(
          tester,
          find.text(
            'Released progress is unavailable. Check your access and retry.',
          ),
        );
        expect(find.text('Mathematics'), findsNothing);
        final requests = api.requests
            .where((r) => r.uri.path.contains('/growth/progress/'))
            .toList();
        expect(requests, isNotEmpty);
        expect(requests.every((r) => r.headers['X-School-Id'] == '1'), isTrue);
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
