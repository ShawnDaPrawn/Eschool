import 'package:dio/dio.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool_teacher/app/app.dart';
import 'package:eschool_teacher/app/staffBuildIdentity.dart';
import 'package:eschool_teacher/facilitator/facilitator_repository.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:hive/hive.dart';
import 'package:integration_test/integration_test.dart';

import 'support/staff_notice_fixture.dart';
import 'support/smoke.dart' show pumpUntilFound, tapWhenReady;

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets(
    'staff progress notice opens only allocated academics without clinical or legacy privileges',
    (tester) async {
      expect(StaffBuildIdentity.current.canStartFacilitator, isTrue);
      final fixture = ProgressInboxFixture();
      final repository = FacilitatorRepository(
        mode: AuthMode.bearer,
        createDio: () => Dio()..httpClientAdapter = fixture.api,
      );
      await initializeApp(facilitatorRepository: repository);
      await pumpUntilFound(tester, find.text('Facilitator sign-in'));
      Finder field(String label) => find.byWidgetPredicate(
        (w) => w is TextField && w.decoration?.labelText == label,
      );
      await tester.enterText(field('School code'), 'jacaranda');
      await tester.enterText(field('Email or handle'), 'teacher@example.test');
      await tester.enterText(field('Password'), 'SyntheticStaff2026');
      await tapWhenReady(tester, find.text('Sign in'));
      await pumpUntilFound(tester, find.text('Your school activities'));
      await tapWhenReady(tester, find.text('Private staff updates'));
      await pumpUntilFound(tester, find.text('Learning progress updated'));
      await tapWhenReady(tester, find.text('Learning progress updated'));
      await pumpUntilFound(
        tester,
        find.byKey(const ValueKey('learner-staff-9-1')),
      );
      expect(fixture.marked, isTrue);
      await tapWhenReady(
        tester,
        find.byKey(const ValueKey('learner-staff-9-1')),
      );
      await tapWhenReady(tester, find.text('Asha Synthetic').last);
      await tapWhenReady(tester, find.text('Synthetic allocated Teacher').last);
      await pumpUntilFound(
        tester,
        find.text('Private allocated academic observation'),
      );
      expect(
        fixture.api.requests.where(
          (r) =>
              r.path.contains('/caseload') ||
              r.path.startsWith('/api/v2/wellbeing'),
        ),
        isEmpty,
      );
      expect(
        fixture.api.requests
            .where(
              (r) =>
                  r.path.contains('/growth/progress/') ||
                  r.path.contains('/staff/private-notices'),
            )
            .every(
              (r) =>
                  r.headers['X-School-Id'] == '1' &&
                  r.headers['X-Account-Id'] == '12',
            ),
        isTrue,
      );
      fixture.inboxGranted = false;
      await tester.runAsync(repository.refresh);
      await tester.pumpAndSettle();
      expect(find.text('Private allocated academic observation'), findsNothing);
      expect(find.text('Private staff updates'), findsNothing);
      expect(Hive.isBoxOpen('authBox'), isFalse);
      expect(tester.takeException(), isNull);
      await tester.pumpWidget(const SizedBox.shrink());
      repository.dispose();
    },
  );
  testWidgets(
    'staff discipline notice opens only its authorized follow-up and erases withdrawn access',
    (tester) async {
      expect(StaffBuildIdentity.current.canStartFacilitator, isTrue);
      final fixture = DisciplineInboxFixture();
      final repository = FacilitatorRepository(
        mode: AuthMode.bearer,
        createDio: () => Dio()..httpClientAdapter = fixture.api,
      );
      await initializeApp(facilitatorRepository: repository);
      await pumpUntilFound(tester, find.text('Facilitator sign-in'));
      Finder field(String label) => find.byWidgetPredicate(
        (w) => w is TextField && w.decoration?.labelText == label,
      );
      await tester.enterText(field('School code'), 'jacaranda');
      await tester.enterText(field('Email or handle'), 'teacher@example.test');
      await tester.enterText(field('Password'), 'SyntheticStaff2026');
      await tapWhenReady(tester, find.text('Sign in'));
      await pumpUntilFound(tester, find.text('Your school activities'));
      await tapWhenReady(tester, find.text('Private staff updates'));
      await pumpUntilFound(tester, find.text('Discipline follow-up updated'));
      await tapWhenReady(tester, find.text('Discipline follow-up updated'));
      await pumpUntilFound(tester, find.text('Synthetic learner'));
      expect(fixture.marked, isTrue);
      await tapWhenReady(tester, find.text('Synthetic learner'));
      await pumpUntilFound(
        tester,
        find.text('Private synthetic discipline follow-up'),
      );
      expect(
        fixture.api.requests
            .where(
              (r) =>
                  r.path.contains('/discipline/') ||
                  r.path.contains('/staff/private-notices'),
            )
            .every(
              (r) =>
                  r.headers['X-School-Id'] == '1' &&
                  r.headers['X-Account-Id'] == '12',
            ),
        isTrue,
      );
      expect(
        fixture.api.requests.where(
          (r) => r.path.contains('/caseload') || r.path.contains('/wellbeing'),
        ),
        isEmpty,
      );
      fixture.available = false;
      await tester.runAsync(repository.refresh);
      await tester.pumpAndSettle();
      expect(find.text('Private synthetic discipline follow-up'), findsNothing);
      expect(find.text('Private staff updates'), findsOneWidget);
      expect(Hive.isBoxOpen('authBox'), isFalse);
      expect(tester.takeException(), isNull);
      await tester.pumpWidget(const SizedBox.shrink());
      repository.dispose();
    },
  );
}
