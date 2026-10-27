import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'support/fixtures.dart';
import 'support/smoke.dart';
import 'support/teaching_fixture.dart';

void main() {
  TeachingSmokeTestBinding();
  testWidgets(
    'Teacher recovers, edits and approves a private three-level draft without publishing marks',
    (tester) async {
      final fixture = TeachingSmokeFixture();
      late final FakeApi api;
      api = await startSmokeApp(tester, {
        ...teacherRoutes(),
        ...fixture.routes(() => api),
        'GET v2/me/context': ok({
          ...academicContextFixture(),
          'user': {
            'id': 31,
            'first_name': 'Synthetic',
            'last_name': 'Teacher',
            'name': 'Synthetic Teacher',
            'email': teacherEmail,
            'image': null,
            'roles': ['Teacher'],
          },
          'feature_flags': {
            'module_switches': true,
            'teaching_assistant': true,
          },
          'modules': [
            for (final key in [
              'school',
              'people',
              'academics',
              'teaching_assistant',
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
      await tapWhenReady(tester, find.text('Teaching assistant'));
      await runTeachingApproval(tester, fixture);
      fixture.granted = false;
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
      await tester.pump();
      expect(find.text('Teacher reviewed browser worksheet'), findsNothing);
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await pumpUntilFound(
        tester,
        find.text('Reopen the teaching workspace to check your access.'),
      );
      expect(find.text('Private browser worksheet'), findsNothing);
      expect(find.text('Teacher reviewed browser worksheet'), findsNothing);
      final reads = api.requests.where(
        (r) => r.uri.path.contains('/teaching-assistant/'),
      );
      expect(reads, isNotEmpty);
      expect(
        reads.every(
          (r) =>
              r.headers['X-School-Id'] == '1' &&
              r.headers['X-Account-Id'] == '31',
        ),
        isTrue,
      );
      expect(tester.takeException(), isNull);
    },
  );
}
