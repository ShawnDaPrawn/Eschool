import 'package:eschool/ui/screens/parentHomeScreen.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_people/eschool_people.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';

import 'support/fixtures.dart';
import 'support/smoke.dart';

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets('guardian finds the selected schools phone and fee schedule', (
    tester,
  ) async {
    Map<String, Object?> school(int id) => {
      'id': id,
      'name': 'School $id',
      'short_name': 'S$id',
      'crest_url': null,
    };
    Map<String, Object?> info(int id) => {
      'school': school(id),
      'website': null,
      'phone': '011 555 010$id',
      'email': null,
      'address': null,
      'documents': [
        {
          'title': 'School $id fee schedule',
          'kind': 'fee_schedule',
          'url': 'https://school$id.test/fees.pdf',
        },
      ],
      'term_dates': [
        {'name': 'Term 4', 'starts_on': '2026-10-01', 'ends_on': '2026-12-04'},
      ],
    };
    final api = await startSmokeApp(tester, {
      ...parentOfTwoRoutes(),
      'GET v2/me/context': ok({
        'schools': [
          {
            'school': school(1),
            'roles': ['Parent'],
          },
        ],
        'children': [
          {
            'id': 11,
            'user_id': 11,
            'first_name': 'Thandi',
            'last_name': 'Mokoena',
            'name': 'Thandi Mokoena',
            'image': null,
            'school': school(1),
          },
          {
            'id': 12,
            'user_id': 12,
            'first_name': 'Sipho',
            'last_name': 'Mokoena',
            'name': 'Sipho Mokoena',
            'image': null,
            'school': school(2),
          },
        ],
      }),
      'GET v2/schools/1/my-school': ok(info(1)),
      'GET v2/schools/2/my-school': ok(info(2)),
    });
    await tapWhenReady(tester, find.text('Login as Parent'));
    await pumpUntilFound(tester, find.text('Email or handle'));
    await enterField(tester, 0, parentEmail);
    await enterField(tester, 1, password);
    await tapWhenReady(
      tester,
      find.widgetWithText(CustomRoundedButton, 'Sign in'),
    );
    await pumpUntilFound(tester, find.byType(ParentHomeScreen));
    await tapWhenReady(tester, find.byIcon(Icons.settings));
    await tapWhenReady(tester, find.text('My school'));
    await pumpUntilFound(tester, find.byType(MySchoolScreen));
    await pumpUntilFound(tester, find.text('011 555 0101'));
    await tapWhenReady(tester, find.widgetWithText(ChoiceChip, 'S2'));
    await pumpUntilFound(tester, find.text('011 555 0102'));
    await pumpUntilFound(tester, find.text('School 2 fee schedule'));
    expect(find.text('011 555 0101'), findsNothing);
    final request = api.requests.lastWhere(
      (r) => r.uri.path.endsWith('schools/2/my-school'),
    );
    expect(request.headers['X-School-Id'], '2');
  });
}
