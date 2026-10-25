import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_teacher/ui/screens/home/homeScreen.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';

import 'support/fixtures.dart';
import 'support/smoke.dart';
import 'support/wellbeing_fixture.dart';

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets(
    'classic dual-role teacher opens only assigned counselling records',
    (tester) async {
      final clinical = ClinicalStaffFixture();
      final api = await startSmokeApp(tester, {
        ...teacherRoutes(),
        ...clinical.api.routes,
        'GET v2/me/context': ok({
          ...academicContextFixture(),
          'user': {
            'id': 12,
            'first_name': 'Teacher',
            'last_name': 'Counsellor',
            'name': 'Teacher Counsellor',
            'email': teacherEmail,
            'image': null,
            'roles': ['Teacher', 'Counsellor'],
          },
          'feature_flags': {'module_switches': true},
          'modules': [
            for (final key in [
              'school',
              'people',
              'academics',
              'web_teacher',
              'wellbeing',
            ])
              {'key': key, 'read_only': false},
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
      await pumpUntilFound(tester, find.byType(HomeScreen));
      await tapWhenReady(tester, find.text('Incidents and counselling'));
      await pumpUntilFound(tester, find.text('Synthetic Case Learner'));
      await tester.tap(find.text('Synthetic Case Learner'));
      await tester.pumpAndSettle();
      final scroll = find.byType(Scrollable).first,
          target = find.text('Private clinical browser baseline');
      for (int i = 0; target.evaluate().isEmpty && i < 20; i++) {
        await tester.drag(scroll, const Offset(0, -250));
        await tester.pumpAndSettle();
      }
      expect(target, findsOneWidget);
      clinical.granted = false;
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
      await tester.pump();
      expect(target, findsNothing);
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await pumpUntilFound(
        tester,
        find.text('No current safeguarding or counselling assignments.'),
      );
      expect(target, findsNothing);
      expect(
        api.requests
            .where((r) => r.path.contains('/wellbeing/'))
            .every(
              (r) =>
                  r.headers['X-Account-Id'] == '12' &&
                  r.headers['X-School-Id'] == '1',
            ),
        true,
      );
      expect(tester.takeException(), isNull);
    },
  );
}
