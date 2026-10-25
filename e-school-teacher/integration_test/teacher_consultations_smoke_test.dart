import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_teacher/ui/screens/home/homeScreen.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';

import 'support/fixtures.dart';
import 'support/smoke.dart';

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets(
    'classic teacher uses own consultation audience and anonymous vote',
    (tester) async {
      final fixture = ConsultationSmokeFixture(accountId: 12);
      final api = await startSmokeApp(tester, {
        ...teacherRoutes(),
        ...fixture.routes,
        'GET teacher/get-notification': ok({
          'data': [
            {
              'id': 71,
              'title': 'School consultation available',
              'type': 'consultations.staff_opened',
              'message':
                  'Open school consultations to review an available ballot.',
              'date': '2026-10-25T10:00:00Z',
              'send_to': 2,
              'image': null,
            },
          ],
          'current_page': 1,
          'last_page': 1,
          'total': 1,
        }),
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
              'communication',
              'consultations',
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
      await pumpUntilFound(tester, find.byType(HomeScreen));
      await tapWhenReady(
        tester,
        find.ancestor(
          of: find.text('Settings'),
          matching: find.byType(InkWell),
        ),
      );
      await tapWhenReady(tester, find.text('Notifications'));
      await pumpUntilFound(tester, find.text('School consultation available'));
      await tapWhenReady(
        tester,
        find.byKey(const ValueKey('consultation-notice-71')),
      );
      await pumpUntilFound(tester, find.text('Synthetic school consultation'));
      await tapWhenReady(tester, find.text('Synthetic school consultation'));
      await pumpUntilFound(
        tester,
        find.text('Synthetic private consultation instructions.'),
      );
      await tapWhenReady(
        tester,
        find.byKey(const ValueKey('consultation-option-11')),
      );
      final answer = find.byKey(const ValueKey('consultation-answer-4'));
      await tester.ensureVisible(answer);
      await tester.pumpAndSettle();
      await tester.enterText(answer, 'Synthetic private staff answer');
      await tapWhenReady(tester, find.text('Record my response'));
      await pumpUntilFound(
        tester,
        find.text('Your response is recorded. You can participate only once.'),
      );
      expect(fixture.votes, 1);
      expect(fixture.lastVote!.keys, ['answers']);
      fixture.revoked = true;
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
      await tester.pump();
      expect(
        find.text('Synthetic private consultation instructions.'),
        findsNothing,
      );
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await pumpUntilFound(
        tester,
        find.text('Your consultation access changed. Reopen this screen.'),
      );
      final requests = api.requests.where(
        (r) => r.path.contains('/consultations'),
      );
      expect(requests, isNotEmpty);
      expect(
        requests.every(
          (r) =>
              r.headers['X-Account-Id'] == '12' &&
              r.headers['X-School-Id'] == '1',
        ),
        isTrue,
      );
      expect(tester.takeException(), isNull);
    },
  );
}
