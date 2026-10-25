import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:eschool_teacher/ui/screens/home/homeScreen.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter/material.dart';
import 'package:integration_test/integration_test.dart';

import 'support/fixtures.dart';
import 'support/risk_fixture.dart';
import 'support/smoke.dart';

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets(
    'teacher opens owned follow-up and erases revoked private action',
    (tester) async {
      final fixture = RiskStaffFixture();
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
              'early_warning',
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
      await tapWhenReady(tester, find.text('Staff follow-up'));
      await tapWhenReady(tester, find.text('Open your intervention'));
      await pumpUntilFound(
        tester,
        find.text('Private synthetic follow-up action'),
      );
      fixture.granted = false;
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
      await tester.pump();
      expect(find.text('Private synthetic follow-up action'), findsNothing);
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await pumpUntilFound(
        tester,
        find.text('Reopen staff follow-up to check your current access.'),
      );
      expect(find.text('Private synthetic follow-up action'), findsNothing);
      final requests = api.requests
          .where((r) => r.uri.path.contains('/growth/risk/'))
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
