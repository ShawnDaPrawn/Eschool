import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:eschool_teacher/ui/screens/home/homeScreen.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter/material.dart';
import 'package:integration_test/integration_test.dart';

import 'support/fixtures.dart';
import 'support/smoke.dart';

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets(
    'teacher opens own allocated notes and clears withdrawn private text',
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
        'GET v2/growth/progress/configuration': (_) => revoked
            ? const FakeResponse({'message': 'Forbidden'}, status: 403)
            : ok({
                'session_year_id': 7,
                'can_manage_bands': false,
                'can_assign_counselling': false,
                'can_read_caseload': false,
                'settings': {
                  'high_bp': 8000,
                  'secure_bp': 6500,
                  'minimum_coverage_bp': 2500,
                  'revision': 0,
                },
                'restricted_staff': [],
                'years': [
                  {'id': 7, 'name': '2026'},
                ],
                'terms': [],
                'groups': [
                  {
                    'id': 9,
                    'name': 'Mathematics A',
                    'subject_id': 4,
                    'subject_name': 'Mathematics',
                    'grade_name': 'Grade 7',
                    'session_year_id': 7,
                  },
                ],
              }),
        'GET v2/growth/progress/groups/9/learners': ok({
          'group_id': 9,
          'current_page': 1,
          'last_page': 1,
          'total': 1,
          'learners': [
            {'learner_id': 11, 'display_name': 'Asha Synthetic'},
          ],
        }),
        'GET v2/growth/progress/learners/11/notes': ok({
          'notes': [
            {
              'id': 9,
              'learner_id': 11,
              'subject_id': null,
              'session_year_id': 7,
              'author_id': 12,
              'author_name': 'Synthetic Teacher',
              'visibility': 'staff',
              'revision': 1,
              'archived': false,
              'updated_at': '2026-10-25T01:00:00Z',
            },
          ],
          'next_after': null,
        }),
        'GET v2/growth/progress/notes/9': ok({
          'id': 9,
          'learner_id': 11,
          'subject_id': null,
          'session_year_id': 7,
          'author_id': 12,
          'author_name': 'Synthetic Teacher',
          'visibility': 'staff',
          'revision': 1,
          'archived': false,
          'updated_at': '2026-10-25T01:00:00Z',
          'body': 'Private teaching observation',
        }),
      });
      await pumpUntilFound(tester, find.text('Email or handle'));
      await enterField(tester, 0, teacherEmail);
      await enterField(tester, 1, password);
      await tapWhenReady(
        tester,
        find.widgetWithText(CustomRoundedButton, 'Sign in'),
      );
      await pumpUntilFound(tester, find.byType(HomeScreen));
      await tapWhenReady(tester, find.text('Progress and notes'));
      final chooser = find.byKey(const ValueKey('learner-staff-9-1'));
      await tapWhenReady(tester, chooser);
      await pumpUntilFound(tester, find.text('Asha Synthetic'));
      await tapWhenReady(tester, find.text('Asha Synthetic').last);
      await tapWhenReady(tester, find.text('Synthetic Teacher'));
      await pumpUntilFound(tester, find.text('Private teaching observation'));
      revoked = true;
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
      await tester.pump();
      expect(find.text('Private teaching observation'), findsNothing);
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await pumpUntilFound(
        tester,
        find.text(
          'Unable to load or save. Check your access and connection, then retry.',
        ),
      );
      expect(find.text('Private teaching observation'), findsNothing);
      final requests = api.requests
          .where((r) => r.uri.path.contains('/growth/progress/'))
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
