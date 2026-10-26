import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';

import 'support/fixtures.dart';
import 'support/smoke.dart';
import 'support/exam_journey.dart';

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets(
    'Teacher licenses, publishes and confirms provisional exam feedback with exact retry',
    (tester) async {
      final fixture = ExamCoachSmokeFixture(reader: 31);
      late final FakeApi api;
      api = await startSmokeApp(tester, {
        ...teacherRoutes(),
        ...fixture.routes(() => api),
        'GET v2/me/context': ok({
          ...academicContextFixture(),
          'user': {
            'id': 31,
            'first_name': 'Synthetic',
            'last_name': 'Teacher',
            'name': 'Synthetic Teacher',
            'email': teacherEmail,
            'image': null,
            'roles': ['Teacher'],
          },
          'roles': ['Teacher'],
          'feature_flags': {'module_switches': true},
          'modules': [
            for (final key in [
              'school',
              'people',
              'academics',
              'exam_coach',
              'web_teacher',
            ])
              {'key': key, 'enabled': true, 'read_only': false},
          ],
        }),
      });
      await pumpUntilFound(tester, find.text('Email or handle'));
      await enterField(tester, 0, teacherEmail);
      await enterField(tester, 1, password);
      await tapWhenReady(
        tester,
        find.widgetWithText(CustomRoundedButton, 'Sign in'),
      );
      await pumpUntilFound(tester, find.text('Exam Coach'));
      await tapWhenReady(tester, find.text('Exam Coach'));
      await runExamTeacher(tester, fixture);
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
      await tester.pump();
      expect(find.text('PRIVATE-STAFF-MEMO-CANARY'), findsNothing);
      fixture.permitted = false;
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await tester.pumpAndSettle();
      expect(find.text('Explain the two equal parts.'), findsNothing);
      final requests = api.requests.where(
        (r) => r.uri.path.contains('/exam-coach/'),
      );
      expect(
        requests,
        everyElement(
          predicate(
            (dynamic r) =>
                r.headers['X-Account-Id'] == '31' &&
                r.headers['X-School-Id'] == '1',
          ),
        ),
      );
      expect(api.requestedPaths.any((p) => p.contains('marks/publish')), false);
    },
  );
}
