import 'package:dio/dio.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:eschool_wallet/testing.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';

import 'support/fixtures.dart';
import 'support/commerce_journey.dart';
import 'support/smoke.dart';

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets('teacher reads own wallet without fee permissions', (
    tester,
  ) async {
    var allowed = true;
    final withdrawals = <Map<String, dynamic>>[];
    final index = walletIndexFixture(viewer: 12);
    Object response(Object data) => allowed
        ? ok(data)
        : FakeResponse({
            'error': true,
            'message': 'Forbidden',
            'code': 403,
          }, status: 403);
    final api = await startSmokeApp(tester, {
      ...teacherRoutes(),
      'GET v2/me/context': ok({
        ...academicContextFixture(),
        'user': {
          'id': 12,
          'first_name': 'Synthetic',
          'last_name': 'Teacher',
          'name': 'Synthetic Teacher',
          'email': teacherEmail,
          'image': null,
          'roles': ['Teacher'],
        },
        'feature_flags': {'module_switches': true},
        'modules': [
          for (final key in [
            'school',
            'people',
            'academics',
            'wallet',
            'vendors',
            'wallet_payments',
            'web_teacher',
          ])
            {'key': key, 'read_only': false},
        ],
      }),
      'GET v2/wallets': (RequestOptions _) => response(index),
      'GET v2/wallets/transfers': (RequestOptions _) =>
          response(walletTransferPageFixture(viewer: 12)),
      'GET v2/wallets/withdrawals': (RequestOptions _) => response(
        walletWithdrawalPageFixture(
          viewer: 12,
          configured: true,
          items: withdrawals,
        ),
      ),
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
        return response(withdrawals.single);
      },
      'GET v2/wallets/deposits': (RequestOptions _) =>
          response(walletDepositPageFixture(viewer: 12)),
      'GET v2/wallets/6/statement': (RequestOptions _) =>
          response(walletStatementFixture(owner: 12, viewer: 12)),
      'GET v2/wallets/6/desk-topups': (RequestOptions _) =>
          response(walletDeskReceiptsFixture(owner: 12, viewer: 12)),
    });
    await pumpUntilFound(tester, find.text('Email or handle'));
    await enterField(tester, 0, teacherEmail);
    await enterField(tester, 1, password);
    await tapWhenReady(
      tester,
      find.widgetWithText(CustomRoundedButton, 'Sign in'),
    );
    await tapWhenReady(tester, find.text('Wallets'));
    await tapWhenReady(tester, find.text('Open wallet statement'));
    await pumpUntilFound(tester, find.text('Wallet balance'));
    expect(find.text('Staff Synthetic'), findsOneWidget);
    expect(find.text('Edit spending controls'), findsOneWidget);
    await tapWhenReady(tester, find.text('View desk top-up receipts'));
    await pumpUntilFound(tester, find.text('WT-201'));
    expect(find.text('Awaiting independent approval'), findsOneWidget);
    await exerciseWithdrawal(tester, withdrawals);
    expect(
      api.requests.where(
        (r) =>
            r.method == 'POST' && r.uri.path.endsWith('/wallets/withdrawals'),
      ),
      hasLength(1),
    );
    await exerciseCommerce(tester, api, actor: 12);
    allowed = false;
    await tester.tap(find.byTooltip('Refresh wallets'));
    await pumpUntilFound(
      tester,
      find.text('Your wallet access changed. Reopen Wallets.'),
    );
    expect(find.text('Staff Synthetic'), findsNothing);
    expect(find.text('WT-201'), findsNothing);
    expect(find.text('WW-601'), findsNothing);
    expect(find.text('WW-PAY-801'), findsNothing);
    expect(
      api.requests
          .where((q) => q.uri.path.contains('/v2/wallets'))
          .every((q) => q.headers['X-Account-Id'].toString() == '12'),
      true,
    );
    expect(tester.takeException(), isNull);
  });
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
