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
import 'support/smoke.dart' show pumpUntilFound, tapWhenReady;

class _Fixture extends ClinicalStaffFixture {
  bool permitted = true;
  @override
  Map<String, dynamic> profile() => {
    ...super.profile(),
    'name': 'Synthetic card holder',
    'roles': ['Facilitator'],
    'features': {
      'cards': permitted,
      'groups': false,
      'messages': false,
      'counselling': false,
    },
  };
}

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets(
    'dedicated staff reads own card without classic auth and removes revoked access',
    (tester) async {
      final fixture = _Fixture(),
          cards = CardSmokeFixture(personId: 12, type: 'staff');
      fixture.api.routes.addAll(cards.routes());
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
      await tapWhenReady(tester, find.text('School identity cards'));
      await tapWhenReady(tester, find.text('Show secure code'));
      await pumpUntilFound(
        tester,
        find.byKey(
          const ValueKey(
            'secure-card-code-8aef50e7-2429-4d2c-a523-668ae436c4d4',
          ),
        ),
      );
      fixture.permitted = false;
      await tester.runAsync(repo.refresh);
      await tester.pumpAndSettle();
      expect(
        find.byKey(
          const ValueKey(
            'secure-card-code-8aef50e7-2429-4d2c-a523-668ae436c4d4',
          ),
        ),
        findsNothing,
      );
      expect(find.text('School identity cards'), findsNothing);
      expect(Hive.isBoxOpen('authBox'), isFalse);
      final reads = fixture.api.requests.where(
        (r) => r.uri.path.contains('/cards/'),
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
