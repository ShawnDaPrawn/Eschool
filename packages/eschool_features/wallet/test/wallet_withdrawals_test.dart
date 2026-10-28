import 'package:dio/dio.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:eschool_wallet/eschool_wallet.dart';
import 'package:eschool_wallet/testing.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';

Map<String, Object?> ok(Object data) => {
  'error': false,
  'message': '',
  'data': data,
  'code': 200,
};

class WithdrawalServer {
  WithdrawalServer({this.actor = 21}) {
    api.routes.addAll({
      'GET v2/wallets': (RequestOptions _) {
        final row = walletIndexFixture(viewer: actor);
        ((row['owners'] as List).first['wallets'] as List)
            .first['balance_cents'] = reserved
            ? 50000 - requestedCents
            : 50000;
        return ok(row);
      },
      'GET v2/wallets/transfers': ok(walletTransferPageFixture(viewer: actor)),
      'GET v2/wallets/deposits': ok(walletDepositPageFixture(viewer: actor)),
      'GET v2/wallets/withdrawals': (RequestOptions _) => ok(
        walletWithdrawalPageFixture(
          viewer: actor,
          configured: true,
          items: intent == null ? [] : [receipt()],
        ),
      ),
      'POST v2/wallets/withdrawals': (RequestOptions request) {
        writes++;
        intent = Map<String, dynamic>.from(request.data as Map);
        reserved = true;
        final key = intent!['operation_id'] as String;
        api.routes['POST v2/wallets/operations/$key/resolve'] = ok({
          'school_id': 1,
          'actor_user_id': actor,
          'operation_id': key,
          'status': 'committed',
        });
        return lostReply
            ? const FakeResponse({
                'error': true,
                'message': 'Unavailable',
                'code': 503,
              }, status: 503)
            : ok(receipt());
      },
      'POST v2/wallets/withdrawals/601/cancel': (RequestOptions _) {
        if (status != 'requested') {
          return const FakeResponse({
            'error': true,
            'message': 'Finance is processing the request.',
            'code': 409,
          }, status: 409);
        }
        status = 'cancelled';
        reserved = false;
        return ok(receipt());
      },
    });
  }
  final int actor;
  final api = FakeApi(routes: {});
  Map<String, dynamic>? intent;
  bool reserved = false, lostReply = false;
  String status = 'requested';
  int writes = 0;
  int get requestedCents {
    final parts = (intent!['amount'] as String).split('.');
    return int.parse(parts.first) * 100 +
        int.parse(parts.last.padRight(2, '0'));
  }

  Map<String, dynamic> receipt() => walletWithdrawalReceiptFixture(
    operation: intent!['operation_id'] as String,
    viewer: actor,
    status: status,
    method: intent!['slot_id'] != null ? 'cash' : 'eft',
    cents: requestedCents,
  );
  ApiWalletRepository repository() => ApiWalletRepository(
    ApiClient(
      createDio: () => Dio()..httpClientAdapter = api,
      authToken: () => 'synthetic',
      onUnauthorized: () {},
    ),
    schoolId: 1,
    accountId: actor,
    contextActive: () => true,
  );
}

