import 'package:dio/dio.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool_teacher/app/app.dart';
import 'package:eschool_teacher/facilitator/facilitator_repository.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:hive/hive.dart';
import 'package:integration_test/integration_test.dart';

import 'support/wellbeing_fixture.dart';
import 'support/my_grade_fixture.dart';
import 'support/smoke.dart' show pumpUntilFound, tapWhenReady;

class _Staff extends ClinicalStaffFixture {
  bool permitted = true;
  @override
  Map<String, dynamic> profile() => {
    ...super.profile(),
    'name': 'Synthetic grade head',
    'roles': ['Facilitator'],
    'features': {
      'my_grade': permitted,
      'counselling': false,
      'groups': false,
      'messages': false,
    },
  };
}

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets(
    'dedicated staff opens its authorized grade and removes a revoked private hub',
    (tester) async {
      final fixture = _Staff(), grade = MyGradeSmokeFixture();
      fixture.api.routes.addAll(grade.routes());
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
      await tapWhenReady(tester, find.text('My grade'));
      await pumpUntilFound(tester, find.text('Synthetic grade learner'));
      fixture.permitted = false;
      grade.granted = false;
      await tester.runAsync(repo.refresh);
      await tester.pumpAndSettle();
      expect(find.text('Synthetic grade learner'), findsNothing);
      expect(find.text('My grade'), findsNothing);
      expect(Hive.isBoxOpen('authBox'), isFalse);
      final reads = fixture.api.requests.where(
        (r) => r.uri.path.contains('/my-grade/'),
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
