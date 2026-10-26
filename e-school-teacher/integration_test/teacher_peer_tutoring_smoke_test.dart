import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';

import 'support/fixtures.dart';
import 'support/smoke.dart';
import 'support/peer_smoke.dart';

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets(
    'teacher supervises matched tutoring with exact retries and corrections',
    (tester) async {
      final forms = PeerSmokeFixture();
      final api = await startSmokeApp(tester, {
        ...teacherRoutes(),
        ...forms.routes,
        'GET v2/me/context': ok({
          ...academicContextFixture(),
          'user': {
            'id': 12,
            'first_name': 'Synthetic',
            'last_name': 'Teacher',
            'name': 'Synthetic Teacher',
            'email': teacherEmail,
            'image': null,
            'roles': ['Teacher'],
          },
          'feature_flags': {'module_switches': true},
          'modules': [
            for (final key in [
              'school',
              'people',
              'academics',
              'peer_tutoring',
              'mastery',
              'web_teacher',
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
      await exercisePeerSupervisor(tester, forms);
      forms.allowed = false;
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
      await tester.pump();
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await pumpUntilFound(
        tester,
        find.text('Your peer tutoring access changed. Reopen this screen.'),
      );
      expect(find.text('Synthetic supervised room'), findsNothing);
      final requests = api.requests.where(
        (r) => r.uri.path.contains('/peer-tutoring/'),
      );
      expect(
        requests.every(
          (r) =>
              r.headers['X-School-Id'] == '1' &&
              r.headers['X-Account-Id'] == '12',
        ),
        isTrue,
      );
      expect(forms.effects, 3);
      expect(
        requests
            .where((r) => r.method == 'POST')
            .every((r) => r.queryParameters.isEmpty),
        isTrue,
      );
      expect(tester.takeException(), isNull);
    },
  );
}
