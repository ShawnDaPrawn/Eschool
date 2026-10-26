import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';

import 'support/fixtures.dart';
import 'support/smoke.dart';

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets(
    'teacher reads scoped effective consent without family health access',
    (tester) async {
      final forms = FamilyFormSmokeFixture(accountId: 12, staff: true);
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
              'family_forms',
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
      await tapWhenReady(tester, find.text('Activity consent'));
      await tapWhenReady(tester, find.text('Synthetic trip'));
      await pumpUntilFound(
        tester,
        find.textContaining('Permission is not current'),
      );
      expect(find.text(privateWording), findsOneWidget);
      expect(find.text('Health forms'), findsNothing);
      expect(find.byType(CheckboxListTile), findsNothing);
      forms.allowed = false;
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
      await tester.pump();
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await pumpUntilFound(
        tester,
        find.text('Your family access changed. Reopen this screen.'),
      );
      expect(find.text(privateWording), findsNothing);
      final requests = api.requests.where(
        (r) => r.uri.path.contains('/family-forms/'),
      );
      expect(
        requests.every(
          (r) =>
              r.headers['X-School-Id'] == '1' &&
              r.headers['X-Account-Id'] == '12',
        ),
        isTrue,
      );
      expect(tester.takeException(), isNull);
    },
  );
}
