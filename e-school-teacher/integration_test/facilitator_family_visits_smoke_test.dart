import 'package:dio/dio.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool_teacher/app/app.dart';
import 'package:eschool_teacher/facilitator/facilitator_repository.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:hive/hive.dart';
import 'package:integration_test/integration_test.dart';

import 'support/wellbeing_fixture.dart';
import 'support/smoke.dart' show pumpUntilFound, tapWhenReady;

class _Fixture extends ClinicalStaffFixture {
  @override
  Map<String, dynamic> profile() => {
    ...super.profile(),
    'name': 'Synthetic card holder',
    'roles': ['Facilitator'],
    'features': {'groups': false, 'messages': false, 'counselling': false},
  };
}

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets(
    'dedicated staff reads only its calendar and erases it on fresh scope withdrawal',
    (tester) async {
      final fixture = _Fixture(),
          forms = FamilyVisitSmokeFixture(accountId: 12, staff: true);
      fixture.api.routes.addAll(forms.routes);
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
      await tapWhenReady(tester, find.text('Meetings and early collection'));
      await pumpUntilFound(
        tester,
        find.text('Synthetic meeting host · Synthetic private meeting room'),
      );
      expect(find.text('Synthetic current learner'), findsOneWidget);
      expect(find.byKey(const ValueKey('visit-collector')), findsNothing);
      expect(find.text('Book this meeting'), findsNothing);
      forms.allowed = false;
      await tester.runAsync(repo.refresh);
      await tester.pumpAndSettle();
      expect(
        find.text('Synthetic meeting host · Synthetic private meeting room'),
        findsNothing,
      );
      expect(find.byType(FamilyVisitsScreen), findsNothing);
      expect(Hive.isBoxOpen('authBox'), isFalse);
      final requests = fixture.api.requests.where(
        (r) => r.uri.path.contains('/family-visits/'),
      );
      expect(requests, isNotEmpty);
      expect(
        requests.every(
          (r) =>
              r.headers['X-School-Id'] == '1' &&
              r.headers['X-Account-Id'] == '12',
        ),
        isTrue,
      );
      expect(forms.writes, isEmpty);
      expect(tester.takeException(), isNull);
    },
  );
}
