import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';

import 'support/fixtures.dart';
import 'support/smoke.dart';

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets('teacher opens own private card and freezes a short-lived code', (
    tester,
  ) async {
    final fixture = CardSmokeFixture(personId: 12, type: 'staff');
    final api = await startSmokeApp(tester, {
      ...teacherRoutes(),
      ...fixture.routes(),
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
            'cards',
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
    await tapWhenReady(tester, find.text('School identity cards'));
    await tapWhenReady(tester, find.text('Show secure code'));
    await pumpUntilFound(
      tester,
      find.byKey(
        const ValueKey('secure-card-code-8aef50e7-2429-4d2c-a523-668ae436c4d4'),
      ),
    );
    await tapWhenReady(tester, find.text('Freeze card'));
    await pumpUntilFound(tester, find.byType(TextField));
    await tester.enterText(find.byType(TextField), 'Synthetic lost card');
    await tester.pump();
    await tapWhenReady(
      tester,
      find.widgetWithText(FilledButton, 'Freeze card'),
    );
    await pumpUntilFound(
      tester,
      find.text('Contact your school to restore or replace this card.'),
    );
    expect(fixture.freezes, 1);
    expect(
      find.byKey(
        const ValueKey('secure-card-code-8aef50e7-2429-4d2c-a523-668ae436c4d4'),
      ),
      findsNothing,
    );
    fixture.allowed = false;
    tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
    await tester.pump();
    tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
    await pumpUntilFound(
      tester,
      find.text('Your card access changed. Reopen this screen.'),
    );
    final reads = api.requests.where((r) => r.uri.path.contains('/cards/'));
    expect(reads, isNotEmpty);
    expect(
      reads.every(
        (r) =>
            r.headers['X-School-Id'] == '1' &&
            r.headers['X-Account-Id'] == '12',
      ),
      isTrue,
    );
    expect(tester.takeException(), isNull);
  });
}
