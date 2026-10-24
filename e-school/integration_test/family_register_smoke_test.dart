import 'package:eschool/ui/screens/parentHomeScreen.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';

import 'support/fixtures.dart';
import 'support/smoke.dart';

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets(
    'parent opens selected child attendance and signed QR from home',
    (tester) async {
      final api = await startSmokeApp(tester, {
        ...parentOfTwoRoutes(),
        'GET v2/me/context': ok({
          ...academicContextFixture(role: 'Parent'),
          'feature_flags': {'academic_registers': true},
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
        'GET v2/academics/registers/learners/11/today': ok({
          'learner_id': 11,
          'on_date': '2026-02-10',
          'entries': [
            {
              'register_id': 4,
              'label': 'Grade 7 A',
              'kind': 'homeroom',
              'state': 'present',
              'source': 'tap',
              'amended_at': null,
            },
          ],
        }),
        'GET v2/academics/registers/learners/11/qr': ok({
          'learner_id': 11,
          'qr_code': 'NEAR1.synthetic-11.signature',
          'version': 1,
        }),
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
      await tapWhenReady(tester, find.text('Attendance and QR'));
      await pumpUntilFound(tester, find.byType(FamilyAttendanceScreen));
      await pumpUntilFound(tester, find.text('Learner attendance QR'));
      expect(find.text('Present'), findsOneWidget);
      final requests = api.requests.where(
        (r) => r.uri.path.contains('/academics/registers/'),
      );
      expect(requests.length, greaterThanOrEqualTo(3));
      for (final request in requests) {
        expect(request.headers['X-School-Id'], '1');
        expect(request.uri.path, contains('/learners/11/'));
        expect(request.method, 'GET');
      }
      expect(api.requested('GET v2/academics/registers/choices'), isFalse);
      expect(tester.takeException(), isNull);
    },
  );
}
