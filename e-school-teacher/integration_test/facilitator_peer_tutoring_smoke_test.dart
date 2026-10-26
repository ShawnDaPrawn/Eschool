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
import 'support/peer_smoke.dart';

class _Fixture extends ClinicalStaffFixture {
  @override
  Map<String, dynamic> profile() => {
    ...super.profile(),
    'name': 'Synthetic supervised tutor teacher',
    'roles': ['Facilitator', 'Teacher'],
    'features': {'groups': false, 'messages': false, 'counselling': false},
  };
}

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets(
    'dedicated staff supervises tutoring without borrowing Teacher auth storage',
    (tester) async {
      final fixture = _Fixture(), forms = PeerSmokeFixture();
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
      await exercisePeerSupervisor(tester, forms);
      forms.allowed = false;
      await tester.runAsync(repo.refresh);
      await tester.pumpAndSettle();
      expect(find.text('Synthetic supervised room'), findsNothing);
      expect(find.byType(PeerTutoringScreen), findsNothing);
      expect(Hive.isBoxOpen('authBox'), isFalse);
      final requests = fixture.api.requests.where(
        (r) => r.uri.path.contains('/peer-tutoring/'),
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
      expect(forms.effects, 3);
      expect(
        requests
            .where((r) => r.method == 'POST')
            .every((r) => r.queryParameters.isEmpty),
        isTrue,
      );
      expect(tester.takeException(), isNull);
    },
  );
}
