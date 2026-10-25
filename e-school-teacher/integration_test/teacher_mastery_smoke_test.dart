import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:eschool_teacher/ui/screens/home/homeScreen.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';

import 'support/fixtures.dart';
import 'support/smoke.dart';

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets(
    'teacher opens allocated roster mastery and hides evidence when role access is withdrawn',
    (tester) async {
      var revoked = false;
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
              'web_teacher',
            ])
              {'key': key, 'read_only': false},
          ],
        }),
        'GET v2/growth/mastery/configuration': (_) => revoked
            ? const FakeResponse({'message': 'Forbidden'}, status: 403)
            : ok({
                'session_year_id': 2,
                'can_manage': false,
                'settings': {
                  'family_marks_enabled': false,
                  'revision': 0,
                  'policy_version': 'recent20-v1',
                },
                'groups': [
                  {
                    'id': 9,
                    'name': 'Mathematics A',
                    'subject_id': 4,
                    'subject_name': 'Mathematics',
                    'grade_level_id': 7,
                    'grade_name': 'Grade 7',
                    'session_year_id': 2,
                    'year_name': '2026',
                  },
                ],
              }),
        'GET v2/growth/mastery/groups/9/learners': ok({
          'group_id': 9,
          'current_page': 1,
          'last_page': 1,
          'total': 1,
          'learners': [
            {'learner_id': 11, 'display_name': 'Asha Synthetic'},
          ],
        }),
        'GET v2/growth/mastery/groups/9/learners/11': ok(
          masteryFixture(staff: true),
        ),
      });
      await pumpUntilFound(tester, find.text('Email or handle'));
      await enterField(tester, 0, teacherEmail);
      await enterField(tester, 1, password);
      await tapWhenReady(
        tester,
        find.widgetWithText(CustomRoundedButton, 'Sign in'),
      );
      await pumpUntilFound(tester, find.byType(HomeScreen));
      await tapWhenReady(tester, find.text('Mastery map'));
    await pumpUntilFound(tester, find.byType(MasteryScreen));
      await pumpUntilFound(tester, find.text('Fractions foundation'));
      await tapWhenReady(tester, find.text('Topic evidence'));
      await pumpUntilFound(tester, find.textContaining('Level 0 / 4'));
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
      expect(requests.every((r) => r.headers['X-School-Id'] == '1'), isTrue);
      expect(
        requests.any(
          (r) => r.uri.path.contains('/subjects/') || r.method != 'GET',
        ),
        isFalse,
      );
      expect(tester.takeException(), isNull);
    },
  );
}
