import 'package:eschool/ui/screens/parentHomeScreen.dart';
import 'package:eschool_academics/eschool_academics.dart';
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
    'parent opens only the selected childs dated timetable and room',
    (tester) async {
      final api = await startSmokeApp(tester, {
        ...parentOfTwoRoutes(),
        'GET v2/me/context': ok({
          ...academicContextFixture(role: 'Parent'),
          'feature_flags': {'academic_timetable': true},
          'children': [
            for (final (id, name) in [(11, 'Thandi'), (12, 'Sipho')])
              {
                'id': id,
                'user_id': id,
                'first_name': name,
                'last_name': 'Mokoena',
                'name': '$name Mokoena',
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
        'GET v2/academics/timetable/next': ok({
          'lesson': timetableLessonFixture(),
        }),
        'GET v2/academics/timetable/week': ok(timetableWeekFixture()),
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
      await pumpUntilFound(tester, find.text('Room 101 · Main'));
      await tapWhenReady(tester, find.text('View week'));
      await pumpUntilFound(tester, find.byType(TimetableScreen));
      await pumpUntilFound(tester, find.text('Room 101'));
      expect(find.text('Place lesson'), findsNothing);
      expect(
        api.requested('GET v2/academics/timetable/configuration'),
        isFalse,
      );
      expect(api.requested('GET v2/academics/teaching-groups'), isFalse);
      final requests = api.requests.where(
        (r) => r.uri.path.contains('/academics/timetable/'),
      );
      expect(requests.length, greaterThanOrEqualTo(2));
      for (final request in requests) {
        expect(request.headers['X-School-Id'], '1');
        expect(request.queryParameters['learner_id'], 11);
        expect(request.queryParameters.containsKey('session_year_id'), isFalse);
      }
      expect(tester.takeException(), isNull);
    },
  );
}
