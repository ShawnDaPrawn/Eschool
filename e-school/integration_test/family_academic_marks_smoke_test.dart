import 'package:eschool/ui/screens/parentHomeScreen.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';

import 'package:eschool_academics/testing.dart';

import 'support/fixtures.dart';
import 'support/smoke.dart';

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets('parent reads only the selected childs released period', (
    tester,
  ) async {
    final api = await startSmokeApp(tester, {
      ...parentOfTwoRoutes(),
      'GET v2/me/context': ok({
        ...academicContextFixture(role: 'Parent'),
        'children': [
          for (final (id, firstName) in [(11, 'Thandi'), (12, 'Sipho')])
            {
              'id': id,
              'user_id': id,
              'first_name': firstName,
              'last_name': 'Mokoena',
              'name': '$firstName Mokoena',
              'image': null,
              'school': {
                'id': 1,
                'name': 'Smoke Test School',
                'short_name': null,
                'crest_url': null,
              },
            },
        ],
      }),
      'GET v2/academics/marks/learners/11/periods': ok(periodsFixture()),
      'GET v2/academics/marks/learners/11': ok(releasedFixture(11)),
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
    await tapWhenReady(tester, find.text('Assessment marks'));
    await pumpUntilFound(tester, find.byType(ReleasedMarksScreen));
    await pumpUntilFound(tester, find.text('Mathematics'));
    expect(find.textContaining('75.05%'), findsOneWidget);
    expect(find.byType(TextField), findsNothing);
    expect(api.requested('GET v2/academics/marks/learners/12'), isFalse);
    expect(
      api.requests.where((r) => r.uri.path.contains('/marks/groups/')),
      isEmpty,
    );
    final request = api.requests.lastWhere(
      (r) => r.uri.path.endsWith('/marks/learners/11'),
    );
    expect(request.headers['X-School-Id'], '1');
    expect(request.queryParameters['session_year_id'], 1);
    expect(request.queryParameters['term_id'], 1);
    expect(tester.takeException(), isNull);
  });
}
