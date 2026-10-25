import 'package:eschool/ui/screens/parentHomeScreen.dart';
import 'package:eschool/ui/screens/home/homeScreen.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';

import 'support/fixtures.dart';
import 'support/smoke.dart';

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  for (final parentProfile in [false, true]) {
    testWidgets(
      '${parentProfile ? 'parent' : 'learner'} opens own mastery, distinguishes unknown and clears revoked evidence',
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
          'GET v2/growth/mastery/learners/11/subjects': (_) => revoked
              ? const FakeResponse({'message': 'Forbidden'}, status: 403)
              : ok({
                  'learner_id': 11,
                  'subjects': [
                    {'id': 4, 'name': 'Mathematics'},
                  ],
                }),
          'GET v2/growth/mastery/learners/11': (_) => revoked
              ? const FakeResponse({'message': 'Forbidden'}, status: 403)
              : ok(masteryFixture()),
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
        await tapWhenReady(tester, find.text('Mastery map'));
        await pumpUntilFound(tester, find.byType(MasteryScreen));
        await pumpUntilFound(tester, find.text('Fractions foundation'));
        await tapWhenReady(tester, find.text('Topic evidence'));
        await pumpUntilFound(tester, find.textContaining('Level 0 / 4'));
        expect(find.textContaining('Not yet assessed'), findsOneWidget);
        revoked = true;
        await tapWhenReady(tester, find.byTooltip('Refresh mastery'));
        await pumpUntilFound(
          tester,
          find.text('Mastery is unavailable for this account or school.'),
        );
        expect(find.text('Fractions foundation'), findsNothing);
        final requests = api.requests
            .where((r) => r.uri.path.contains('/growth/mastery/'))
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
