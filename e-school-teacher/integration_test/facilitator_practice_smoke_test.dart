import 'package:dio/dio.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_teacher/app/app.dart';
import 'package:eschool_teacher/app/staffBuildIdentity.dart';
import 'package:eschool_teacher/facilitator/facilitator_repository.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:hive/hive.dart';
import 'package:integration_test/integration_test.dart';

import 'support/wellbeing_fixture.dart';
import 'support/smoke.dart' show pumpUntilFound, tapWhenReady;

class PracticeStaffFixture extends ClinicalStaffFixture {
  final practice = PracticeProtocolFixture();
  bool practiceGranted = true;
  @override
  Map<String, dynamic> profile() {
    final value = super.profile();
    value['roles'] = ['Teacher'];
    value['features'] = {'daily_practice': practiceGranted};
    return value;
  }

  PracticeStaffFixture() {
    api.routes.addAll(practice.routes());
  }
}

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets(
    'dedicated Teacher profile approves practice without opening legacy auth storage',
    (tester) async {
      expect(StaffBuildIdentity.current.canStartFacilitator, isTrue);
      final fixture = PracticeStaffFixture();
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
      await tapWhenReady(tester, find.text('Practice question bank'));
      await tapWhenReady(tester, find.text('One half as a decimal?'));
      await pumpUntilFound(tester, find.text('Published topic'));
      final explanationField = find.byWidgetPredicate(
        (w) =>
            w is TextField && w.decoration?.labelText == 'Worked explanation',
      );
      await tester.scrollUntilVisible(
        explanationField,
        200,
        scrollable: find.byType(Scrollable).first,
      );
      await tester.enterText(
        explanationField,
        'A staff-approved worked explanation.',
      );
      await tester.scrollUntilVisible(
        find.text('Approve question revision'),
        200,
        scrollable: find.byType(Scrollable).first,
      );
      await tapWhenReady(tester, find.text('Approve question revision'));
      await pumpUntilFound(tester, find.text('Question revision approved.'));
      expect(fixture.practice.lastApproval?['expected_revision'], 1);
      expect(
        fixture.api.requests
            .where((r) => r.path.contains('/learning/practice/'))
            .every(
              (r) =>
                  r.headers['X-School-Id'] == '1' &&
                  r.headers['X-Account-Id'] == '12',
            ),
        isTrue,
      );
      fixture.practiceGranted = false;
      await tester.runAsync(repo.refresh);
      await tester.pumpAndSettle();
      expect(find.text('One half as a decimal?'), findsNothing);
      expect(find.text('Practice question bank'), findsNothing);
      expect(Hive.isBoxOpen('authBox'), isFalse);
      expect(tester.takeException(), isNull);
      await tester.pumpWidget(const SizedBox.shrink());
      repo.dispose();
    },
  );
}
