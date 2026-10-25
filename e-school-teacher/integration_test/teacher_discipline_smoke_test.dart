import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_teacher/ui/screens/home/homeScreen.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter/material.dart';
import 'package:integration_test/integration_test.dart';

import 'support/fixtures.dart';
import 'support/discipline_fixture.dart';
import 'support/smoke.dart';

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets(
    'teacher opens an authorized discipline record and erases revoked private follow-up',
    (tester) async {
      final fixture = DisciplineStaffFixture();
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
              'discipline',
              'web_teacher',
            ])
              {'key': key, 'read_only': false},
          ],
        }),
        ...fixture.routes(),
      });
      await pumpUntilFound(tester, find.text('Email or handle'));
      await enterField(tester, 0, teacherEmail);
      await enterField(tester, 1, password);
      await tapWhenReady(
        tester,
        find.widgetWithText(CustomRoundedButton, 'Sign in'),
      );
      await pumpUntilFound(tester, find.byType(HomeScreen));
      await tapWhenReady(tester, find.text('School discipline'));
      await tapWhenReady(tester, find.text('Synthetic learner'));
      await pumpUntilFound(
        tester,
        find.text('Private synthetic discipline follow-up'),
      );
      fixture.granted = false;
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
      await tester.pump();
      expect(find.text('Private synthetic discipline follow-up'), findsNothing);
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await pumpUntilFound(
        tester,
        find.text('Reopen school discipline to check your access.'),
      );
      expect(find.text('Private synthetic discipline follow-up'), findsNothing);
      final requests = api.requests
          .where((r) => r.uri.path.contains('/discipline/'))
          .toList();
      expect(requests, isNotEmpty);
      expect(
        requests.every(
          (r) =>
              r.headers['X-School-Id'] == '1' &&
              r.headers['X-Account-Id'] == '12',
        ),
        isTrue,
      );
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
