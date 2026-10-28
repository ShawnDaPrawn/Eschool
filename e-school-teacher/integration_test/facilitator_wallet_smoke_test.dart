import 'package:dio/dio.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool_teacher/app/app.dart';
import 'package:eschool_teacher/app/staffBuildIdentity.dart';
import 'package:eschool_teacher/facilitator/facilitator_repository.dart';
import 'package:eschool_wallet/testing.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';

import 'support/wellbeing_fixture.dart';
import 'support/commerce_journey.dart';
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
      'vendors': permitted,
      'wallet_payments': permitted,
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
      expect(StaffBuildIdentity.current.canStartFacilitator, isTrue);
      final fixture = _Staff();
      final withdrawals = <Map<String, dynamic>>[];
      final index = walletIndexFixture(viewer: 12);
      fixture.api.routes.addAll({
        'GET v2/wallets': {
          'error': false,
          'message': '',
          'code': 200,
          'data': index,
        },
        'GET v2/wallets/transfers': {
          'error': false,
          'message': '',
          'code': 200,
          'data': walletTransferPageFixture(viewer: 12),
        },
        'GET v2/wallets/withdrawals': (RequestOptions _) => {
          'error': false,
          'message': '',
          'code': 200,
          'data': walletWithdrawalPageFixture(
            viewer: 12,
            configured: true,
            items: withdrawals,
          ),
        },
        'POST v2/wallets/withdrawals': (RequestOptions q) {
          withdrawals.add(
            walletWithdrawalReceiptFixture(
              operation: (q.data as Map)['operation_id'] as String,
              viewer: 12,
              method: 'cash',
              cents: 1000,
            ),
          );
          ((index['owners'] as List).first['wallets'] as List)
                  .first['balance_cents'] =
              1000;
          return {
            'error': false,
            'message': '',
            'code': 200,
            'data': withdrawals.single,
          };
        },
        'GET v2/wallets/deposits': {
          'error': false,
          'message': '',
          'code': 200,
          'data': walletDepositPageFixture(viewer: 12),
        },
        'GET v2/wallets/6/desk-topups': {
          'error': false,
          'message': '',
          'code': 200,
          'data': walletDeskReceiptsFixture(owner: 12, viewer: 12),
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
      await tapWhenReady(tester, find.text('View desk top-up receipts'));
      await pumpUntilFound(tester, find.text('WT-201'));
      expect(find.text('Awaiting independent approval'), findsOneWidget);
      await exerciseWithdrawal(tester, withdrawals);
      expect(
        fixture.api.requests.where(
          (r) =>
              r.method == 'POST' && r.uri.path.endsWith('/wallets/withdrawals'),
        ),
        hasLength(1),
      );
      await exerciseCommerce(tester, fixture.api, actor: 12);
      fixture.permitted = false;
      await tester.runAsync(repo.refresh);
      await tester.pumpAndSettle();
      expect(find.text('Staff Synthetic'), findsNothing);
      expect(find.text('WT-201'), findsNothing);
      expect(find.text('WW-601'), findsNothing);
      expect(find.text('WW-PAY-801'), findsNothing);
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

Future<void> exerciseWithdrawal(
  WidgetTester tester,
  List<Map<String, dynamic>> requests,
) async {
  await tapWhenReady(tester, find.text('Adult wallet withdrawals'));
  final amount = find.byWidgetPredicate(
    (w) => w is TextField && w.decoration?.labelText == 'Withdrawal amount',
  );
  await pumpUntilFound(tester, amount);
  await tester.ensureVisible(amount);
  await tester.enterText(amount, '10.00');
  await pumpUntilFound(tester, find.text('Cash pickup slot'));
  final selector = find.byType(DropdownButtonFormField<int>).last;
  await tester.ensureVisible(selector);
  await tester.tap(selector);
  await tester.pumpAndSettle();
  await tester.tap(
    find.text('2026-11-01 10:00 – 2026-11-01 11:00 Africa/Johannesburg').last,
  );
  await tester.pumpAndSettle();
  await tapWhenReady(
    tester,
    find.text(
      'I reviewed the amount and my own pickup or bank details. Reserve these funds for Finance approval; this does not send a payment.',
    ),
  );
  await tapWhenReady(tester, find.text('Request wallet withdrawal'));
  await tester.pumpAndSettle();
  Future<void> expanded(String text) async {
    if (find.text(text).evaluate().isEmpty) {
      await tapWhenReady(tester, find.text('Adult wallet withdrawals'));
    }
    await pumpUntilFound(tester, find.text(text));
  }

  await expanded('WW-601');
  expect(find.text('Awaiting Finance approval'), findsOneWidget);
  requests.single['status'] = 'approved';
  requests.single['processed_at'] = '2026-06-15T09:01:00.000000Z';
  await tester.tap(find.byTooltip('Refresh wallets'));
  await tester.pumpAndSettle();
  await expanded('Approved, awaiting actual payment');
  expect(find.text('Cancel unpaid withdrawal'), findsNothing);
  requests.single['status'] = 'paid';
  requests.single['paid_on'] = '2026-06-15';
  requests.single['payout_reference'] = 'WW-PAY-801';
  await tester.tap(find.byTooltip('Refresh wallets'));
  await tester.pumpAndSettle();
  await expanded('WW-PAY-801');
  expect(find.text('Paid'), findsOneWidget);
}
