import 'package:dio/dio.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:eschool_wallet/eschool_wallet.dart';
import 'package:eschool_wallet/testing.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';

const operation = '00000000-0000-4000-8000-000000000401';
Map<String, Object?> ok(Object data) => {
  'error': false,
  'message': '',
  'data': data,
  'code': 200,
};

class DepositServer {
  DepositServer() {
    api.routes.addAll({
      'GET v2/wallets': (RequestOptions _) {
        final row = walletIndexFixture();
        if (status == 'verified') {
          ((row['owners'] as List).first['wallets'] as List)
                  .first['balance_cents'] =
              3950;
        }
        return ok(row);
      },
      'GET v2/wallets/transfers': ok(walletTransferPageFixture()),
      'GET v2/wallets/deposits': (RequestOptions _) => ok(
        walletDepositPageFixture(
          configured: true,
          items: claim == null ? [] : [receipt()],
        ),
      ),
      'POST v2/wallets/deposits': (RequestOptions request) {
        writes++;
        claim = Map<String, dynamic>.from(request.data as Map);
        final key = claim!['operation_id'] as String;
        api.routes['POST v2/wallets/operations/$key/resolve'] = ok({
          'school_id': 1,
          'actor_user_id': 21,
          'operation_id': key,
          'status': 'committed',
        });
        return loseReply
            ? const FakeResponse({
                'error': true,
                'message': 'Unavailable',
                'code': 503,
              }, status: 503)
            : ok(receipt());
      },
      'POST v2/wallets/deposits/401/cancel': (RequestOptions _) {
        status = 'cancelled';
        return ok(receipt());
      },
    });
  }
  final FakeApi api = FakeApi(routes: {});
  Map<String, dynamic>? claim;
  int writes = 0;
  bool loseReply = false;
  String status = 'pending';
  Map<String, dynamic> receipt() => walletDepositReceiptFixture(
    operation: claim!['operation_id'] as String,
    reference: claim!['source_reference'] as String,
    paidOn: claim!['paid_on'] as String,
    method: claim!['method'] as String,
    status: status,
    verifiedCents: 1950,
  );
  ApiWalletRepository repository() => ApiWalletRepository(
    ApiClient(
      createDio: () => Dio()..httpClientAdapter = api,
      authToken: () => 'synthetic',
      onUnauthorized: () {},
    ),
    schoolId: 1,
    accountId: 21,
    contextActive: () => true,
  );
}

