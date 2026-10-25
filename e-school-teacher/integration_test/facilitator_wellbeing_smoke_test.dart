import 'package:dio/dio.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool_teacher/app/app.dart';
import 'package:eschool_teacher/app/staffBuildIdentity.dart';
import 'package:eschool_teacher/facilitator/facilitator_repository.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:hive/hive.dart';
import 'package:integration_test/integration_test.dart';

import 'support/wellbeing_fixture.dart';
import 'support/smoke.dart' show pumpUntilFound, tapWhenReady;

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets(
    'real counsellor shell writes private case and erases withdrawn scope',
    (tester) async {
      expect(StaffBuildIdentity.current.canStartFacilitator, isTrue);
      final fixture = ClinicalStaffFixture(),
          repo = FacilitatorRepository(
            mode: AuthMode.bearer,
            createDio: () => Dio()..httpClientAdapter = fixture.api,
          );
      await initializeApp(facilitatorRepository: repo);
      await pumpUntilFound(tester, find.text('Facilitator sign-in'));
      Finder field(String text) => find.byWidgetPredicate(
        (w) => w is TextField && w.decoration?.labelText == text,
      );
      Future<void> seek(Finder target) async {
                FocusManager.instance.primaryFocus?.unfocus();
        await tester.pumpAndSettle();
        final scroll = find.byType(Scrollable).first;
        await tester.drag(scroll, const Offset(0, 100000));
        await tester.pumpAndSettle();
        for (int i = 0; target.evaluate().isEmpty && i < 30; i++) {
          await tester.drag(scroll, const Offset(0, -300));
          await tester.pumpAndSettle();
        }
        await tester.ensureVisible(target);
        await tester.pumpAndSettle();
      }

      await tester.enterText(field('School code'), 'jacaranda');
      await tester.enterText(field('Email or handle'), 'coach@example.test');
      await tester.enterText(field('Password'), 'SyntheticStaff2026');
      await tapWhenReady(tester, find.text('Sign in'));
      await pumpUntilFound(tester, find.text('Your school activities'));
      await tapWhenReady(tester, find.text('Incidents and counselling'));
      await pumpUntilFound(tester, find.text('Synthetic Case Learner'));
      await tester.tap(find.text('Synthetic Case Learner'));
      await tester.pumpAndSettle();
      await seek(field('Private case note'));
      await tester.enterText(
        field('Private case note'),
        'Private clinical browser update',
      );
      final save = find.widgetWithText(FilledButton, 'Save private case entry');
      await seek(save);
      await tester.tap(save);
      await tester.pumpAndSettle();
      await seek(find.text('Private clinical browser update'));
      expect(find.text('Private clinical browser update'), findsOneWidget);
      expect(
        fixture.api.requests
            .where((r) => r.path.contains('/wellbeing/'))
            .every(
              (r) =>
                  r.headers['X-Account-Id'] == '12' &&
                  r.headers['X-School-Id'] == '1',
            ),
        true,
      );
      expect(Hive.isBoxOpen('authBox'), false);
      fixture.granted = false;
      await tester.runAsync(repo.refresh);
      await tester.pumpAndSettle();
      expect(find.text('Private clinical browser update'), findsNothing);
      await tapWhenReady(tester, find.text('Incidents and counselling'));
      await pumpUntilFound(
        tester,
        find.text('No current safeguarding or counselling assignments.'),
      );
      expect(find.text('Private clinical browser update'), findsNothing);
      expect(Hive.isBoxOpen('authBox'), false);
      expect(tester.takeException(), isNull);
      await tester.pumpWidget(const SizedBox.shrink());
      repo.dispose();
    },
  );
}
