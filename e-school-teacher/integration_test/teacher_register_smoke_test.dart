import 'package:dio/dio.dart';
import 'package:eschool_academics/eschool_academics.dart';
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
    'teacher opens an allocated register and saves 35 explicit entries',
    (tester) async {
      var saved = false;
      final api = await startSmokeApp(tester, {
        ...teacherRoutes(),
        'GET v2/me/context': ok({
          ...academicContextFixture(),
          'feature_flags': {'academic_registers': true},
        }),
        'GET v2/academics/registers/choices': ok(registerChoicesFixture()),
        'POST v2/academics/registers/open': ok(registerMatrixFixture()),
        'GET v2/academics/registers/4': (RequestOptions request) => ok(
          registerMatrixFixture(
            revision: saved ? 1 : 0,
            state: saved ? 'present' : 'unmarked',
            entryRevision: saved ? 1 : 0,
          ),
        ),
        'POST v2/academics/registers/4/sync': (RequestOptions request) {
          final data = request.data as Map;
          expect((data['rows'] as List).length, 35);
          expect(
            (data['rows'] as List).every((row) => row['state'] == 'present'),
            isTrue,
          );
          saved = true;
          return ok({
            'register_id': 4,
            'revision': 1,
            'entries': [
              for (var id = 11; id < 46; id++)
                {'learner_id': id, 'revision': 1},
            ],
          });
        },
      });
      await pumpUntilFound(tester, find.text('Email or handle'));
      await enterField(tester, 0, teacherEmail);
      await enterField(tester, 1, password);
      await tapWhenReady(
        tester,
        find.widgetWithText(CustomRoundedButton, 'Sign in'),
      );
      await pumpUntilFound(tester, find.byType(HomeScreen));
      await tapWhenReady(tester, find.text('Academic registers'));
      await pumpUntilFound(tester, find.byType(RegisterHubScreen));
      await tapWhenReady(tester, find.text('Grade 7 A'));
      await pumpUntilFound(tester, find.text('35 unmarked'));
      await tapWhenReady(
        tester,
        find.widgetWithText(OutlinedButton, 'Mark remaining present'),
      );
      await pumpUntilFound(tester, find.text('35 present'));
      expect(saved, isFalse);
      await tapWhenReady(
        tester,
        find.widgetWithText(FilledButton, 'Save register'),
      );
      await pumpUntilFound(tester, find.text('Register synced'));
      final writes = api.requests
          .where((r) => r.uri.path.endsWith('/4/sync'))
          .toList();
      expect(writes.length, 1);
      expect(writes.single.headers['X-School-Id'], '1');
      expect((writes.single.data as Map)['operation_id'], isNotEmpty);
      expect(tester.takeException(), isNull);
    },
  );
}
