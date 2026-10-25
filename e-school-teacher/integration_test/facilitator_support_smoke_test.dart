import 'package:dio/dio.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool_teacher/app/app.dart';
import 'package:eschool_teacher/app/staffBuildIdentity.dart';
import 'package:eschool_teacher/facilitator/facilitator_repository.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:hive/hive.dart';
import 'package:integration_test/integration_test.dart';

import 'support/support_fixture.dart';
import 'support/smoke.dart' show pumpUntilFound, tapWhenReady;

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets(
    'real counsellor shell saves support and clears withdrawn private data',
    (tester) async {
      expect(StaffBuildIdentity.current.canStartFacilitator, isTrue);
      final fixture = SupportStaffFixture();
      final repo = FacilitatorRepository(
        mode: AuthMode.bearer,
        createDio: () => Dio()..httpClientAdapter = fixture.api,
      );
      await initializeApp(facilitatorRepository: repo);
      await pumpUntilFound(tester, find.text('Facilitator sign-in'));
      Finder field(String label) => find.byWidgetPredicate(
        (w) => w is TextField && w.decoration?.labelText == label,
      );
      await tester.enterText(field('School code'), 'jacaranda');
      await tester.enterText(field('Email or handle'), 'coach@example.test');
      await tester.enterText(field('Password'), 'SyntheticStaff2026');
      await tapWhenReady(tester, find.text('Sign in'));
      await pumpUntilFound(tester, find.text('Your school activities'));
      await tapWhenReady(tester, find.text('Support plans'));
      await pumpUntilFound(tester, find.byType(DropdownButtonFormField<int>));
      await tester.tap(find.byType(DropdownButtonFormField<int>));
      await tester.pumpAndSettle();
      await tester.tap(find.text('Synthetic Learner').last);
      await tester.pumpAndSettle();
      await tester.enterText(field('SIAS stage'), 'SNA1');
      await tester.enterText(field('Support needs'), 'Private support needs');
      await tester.enterText(field('Accommodations'), 'Private full plan');
      FocusManager.instance.primaryFocus?.unfocus();
      await tester.pumpAndSettle();
      final save = find.widgetWithText(FilledButton, 'Save support plan');
      await tester.ensureVisible(save);
      await tester.pumpAndSettle();
      await tester.tap(save);
      await pumpUntilFound(tester, find.textContaining('Current saved plan'));
      expect(fixture.hasPlan, isTrue);
      expect(
        fixture.api.requests
            .where((r) => r.path.contains('/pastoral/support/'))
            .every(
              (r) =>
                  r.headers['X-Account-Id'] == '12' &&
                  r.headers['X-School-Id'] == '1',
            ),
        isTrue,
      );
      expect(Hive.isBoxOpen('authBox'), isFalse);
      fixture.granted = false;
      await tester.runAsync(repo.refresh);
      await tester.pumpAndSettle();
      expect(find.text('Private support needs'), findsNothing);
      await tapWhenReady(tester, find.text('Support plans'));
      await pumpUntilFound(
        tester,
        find.text('No support plans in your current caseload.'),
      );
      expect(find.text('Private support needs'), findsNothing);
      expect(Hive.isBoxOpen('authBox'), isFalse);
      expect(tester.takeException(), isNull);
      await tester.pumpWidget(const SizedBox.shrink());
      repo.dispose();
    },
  );
}