Future<bool> submit(WalletCubit cubit) => cubit.requestWithdrawal(
  amountCents: 30000,
  bankInstructions: 'Private synthetic original-holder bank details',
);

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  setUp(() => SharedPreferences.setMockInitialValues({}));

  test(
    'request reflects server reservation and holder cancellation restores only the original wallet',
    () async {
      final server = WithdrawalServer();
      final cubit = WalletCubit(server.repository());
      expect(await cubit.load(), isTrue);
      expect(await submit(cubit), isTrue);
      expect(
        cubit.state.index!.owners.first.wallets.single.balanceCents,
        20000,
      );
      expect(cubit.lastWithdrawal!.amountCents, 30000);
      expect(
        cubit.withdrawals!.items.single.status,
        WalletWithdrawalReceiptStatusEnum.requested,
      );
      final sent = server.api.requests.firstWhere((r) => r.method == 'POST');
      expect(sent.headers['Idempotency-Key'], server.intent!['operation_id']);
      expect(server.intent!['amount'], '300.00');
      expect(await cubit.cancelWithdrawal(601), isTrue);
      expect(
        cubit.state.index!.owners.first.wallets.single.balanceCents,
        50000,
      );
      expect(
        cubit.withdrawals!.items.single.status,
        WalletWithdrawalReceiptStatusEnum.cancelled,
      );
      expect(server.writes, 1);
      await cubit.close();
    },
  );

  test(
    'lost reply survives reopening and resolves one reserved withdrawal without persisting bank instructions',
    () async {
      final server = WithdrawalServer()..lostReply = true;
      final first = WalletCubit(server.repository());
      await first.load();
      expect(await submit(first), isFalse);
      expect(first.hasPendingMoney, isTrue);
      expect(first.canRetry, isFalse);
      final prefs = await SharedPreferences.getInstance();
      expect(prefs.getKeys().every((key) => prefs.get(key) == true), isTrue);
      await first.close();
      final next = WalletCubit(server.repository());
      expect(await next.load(), isTrue);
      expect(await submit(next), isFalse);
      expect(await next.resolvePending(), isTrue);
      expect(next.withdrawals!.items.single.reference, 'WW-601');
      expect(next.state.index!.owners.first.wallets.single.balanceCents, 20000);
      expect(next.hasPendingMoney, isFalse);
      expect(server.writes, 1);
      expect(prefs.getKeys(), isEmpty);
      await next.close();
    },
  );

  test(
    'the exact boundary follows the explicit school choice for cash or bank',
    () async {
      for (final cashBoundary in [false, true]) {
        final server = WithdrawalServer();
        final meta = walletWithdrawalPageFixture(configured: true);
        meta['cash_at_boundary'] = cashBoundary;
        server.api.routes['GET v2/wallets/withdrawals'] = ok(meta);
        final repo = server.repository();
        await repo.index();
        await repo.withdrawals();
        final row = await repo.requestWithdrawal(
          amountCents: 25000,
          slotId: cashBoundary ? 701 : null,
          bankInstructions: cashBoundary
              ? null
              : 'Private original-holder bank details',
          operation: '00000000-0000-4000-8000-000000000612',
        );
        expect(
          row.method,
          cashBoundary
              ? WalletWithdrawalReceiptMethodEnum.cash
              : WalletWithdrawalReceiptMethodEnum.eft,
        );
        expect(server.intent!['slot_id'], cashBoundary ? 701 : null);
      }
    },
  );

  test(
    'malformed last metadata request clears an earlier private child view',
    () async {
      final server = WithdrawalServer();
      server.api.routes['GET v2/wallets/7/statement'] = ok(
        walletStatementFixture(),
      );
      final cubit = WalletCubit(server.repository());
      await cubit.load();
      await cubit.show(7);
      server.api.routes['GET v2/wallets/withdrawals'] = const FakeResponse({
        'error': true,
        'message': 'Unavailable',
        'code': 503,
      }, status: 503);
      expect(await cubit.load(), isFalse);
      expect(cubit.state.index, isNull);
      expect(cubit.state.statement, isNull);
      expect(cubit.transfers, isNull);
      expect(cubit.deposits, isNull);
      expect(cubit.withdrawals, isNull);
      await cubit.close();
    },
  );

  for (final mutation in [
    'school',
    'holder',
    'wallet',
    'fractional',
    'paid-receipt',
    'cash-slot',
    'policy',
    'places',
  ]) {
    test(
      'withdrawal response refuses malformed $mutation before exposing private history',
      () async {
        final server = WithdrawalServer();
        final row = walletWithdrawalReceiptFixture(status: 'paid');
        final page = walletWithdrawalPageFixture(
          configured: true,
          items: [row],
        );
        switch (mutation) {
          case 'school':
            row['school_id'] = 2;
          case 'holder':
            row['holder_user_id'] = 99;
          case 'wallet':
            row['wallet_id'] = 7;
          case 'fractional':
            row['amount_cents'] = 30000.5;
          case 'paid-receipt':
            row['payout_reference'] = null;
          case 'cash-slot':
            row['pickup_starts_at'] = '2026-11-01T08:00:00Z';
          case 'policy':
            page['policy_revision'] = 1.5;
          case 'places':
            (page['slots'] as List).first['available_places'] = 1.5;
        }
        server.api.routes['GET v2/wallets/withdrawals'] = ok(page);
        final cubit = WalletCubit(server.repository());
        expect(await cubit.load(), isFalse);
        expect(cubit.withdrawals, isNull);
        expect(cubit.state.index, isNull);
        await cubit.close();
      },
    );
  }

  for (final width in [390.0, 820.0, 1280.0]) {
    testWidgets(
      'Parent reviews a bank withdrawal, sees approval and payout without exposing bank details at $width',
      (tester) async {
        tester.view.physicalSize = Size(width, 1800);
        tester.view.devicePixelRatio = 1;
        addTearDown(tester.view.resetPhysicalSize);
        addTearDown(tester.view.resetDevicePixelRatio);
        final server = WithdrawalServer();
        await tester.pumpWidget(
          MaterialApp(home: WalletScreen(repository: server.repository())),
        );
        await tester.pumpAndSettle();
        await tester.ensureVisible(find.text('Adult wallet withdrawals'));
        await tester.tap(find.text('Adult wallet withdrawals'));
        await tester.pumpAndSettle();
        Future<void> enter(String label, String value) async {
          final field = find.byWidgetPredicate(
            (w) => w is TextField && w.decoration?.labelText == label,
          );
          await tester.ensureVisible(field);
          await tester.enterText(field, value);
          await tester.pumpAndSettle();
        }

        await enter('Withdrawal amount', '300.00');
        await enter(
          'Private bank details for your own account',
          'Private synthetic original-holder bank details',
        );
        final consent = find.text(
          'I reviewed the amount and my own pickup or bank details. Reserve these funds for Finance approval; this does not send a payment.',
        );
        await tester.ensureVisible(consent);
        await tester.tap(consent);
        await tester.pumpAndSettle();
        final submit = find.text('Request wallet withdrawal');
        await tester.ensureVisible(submit);
        await tester.tap(submit);
        await tester.pumpAndSettle();
        expect(server.writes, 1);
        Future<void> expanded(String text) async {
          if (find.text(text).evaluate().isEmpty) {
            await tester.ensureVisible(find.text('Adult wallet withdrawals'));
            await tester.tap(find.text('Adult wallet withdrawals'));
            await tester.pumpAndSettle();
          }
        }

        await expanded('WW-601');
        expect(find.text('WW-601'), findsOneWidget);
        expect(find.text('Awaiting Finance approval'), findsOneWidget);
        expect(
          find.text('Private synthetic original-holder bank details'),
          findsNothing,
        );
        server.status = 'approved';
        await tester.tap(find.byTooltip('Refresh wallets'));
        await tester.pumpAndSettle();
        await expanded('Approved, awaiting actual payment');
        expect(find.text('Approved, awaiting actual payment'), findsOneWidget);
        expect(find.text('Cancel unpaid withdrawal'), findsNothing);
        server.status = 'paid';
        await tester.tap(find.byTooltip('Refresh wallets'));
        await tester.pumpAndSettle();
        await expanded('WW-PAY-801');
        expect(find.text('WW-PAY-801'), findsOneWidget);
        expect(find.text('Paid'), findsOneWidget);
        expect(tester.takeException(), isNull);
        await tester.pumpWidget(const SizedBox());
      },
    );
  }
  testWidgets('Teacher chooses a published cash pickup slot at phone width', (
    tester,
  ) async {
    tester.view.physicalSize = const Size(390, 1800);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
    final server = WithdrawalServer(actor: 12);
    await tester.pumpWidget(
      MaterialApp(home: WalletScreen(repository: server.repository())),
    );
    await tester.pumpAndSettle();
    await tester.ensureVisible(find.text('Adult wallet withdrawals'));
    await tester.tap(find.text('Adult wallet withdrawals'));
    await tester.pumpAndSettle();
    final amount = find.byWidgetPredicate(
      (w) => w is TextField && w.decoration?.labelText == 'Withdrawal amount',
    );
    await tester.ensureVisible(amount);
    await tester.enterText(amount, '10.00');
    await tester.pumpAndSettle();
    final selector = find.byType(DropdownButtonFormField<int>).last;
    await tester.ensureVisible(selector);
    await tester.tap(selector);
    await tester.pumpAndSettle();
    await tester.tap(
      find.text('2026-11-01 10:00 – 2026-11-01 11:00 Africa/Johannesburg').last,
    );
    await tester.pumpAndSettle();
    final consent = find.text(
      'I reviewed the amount and my own pickup or bank details. Reserve these funds for Finance approval; this does not send a payment.',
    );
    await tester.ensureVisible(consent);
    await tester.tap(consent);
    await tester.pumpAndSettle();
    final submit = find.text('Request wallet withdrawal');
    await tester.ensureVisible(submit);
    await tester.tap(submit);
    await tester.pumpAndSettle();
    expect(server.writes, 1);
    expect(server.intent!['slot_id'], 701);
    expect(server.intent!['private_bank_instructions'], isNull);
    if (find.text('WW-601').evaluate().isEmpty) {
      await tester.ensureVisible(find.text('Adult wallet withdrawals'));
      await tester.tap(find.text('Adult wallet withdrawals'));
      await tester.pumpAndSettle();
    }
    expect(find.text('WW-601'), findsOneWidget);
    expect(find.text('Awaiting Finance approval'), findsOneWidget);
    expect(tester.takeException(), isNull);
    await tester.pumpWidget(const SizedBox());
  });
}
