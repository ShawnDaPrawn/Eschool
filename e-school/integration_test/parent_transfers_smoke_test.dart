import 'package:dio/dio.dart';
import 'package:eschool/ui/screens/parentHomeScreen.dart';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';

import 'support/fixtures.dart';
import 'support/smoke.dart';

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets(
    'parent opens account-level transfer consent, submits exact package and erases revoked records',
    (tester) async {
      var revoked = false, approved = false;
      final api = await startSmokeApp(tester, {
        ...parentOfTwoRoutes(),
        'GET v2/me/context': ok({
          ...academicContextFixture(role: 'Parent'),
          'user': {
            'id': 21,
            'first_name': 'Synthetic',
            'last_name': 'Guardian',
            'name': 'Synthetic Guardian',
            'email': parentEmail,
            'image': null,
            'roles': ['Parent'],
          },
          'feature_flags': {'module_switches': true},
          'modules': [
            for (final key in [
              'school',
              'people',
              'academics',
              'school_transfers',
              'web_parent',
            ])
              {'key': key, 'read_only': false},
          ],
          // The transfer route needs the guardian's current account, not an
          // approved child selection. This account deliberately has none here.
          'children': [],
        }),
        'GET v2/transfers/access': (_) => revoked
            ? const FakeResponse({'message': 'Forbidden'}, status: 403)
            : ok(transferAccessFixture()),
        'GET v2/transfers/consents': (_) => revoked
            ? const FakeResponse({'message': 'Forbidden'}, status: 403)
            : ok(
                transferPageFixture(
                  state: approved ? 'consented' : 'awaiting_consent',
                  revision: approved ? 2 : 1,
                ),
              ),
        'GET v2/transfers/consents/7': (_) => revoked
            ? const FakeResponse({'message': 'Forbidden'}, status: 403)
            : ok(
                transferDetailFixture(
                  state: approved ? 'consented' : 'awaiting_consent',
                  revision: approved ? 2 : 1,
                ),
              ),
        'POST v2/transfers/packages/7/consent': (RequestOptions r) {
          final data = Map<String, dynamic>.from(r.data as Map);
          expect(data['fingerprint'], 'a' * 64);
          expect(data['revision'], 1);
          expect(data['version'], 'school-transfer-v1');
          expect(data['confirmation'], true);
          approved = true;
          return ok({'id': 7, 'revision': 2, 'kind': 'package'});
        },
      });
      await tapWhenReady(tester, find.text('Login as Parent'));
      await pumpUntilFound(tester, find.text('Email or handle'));
      await enterField(tester, 0, parentEmail);
      await enterField(tester, 1, password);
      await tapWhenReady(
        tester,
        find.widgetWithText(CustomRoundedButton, 'Sign in'),
      );
      await pumpUntilFound(tester, find.byType(ParentHomeScreen));
      await tapWhenReady(tester, find.text('School transfers'));
      await pumpUntilFound(tester, find.byType(TransferConsentScreen));
      await tapWhenReady(tester, find.text('Synthetic transfer learner'));
      await pumpUntilFound(tester, find.text('Receiving demo school'));
      expect(find.text('0.00%'), findsNothing);
      await tapWhenReady(
        tester,
        find.widgetWithText(
          CheckboxListTile,
          'I reviewed these records, the named receiving school and dates, and consent to this exact package.',
        ),
      );
      await tapWhenReady(
        tester,
        find.widgetWithText(FilledButton, 'Consent to this transfer'),
      );
      await pumpUntilFound(tester, find.text('School transfer change saved'));
      expect(approved, isTrue);
      revoked = true;
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.inactive);
      await tester.pump();
      expect(find.text('Receiving demo school'), findsNothing);
      tester.binding.handleAppLifecycleStateChanged(AppLifecycleState.resumed);
      await pumpUntilFound(
        tester,
        find.text(
          'Transfers are unavailable. Check your guardian access and retry.',
        ),
      );
      expect(find.text('Synthetic shared document.pdf'), findsNothing);
      final calls = api.requests
          .where((r) => r.uri.path.contains('/transfers/'))
          .toList();
      expect(calls, isNotEmpty);
      expect(
        calls.every(
          (r) =>
              r.headers['X-School-Id'] == '1' &&
              r.headers['X-Account-Id'] == '21',
        ),
        isTrue,
      );
      expect(
        calls.any(
          (r) =>
              r.uri.path.contains('/outgoing') ||
              r.uri.path.contains('/incoming'),
        ),
        isFalse,
      );
      expect(tester.takeException(), isNull);
    },
  );
}
