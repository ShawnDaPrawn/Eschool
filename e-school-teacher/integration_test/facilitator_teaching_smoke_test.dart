import 'package:dio/dio.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool_teacher/app/app.dart';
import 'package:eschool_teacher/app/staffBuildIdentity.dart';
import 'package:eschool_teacher/facilitator/facilitator_repository.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:hive/hive.dart';
import 'package:integration_test/integration_test.dart';

import 'support/smoke.dart' show pumpUntilFound, tapWhenReady;
import 'support/teaching_fixture.dart';
import 'support/wellbeing_fixture.dart';

class _TeachingStaff extends ClinicalStaffFixture {
  bool permitted = true;
  @override
  Map<String, dynamic> profile() => {
    ...super.profile(),
    'name': 'Synthetic allocated Teacher',
    'roles': ['Facilitator', 'Teacher'],
    'features': {
      'teaching_assistant': permitted,
      'counselling': false,
      'groups': false,
      'messages': false,
    },
  };
}

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets(
    'dedicated Facilitator Teacher approves its own draft and erases it on feature withdrawal',
    (tester) async {
      expect(StaffBuildIdentity.current.canStartFacilitator, isTrue);
      final staff = _TeachingStaff(),
          fixture = TeachingSmokeFixture(reader: 12);
      staff.api.routes.addAll(fixture.routes(() => staff.api));
      final repository = FacilitatorRepository(
        mode: AuthMode.bearer,
        createDio: () => Dio()..httpClientAdapter = staff.api,
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
      await tapWhenReady(tester, find.text('Teaching assistant'));
      await runTeachingApproval(tester, fixture);
      staff.permitted = false;
      fixture.granted = false;
      await tester.runAsync(repository.refresh);
      await tester.pumpAndSettle();
      expect(find.text('Teacher reviewed browser worksheet'), findsNothing);
      expect(find.text('Teaching assistant'), findsNothing);
      expect(Hive.isBoxOpen('authBox'), isFalse);
      final reads = staff.api.requests.where(
        (r) => r.uri.path.contains('/teaching-assistant/'),
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
      expect(tester.takeException(), isNull);
    },
  );
}
