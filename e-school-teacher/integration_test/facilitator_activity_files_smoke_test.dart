import 'package:dio/dio.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool_teacher/app/app.dart';
import 'package:eschool_teacher/facilitator/facilitator_repository.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:hive/hive.dart';
import 'package:integration_test/integration_test.dart';

import 'support/wellbeing_fixture.dart';
import 'support/activity_file_flow.dart';
import 'support/smoke.dart' show pumpUntilFound, tapWhenReady;

class _Fixture extends ClinicalStaffFixture {
  bool permitted = true;
  @override
  Map<String, dynamic> profile() => {
    ...super.profile(),
    'name': 'Synthetic card holder',
    'roles': ['Facilitator'],
    'features': {
      'family_forms': false,
      'groups': permitted,
      'messages': false,
      'counselling': false,
    },
  };
}

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets(
    'dedicated facilitator reads and removes private group documents without teacher bootstrap',
    (tester) async {
      final fixture = _Fixture(),
          files = MaterialFileClientFixture(staff: true);
      fixture.api.routes.addAll(files.routes());
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
      await verifyMaterialFiles(
        tester,
        files,
        fixture.api,
        account: 12,
        bearer: true,
        resetsRoute: true,
      );
      fixture.permitted = false;
      await tester.runAsync(repo.refresh);
      await tester.pumpAndSettle();
      expect(find.text('Activity groups'), findsNothing);
      expect(Hive.isBoxOpen('authBox'), isFalse);
      expect(tester.takeException(), isNull);
    },
  );
}
