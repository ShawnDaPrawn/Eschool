import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_academics/testing.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';

import 'support/fixtures.dart';
import 'support/discipline_fixture.dart';
import 'support/my_grade_fixture.dart';
import 'support/smoke.dart';

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets(
    'grade head corrects the current register and opens the exact private discipline record',
    (tester) async {
      final grade = MyGradeSmokeFixture(),
          discipline = DisciplineStaffFixture();
      final api = await startSmokeApp(tester, {
        ...teacherRoutes(),
        ...grade.routes(),
        ...discipline.routes(),
        'GET v2/me/context': ok({
          ...academicContextFixture(),
          'user': {
            'id': 12,
            'first_name': 'Synthetic',
            'last_name': 'Head',
            'name': 'Synthetic Head',
            'email': teacherEmail,
            'image': null,
            'roles': ['Teacher'],
          },
          'feature_flags': {'module_switches': true, 'my_grade': true},
          'modules': [
            for (final key in [
              'school',
              'people',
              'academics',
              'my_grade',
              'discipline',
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
      await tapWhenReady(tester, find.text('My grade'));
      await pumpUntilFound(tester, find.text('Synthetic grade learner'));
      await tapWhenReady(tester, find.text('Open register'));
      await pumpUntilFound(tester, find.text('1 unmarked'));
      await tapWhenReady(
        tester,
        find.widgetWithText(OutlinedButton, 'Mark remaining present'),
      );
      await tapWhenReady(
        tester,
        find.widgetWithText(FilledButton, 'Save register'),
      );
      await pumpUntilFound(tester, find.text('Register synced'));
      expect(grade.saved, isTrue);
      await tester.pageBack();
      await tester.pump();
      await tapWhenReady(tester, find.text('Synthetic discipline queue'));
      await pumpUntilFound(
        tester,
        find.text('Private synthetic discipline follow-up'),
      );
      grade.granted = false;
      discipline.granted = false;
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
      await tester.pump();
      expect(find.text('Private synthetic discipline follow-up'), findsNothing);
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await pumpUntilFound(
        tester,
        find.text('Reopen My grade to check your access.'),
      );
      expect(find.text('Private synthetic discipline follow-up'), findsNothing);
      final reads = api.requests.where(
        (r) =>
            r.uri.path.contains('/my-grade/') ||
            r.uri.path.contains('/discipline/') ||
            r.uri.path.contains('/academics/registers/'),
      );
      expect(reads, isNotEmpty);
      expect(
        reads.every(
          (r) =>
              r.headers['X-School-Id'] == '1' &&
              r.headers['X-Account-Id'] == '12',
        ),
        isTrue,
      );
      final writes = reads.where((r) => r.method == 'POST').toList();
      expect(writes, hasLength(1));
      expect((writes.single.data as Map)['operation_id'], isNotEmpty);
      expect(tester.takeException(), isNull);
    },
  );
}
