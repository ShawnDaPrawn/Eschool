import 'package:dio/dio.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool_teacher/app/deepLinks.dart';
import 'package:eschool_teacher/app/routes.dart';
import 'package:eschool_teacher/ui/screens/home/homeScreen.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';

import 'support/fixtures.dart';
import 'support/smoke.dart';

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets(
    'teacher requests dated cover, keeps private draft and loses withdrawn access',
    (tester) async {
      var submitted = false, withdrawn = false;
      final api = await startSmokeApp(tester, {
        ...teacherRoutes(),
        'GET v2/me/context': ok({
          ...academicContextFixture(),
          'user': {
            'id': 31,
            'first_name': 'Sipho',
            'last_name': 'Nkosi',
            'name': 'Sipho Nkosi',
            'email': teacherEmail,
            'image': null,
            'roles': ['Teacher'],
          },
          'feature_flags': {'module_switches': true, 'relief_cover': true},
          'modules': [
            for (final key in [
              'school',
              'people',
              'academics',
              'web_teacher',
              'relief_cover',
            ])
              {'key': key, 'read_only': false},
          ],
        }),
        'GET v2/pastoral/cover/configuration': (_) => withdrawn
            ? const FakeResponse({'message': 'Access withdrawn'}, status: 403)
            : ok({
                'session_year_id': 1,
                'year_name': '2026',
                'can_manage': false,
                'teachers': [
                  {'id': 31, 'name': 'Sipho Nkosi'},
                ],
                'current_page': 1,
                'last_page': 1,
                'total': submitted ? 1 : 0,
                'absences': submitted
                    ? [
                        {
                          'id': 8,
                          'revision': 1,
                          'state': 'requested',
                          'teacher_id': 31,
                          'teacher_name': 'Sipho Nkosi',
                          'starts_on': '2026-10-27',
                          'ends_on': '2026-10-27',
                        },
                      ]
                    : [],
              }),
        'GET v2/pastoral/cover/mine': ok({'occurrences': []}),
        'POST v2/pastoral/cover/absences': (RequestOptions r) {
          expect(r.data['teacher_id'], 31);
          expect(r.data['reason'], 'Private appointment');
          expect(r.data['operation_id'], isNotEmpty);
          submitted = true;
          return ok({'id': 8});
        },
      });
      await pumpUntilFound(tester, find.text('Email or handle'));
      await enterField(tester, 0, teacherEmail);
      await enterField(tester, 1, password);
      await tapWhenReady(
        tester,
        find.widgetWithText(CustomRoundedButton, 'Sign in'),
      );
      await pumpUntilFound(tester, find.byType(HomeScreen));
      await tapWhenReady(tester, find.text('Relief cover'));
      await pumpUntilFound(tester, find.byType(CoverScreen));
      await tapWhenReady(tester, find.text('Cover requests'));
      final reason = find.byWidgetPredicate(
        (w) =>
            w is TextField &&
            w.decoration?.labelText == 'Private reason (optional)',
      );
      await pumpUntilFound(tester, reason);
      await tester.enterText(reason, 'Private appointment');
      await tester.pump();
      await tapWhenReady(
        tester,
        find.widgetWithText(FilledButton, 'Request cover'),
      );
      await pumpUntilFound(
        tester,
        find.text('Sipho Nkosi · 2026-10-27 – 2026-10-27'),
      );
      expect(submitted, isTrue);
      expect(
        api.requests
            .where((r) => r.path.contains('/pastoral/cover/'))
            .every((r) => r.headers['X-School-Id'] == '1'),
        isTrue,
      );
      expect(
        api.requests
            .where((r) => r.path.contains('/pastoral/cover/'))
            .every((r) => r.headers['X-Account-Id'] == '31'),
        isTrue,
      );
      withdrawn = true;
      openDeepLink(const DeepLinkTarget(Routes.reliefCover), signedIn: true);
      await pumpUntilFound(tester, find.textContaining('Your access changed'));
      expect(find.byType(TextField), findsNothing);
    },
  );
}
