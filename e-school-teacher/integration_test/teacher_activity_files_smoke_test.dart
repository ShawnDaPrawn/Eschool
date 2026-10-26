import 'package:eschool_teacher/utils/api.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';

import 'support/fixtures.dart';
import 'support/activity_file_flow.dart';
import 'support/smoke.dart';

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets(
    'teacher reads and removes private group documents with fresh scope',
    (tester) async {
      final files = MaterialFileClientFixture(staff: true);
      final api = await startSmokeApp(tester, {
        ...teacherRoutes(),
        ...files.routes(),
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
              'groups',
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
      await verifyMaterialFiles(
        tester,
        files,
        api,
        account: 12,
        bearer: Api.client.sendsBearerToken,
      );
    },
  );
}
