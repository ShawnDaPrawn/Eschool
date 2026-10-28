import 'package:dio/dio.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool_teacher/app/app.dart';
import 'package:eschool_teacher/facilitator/facilitator_repository.dart';
import 'package:eschool_wallet/testing.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';

import 'support/wellbeing_fixture.dart';
import 'support/smoke.dart' show pumpUntilFound, tapWhenReady;

class _Staff extends ClinicalStaffFixture {
  bool permitted = true;
  @override
  Map<String, dynamic> profile() => {
    ...super.profile(),
    'name': 'Synthetic wallet facilitator',
    'roles': ['Facilitator'],
    'features': {
      'wallet': permitted,
      'groups': false,
      'messages': false,
      'counselling': false,
    },
  };
}

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets(
    'dedicated facilitator uses only own wallet and clears it on refresh',
    (tester) async {
      final fixture = _Staff();
      fixture.api.routes.addAll({
        'GET v2/wallets': {
          'error': false,
          'message': '',
          'code': 200,
          'data': walletIndexFixture(viewer: 12),
        },
        'GET v2/wallets/6/statement': {
          'error': false,
          'message': '',
          'code': 200,
          'data': walletStatementFixture(owner: 12, viewer: 12),
        },
      });
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
      await tapWhenReady(tester, find.text('Wallets'));
      await tapWhenReady(tester, find.text('Open wallet statement'));
      await pumpUntilFound(tester, find.text('Staff Synthetic'));
      fixture.permitted = false;
      await tester.runAsync(repo.refresh);
      await tester.pumpAndSettle();
      expect(find.text('Staff Synthetic'), findsNothing);
      expect(find.text('Wallets'), findsNothing);
      expect(
        fixture.api.requests
            .where((q) => q.uri.path.contains('/v2/wallets'))
            .every((q) => q.headers['X-Account-Id'].toString() == '12'),
        true,
      );
      expect(tester.takeException(), isNull);
    },
  );
}