Future<bool> submit(WalletCubit cubit) => cubit.requestDeposit(
  amountCents: 2000,
  method: 'eft',
  reference: ' parent-deposit-001 ',
  paidOn: '2026-06-15',
  evidence: 'Private synthetic proof for Finance only',
);

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  setUp(() => SharedPreferences.setMockInitialValues({}));

  test(
    'claim creates no optimistic credit; corrected Finance receipt changes balance only after refresh',
    () async {
      final server = DepositServer();
      final cubit = WalletCubit(server.repository());
      expect(await cubit.load(), isTrue);
      expect(await submit(cubit), isTrue);
      expect(cubit.state.index!.owners.first.wallets.single.balanceCents, 2000);
      expect(cubit.lastDeposit!.requestedCents, 2000);
      expect(
        cubit.deposits!.items.single.status,
        WalletDepositReceiptStatusEnum.pending,
      );
      expect(server.claim!['amount'], '20.00');
      expect(server.claim!['source_reference'], 'PARENT-DEPOSIT-001');
      final request = server.api.requests.firstWhere((r) => r.method == 'POST');
      expect(request.headers['Idempotency-Key'], server.claim!['operation_id']);
      server.status = 'verified';
      expect(await cubit.load(), isTrue);
      expect(cubit.state.index!.owners.first.wallets.single.balanceCents, 3950);
      expect(cubit.deposits!.items.single.requestedCents, 2000);
      expect(cubit.deposits!.items.single.verifiedReceipt!.amountCents, 1950);
      expect(cubit.lastDeposit, isNull);
      await cubit.close();
    },
  );

  test(
    'lost claim reply recovers its permanent UUID after reopening without duplicate claim or private persisted proof',
    () async {
      final server = DepositServer()..loseReply = true;
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
      expect(next.hasPendingMoney, isFalse);
      expect(next.deposits!.items.single.reference, 'WD-401');
      expect(next.state.index!.owners.first.wallets.single.balanceCents, 2000);
      expect(server.writes, 1);
      expect(prefs.getKeys(), isEmpty);
      await next.close();
    },
  );

  test(
    'withdrawing a pending verification request never changes the wallet balance',
    () async {
      final server = DepositServer();
      final cubit = WalletCubit(server.repository());
      await cubit.load();
      await submit(cubit);
      expect(await cubit.cancelDeposit(401), isTrue);
      expect(
        cubit.deposits!.items.single.status,
        WalletDepositReceiptStatusEnum.cancelled,
      );
      expect(cubit.state.index!.owners.first.wallets.single.balanceCents, 2000);
      await cubit.close();
    },
  );

  for (final change in [
    'school',
    'actor',
    'wallet',
    'fractional',
    'fee',
    'net',
    'collector',
    'settlement',
  ]) {
    test(
      'deposit history rejects malformed $change before exposing its receipts',
      () async {
        final server = DepositServer();
        final row = walletDepositReceiptFixture(status: 'verified');
        switch (change) {
          case 'school':
            row['school_id'] = 2;
          case 'actor':
            row['actor_user_id'] = 99;
          case 'wallet':
            row['wallet_id'] = 7;
          case 'fractional':
            row['requested_cents'] = 2000.5;
          case 'fee':
            (row['verified_receipt'] as Map)['fee_cents'] = 1;
          case 'net':
            (row['verified_receipt'] as Map)['net_cents'] = 1999;
          case 'collector':
            (row['verified_receipt'] as Map)['collected_by'] = 'platform';
          case 'settlement':
            (row['verified_receipt'] as Map)['settlement_reference'] = 'WRONG';
        }
        server.api.routes['GET v2/wallets/deposits'] = ok(
          walletDepositPageFixture(configured: true, items: [row]),
        );
        final cubit = WalletCubit(server.repository());
        expect(await cubit.load(), isFalse);
        expect(cubit.deposits, isNull);
        expect(cubit.state.index, isNull);
        await cubit.close();
      },
    );
  }

  for (final width in [390.0, 820.0, 1280.0]) {
    testWidgets(
      'Parent submits a reviewed deposit and sees requested and actual amounts at $width',
      (tester) async {
        tester.view.physicalSize = Size(width, 1800);
        tester.view.devicePixelRatio = 1;
        addTearDown(tester.view.resetPhysicalSize);
        addTearDown(tester.view.resetDevicePixelRatio);
        final server = DepositServer();
        await tester.pumpWidget(
          MaterialApp(home: WalletScreen(repository: server.repository())),
        );
        await tester.pumpAndSettle();
        await tester.tap(find.text('Parent wallet deposits'));
        await tester.pumpAndSettle();
        await tester.tap(find.byType(DropdownButtonFormField<String>).last);
        await tester.pumpAndSettle();
        await tester.tap(find.text('EFT already paid').last);
        await tester.pumpAndSettle();
        Future<void> enter(String label, String value) async {
          final field = find.byWidgetPredicate(
            (w) => w is TextField && w.decoration?.labelText == label,
          );
          await tester.ensureVisible(field);
          await tester.enterText(field, value);
        }

        await enter('Amount already paid', '20.00');
        await enter(
          'Cash receipt or bank transaction reference',
          'parent-deposit-001',
        );
        await enter('Paid date (YYYY-MM-DD)', '2026-06-15');
        await enter(
          'Private receipt details for Finance',
          'Private synthetic proof for Finance only',
        );
        final consent = find.text(
          'I already paid this deposit and understand that Finance must verify it before crediting my wallet.',
        );
        await tester.ensureVisible(consent);
        await tester.tap(consent);
        await tester.pumpAndSettle();
        final submitButton = find.text('Submit deposit for verification');
        await tester.ensureVisible(submitButton);
        await tester.tap(submitButton);
        await tester.pumpAndSettle();
        expect(server.writes, 1);
        await tester.tap(find.text('Parent wallet deposits'));
        await tester.pumpAndSettle();
        expect(find.text('WD-401'), findsOneWidget);
        expect(find.text('Awaiting Finance verification'), findsOneWidget);
        expect(find.text('Requested amount: ZAR 20.00'), findsOneWidget);
        expect(
          find.text('Private synthetic proof for Finance only'),
          findsNothing,
        );
        server.status = 'verified';
        await tester.tap(find.byTooltip('Refresh wallets'));
        await tester.pumpAndSettle();
        await tester.tap(find.text('Parent wallet deposits'));
        await tester.pumpAndSettle();
        expect(find.text('WP-501'), findsOneWidget);
        expect(find.text('Actual verified amount: ZAR 19.50'), findsOneWidget);
        expect(tester.takeException(), isNull);
        await tester.pumpWidget(const SizedBox());
      },
    );
  }
}
