import 'package:dio/dio.dart';
import 'package:eschool/ui/screens/home/homeScreen.dart';
import 'package:eschool/ui/screens/parentHomeScreen.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:eschool_wallet/testing.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter/material.dart';
import 'package:integration_test/integration_test.dart';

import 'support/fixtures.dart';
import 'support/smoke.dart';

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  for (final parent in [false, true]) {
    testWidgets(
      '${parent ? 'parent' : 'learner'} reads private wallet independently of fees',
      (tester) async {
        final actor = parent ? 21 : 11;
        var allowed = true;
        final transfers = <Map<String, dynamic>>[];
        final deposits = <Map<String, dynamic>>[];
        final withdrawals = <Map<String, dynamic>>[];
        final statement = walletStatementFixture();
        statement['owner_user_id'] = 11;
        statement['can_manage_limits'] = parent;
        final index = walletIndexFixture(viewer: parent ? 21 : 31);
        index['actor_user_id'] = actor;
        final child = (index['owners'] as List).last as Map;
        child['owner_user_id'] = 11;
        child['is_self'] = !parent;
        (child['wallets'] as List).first['owner_user_id'] = 11;
        (child['wallets'] as List).first['can_manage_limits'] = parent;
        final receipts = walletDeskReceiptsFixture();
        receipts['owner_user_id'] = 11;
        receipts['actor_user_id'] = actor;
        Object response(Object data) => allowed
            ? ok(data)
            : FakeResponse({
                'error': true,
                'message': 'Forbidden',
                'code': 403,
              }, status: 403);
        final api = await startSmokeApp(tester, {
          ...(parent ? parentOfTwoRoutes() : learnerRoutes()),
          'GET v2/me/context': ok({
            ...academicContextFixture(role: parent ? 'Parent' : 'Student'),
            'user': {
              'id': actor,
              'first_name': 'Synthetic',
              'last_name': parent ? 'Parent' : 'Learner',
              'name': 'Synthetic User',
              'email': parent ? parentEmail : 'learner@example.test',
              'image': null,
              'roles': [parent ? 'Parent' : 'Student'],
            },
            'feature_flags': {'module_switches': true},
            'modules': [
              for (final key in [
                'school',
                'people',
                'academics',
                'wallet',
                'web_parent',
                'web_learner',
              ])
                {'key': key, 'read_only': false},
            ],
            if (parent)
              'children': [
                {
                  'id': 11,
                  'user_id': 11,
                  'first_name': 'Asha',
                  'last_name': 'Synthetic',
                  'name': 'Asha Synthetic',
                  'approved': true,
                  'image': null,
                  'school': {
                    'id': 1,
                    'name': 'Smoke Test School',
                    'short_name': null,
                    'crest_url': null,
                  },
                },
              ],
          }),
          'GET v2/wallets': (RequestOptions _) => response(index),
          'GET v2/wallets/transfers': (RequestOptions _) => response(
            walletTransferPageFixture(
              viewer: actor,
              canTransfer: parent,
              items: transfers,
            ),
          ),
          'POST v2/wallets/transfers': (RequestOptions q) {
            final body = q.data as Map;
            final receipt = walletTransferReceiptFixture(
              operation: body['operation_id'] as String,
              childOwner: 11,
            );
            transfers.add(receipt);
            (child['wallets'] as List).first['balance_cents'] = 6000;
            statement['balance_cents'] = 6000;
            (statement['entries']['items'] as List).first['change_cents'] =
                6000;
            (statement['entries']['items'] as List)
                    .first['balance_after_cents'] =
                6000;
            ((index['owners'] as List).first['wallets'] as List)
                    .first['balance_cents'] =
                1000;
            return response(receipt);
          },
          'GET v2/wallets/withdrawals': (RequestOptions _) => response(
            walletWithdrawalPageFixture(
              viewer: actor,
              configured: parent,
              items: withdrawals,
            ),
          ),
          'POST v2/wallets/withdrawals': (RequestOptions q) {
            withdrawals.add(
              walletWithdrawalReceiptFixture(
                operation: (q.data as Map)['operation_id'] as String,
                viewer: actor,
                method: 'cash',
                cents: 1000,
              ),
            );
            ((index['owners'] as List).first['wallets'] as List)
                    .first['balance_cents'] =
                1950;
            return response(withdrawals.single);
          },
          'GET v2/wallets/deposits': (RequestOptions _) => response(
            walletDepositPageFixture(
              viewer: actor,
              configured: parent,
              items: deposits,
            ),
          ),
          'POST v2/wallets/deposits': (RequestOptions q) {
            final body = q.data as Map;
            final receipt = walletDepositReceiptFixture(
              operation: body['operation_id'] as String,
              reference: body['source_reference'] as String,
              paidOn: body['paid_on'] as String,
            );
            deposits.add(receipt);
            return response(receipt);
          },
          'GET v2/wallets/7/statement': (RequestOptions _) =>
              response(statement),
          'GET v2/wallets/7/desk-topups': (RequestOptions _) =>
              response(receipts),
        });
        await tapWhenReady(
          tester,
          find.text(parent ? 'Login as Parent' : 'Login as Student'),
        );
        await pumpUntilFound(
          tester,
          find.text(
            parent ? 'Email or handle' : 'Student number, GR number or handle',
          ),
        );
        await enterField(tester, 0, parent ? parentEmail : grNumber);
        await enterField(tester, 1, password);
        await tapWhenReady(
          tester,
          find.widgetWithText(CustomRoundedButton, 'Sign in'),
        );
        await pumpUntilFound(
          tester,
          find.byType(parent ? ParentHomeScreen : HomeScreen),
        );
        await tapWhenReady(tester, find.text('Wallets'));
        await openWalletStatement(tester, 7);
        await pumpUntilFound(tester, find.text('Wallet balance'));
        expect(find.text('Asha Synthetic'), findsWidgets);
        expect(
          find.text('Edit spending controls'),
          parent ? findsOneWidget : findsNothing,
        );
        expect(find.text('ZAR 50.00'), findsWidgets);
        await tapWhenReady(tester, find.text('View desk top-up receipts'));
        await pumpUntilFound(tester, find.text('WT-201'));
        expect(find.text('Awaiting independent approval'), findsOneWidget);
        if (parent) {
          final amount = find.byWidgetPredicate(
            (w) =>
                w is TextField &&
                w.decoration?.labelText == 'Wallet transfer amount',
          );
          await tester.ensureVisible(amount);
          await tester.enterText(amount, '10.00');
          await tapWhenReady(
            tester,
            find.text(
              'I reviewed the source, recipient and amount for this wallet transfer.',
            ),
          );
          await tapWhenReady(tester, find.text('Move wallet money'));
          await pumpUntilFound(tester, find.text('WX-301'));
          expect(
            api.requests.where(
              (q) =>
                  q.method == 'POST' &&
                  q.uri.path.endsWith('/wallets/transfers'),
            ),
            hasLength(1),
          );
          await openWalletStatement(tester, 7);
          await pumpUntilFound(tester, find.text('ZAR 60.00'));
          await tapWhenReady(tester, find.text('Parent wallet deposits'));
          await pumpUntilFound(
            tester,
            find.byType(DropdownButtonFormField<String>),
          );
          await tester.ensureVisible(
            find.byType(DropdownButtonFormField<String>).last,
          );
          await tester.tap(find.byType(DropdownButtonFormField<String>).last);
          await tester.pumpAndSettle();
          await tester.tap(find.text('EFT already paid').last);
          await tester.pumpAndSettle();
          Future<void> enterDeposit(String label, String value) async {
            final field = find.byWidgetPredicate(
              (w) => w is TextField && w.decoration?.labelText == label,
            );
            await tester.ensureVisible(field);
            await tester.enterText(field, value);
          }

          await enterDeposit('Amount already paid', '20.00');
          await enterDeposit(
            'Cash receipt or bank transaction reference',
            'PARENT-DEPOSIT-001',
          );
          await enterDeposit('Paid date (YYYY-MM-DD)', '2026-06-15');
          await enterDeposit(
            'Private receipt details for Finance',
            'Private synthetic native parent proof',
          );
          await tapWhenReady(
            tester,
            find.text(
              'I already paid this deposit and understand that Finance must verify it before crediting my wallet.',
            ),
          );
          await tapWhenReady(
            tester,
            find.text('Submit deposit for verification'),
          );
          await tester.pumpAndSettle();
          if (find.text('WD-401').evaluate().isEmpty) {
            await tapWhenReady(tester, find.text('Parent wallet deposits'));
          }
          await pumpUntilFound(tester, find.text('WD-401'));
          expect(find.text('Awaiting Finance verification'), findsOneWidget);
          expect(
            find.text('Private synthetic native parent proof'),
            findsNothing,
          );
          expect(
            ((index['owners'] as List).first['wallets'] as List)
                .first['balance_cents'],
            1000,
          );
          final original = deposits.single;
          deposits[0] = walletDepositReceiptFixture(
            operation: original['operation_id'] as String,
            status: 'verified',
            verifiedCents: 1950,
          );
          ((index['owners'] as List).first['wallets'] as List)
                  .first['balance_cents'] =
              2950;
          await tester.tap(find.byTooltip('Refresh wallets'));
          await tester.pumpAndSettle();
          if (find.text('WP-501').evaluate().isEmpty) {
            await tapWhenReady(tester, find.text('Parent wallet deposits'));
          }
          await pumpUntilFound(tester, find.text('WP-501'));
          expect(
            find.text('Actual verified amount: ZAR 19.50'),
            findsOneWidget,
          );
          await exerciseWithdrawal(tester, withdrawals);
          expect(
            api.requests.where(
              (r) =>
                  r.method == 'POST' &&
                  r.uri.path.endsWith('/wallets/withdrawals'),
            ),
            hasLength(1),
          );
        }
        allowed = false;
        await tester.tap(find.byTooltip('Refresh wallets'));
        await pumpUntilFound(
          tester,
          find.text('Your wallet access changed. Reopen Wallets.'),
        );
        expect(find.text('Asha Synthetic'), findsNothing);
        expect(find.text('WT-201'), findsNothing);
        expect(find.text('WX-301'), findsNothing);
        expect(find.text('WD-401'), findsNothing);
        expect(find.text('WP-501'), findsNothing);
        expect(find.text('WW-601'), findsNothing);
        expect(find.text('WW-PAY-801'), findsNothing);
        final reads = api.requests.where(
          (q) => q.uri.path.contains('/v2/wallets'),
        );
        expect(reads, isNotEmpty);
        for (final q in reads) {
          expect(q.headers['X-School-Id'].toString(), '1');
          expect(q.headers['X-Account-Id'].toString(), '$actor');
        }
        expect(tester.takeException(), isNull);
      },
    );
  }
}

Future<void> exerciseWithdrawal(
  WidgetTester tester,
  List<Map<String, dynamic>> requests,
) async {
  await tester.scrollUntilVisible(
    find.text('Adult wallet withdrawals'),
    160,
    scrollable: find
        .descendant(
          of: find.byKey(const ValueKey('wallet-screen-scroll')),
          matching: find.byType(Scrollable),
        )
        .first,
  );
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

Future<void> openWalletStatement(WidgetTester tester, int wallet) async {
  await pumpUntilFound(tester, find.text('Open wallet statement'));
  final target = find.byKey(ValueKey('wallet-statement-$wallet'));
  await tester.scrollUntilVisible(
    target,
    160,
    scrollable: find
        .descendant(
          of: find.byKey(const ValueKey('wallet-screen-scroll')),
          matching: find.byType(Scrollable),
        )
        .first,
  );
  await tapWhenReady(tester, target);
}
