import 'dart:async';
import 'dart:convert';
import 'dart:typed_data';
import 'package:dio/dio.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:eschool_wallet/eschool_wallet.dart';
import 'package:eschool_wallet/testing.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:qr_flutter/qr_flutter.dart';
import 'package:shared_preferences/shared_preferences.dart';

Map<String, Object?> ok(Object data) => {
  'error': false,
  'message': '',
  'data': data,
  'code': 200,
};
const key = '00000000-0000-4000-8000-000000000901';

class GateAdapter implements HttpClientAdapter {
  GateAdapter(this.delegate);
  final FakeApi delegate;
  Completer<FakeResponse>? gate;
  final started = Completer<void>();
  @override
  Future<ResponseBody> fetch(
    RequestOptions options,
    Stream<Uint8List>? stream,
    Future<void>? cancel,
  ) async {
    if (gate != null &&
        options.method == 'GET' &&
        options.uri.path.endsWith('/commerce/orders')) {
      delegate.requests.add(options);
      await stream?.drain<void>();
      if (!started.isCompleted) started.complete();
      final answer = await gate!.future;
      return ResponseBody.fromString(
        jsonEncode(answer.body),
        answer.status,
        headers: {
          Headers.contentTypeHeader: [Headers.jsonContentType],
        },
      );
    }
    return delegate.fetch(options, stream, cancel);
  }

  @override
  void close({bool force = false}) {}
}

ApiCommerceRepository gatedRepository(
  CommerceFakeServer server,
  GateAdapter adapter, {
  bool Function()? active,
}) {
  final client = ApiClient(
    createDio: () => Dio()..httpClientAdapter = adapter,
    authToken: () => 'synthetic',
    onUnauthorized: () {},
  );
  return ApiCommerceRepository(
    client,
    wallets: ApiWalletRepository(
      client,
      schoolId: 1,
      accountId: server.actor,
      contextActive: active ?? () => true,
    ),
  );
}

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  setUp(() => SharedPreferences.setMockInitialValues({}));

  test(
    'quoted purchase and cancellation keep original wallet scope and integer receipts',
    () async {
      final server = CommerceFakeServer();
      final repository = server.repository();
      await repository.walletChoices();
      expect(
        (await repository.vendors()).items.single.name,
        'Synthetic tuckshop',
      );
      expect((await repository.catalogue(81)).items.single.priceCents, 1250);
      final quote = await repository.quote(81, 6, {51: 1});
      final receipt = await repository.purchase(quote, key);
      expect(receipt.amountCents, 1250);
      expect(
        (await repository.walletChoices())
            .owners
            .first
            .wallets
            .single
            .balanceCents,
        3750,
      );
      expect(
        (await repository.orders()).items.single.qr,
        startsWith('navigateed:order:'),
      );
      final cancelled = await repository.cancel(
        901,
        'Synthetic uncollected cancellation',
        '00000000-0000-4000-8000-000000000902',
      );
      expect(cancelled.status, CommerceOrderReceiptStatusEnum.cancelled);
      expect(cancelled.qr, isNull);
      expect(server.balances[6], 5000);
      for (final request in server.api.requests) {
        expect(request.headers['X-School-Id'].toString(), '1');
        expect(request.headers['X-Account-Id'].toString(), '21');
      }
      final purchase = server.api.requests.singleWhere(
        (r) => r.method == 'POST' && r.uri.path.endsWith('/commerce/orders'),
      );
      expect(purchase.headers['Idempotency-Key'], key);
      expect((purchase.data as Map)['quote'], 'synthetic-opaque-checkout');
    },
  );

  final mutations = <String, void Function(Map<String, dynamic>)>{
    'another school': (r) => r['school_id'] = 2,
    'another account': (r) => r['actor_user_id'] = 999,
    'another wallet owner': (r) => r['wallet_owner_id'] = 999,
    'unknown wallet': (r) => r['wallet_id'] = 999,
    'another currency': (r) => r['currency'] = 'USD',
    'fractional cents': (r) => r['amount_cents'] = 1250.5,
    'fractional quantity': (r) => (r['lines'] as List).first['quantity'] = 1.5,
    'wrong line total': (r) => (r['lines'] as List).first['gross_cents'] = 1,
    'wrong VAT total': (r) => r['vat_cents'] = 1,
    'wrong seller': (r) => (r['seller'] as Map)['id'] = 999,
    'inconsistent seller VAT': (r) =>
        (r['seller'] as Map)['vat_registered'] = true,
    'unknown status': (r) => r['status'] = 'sent_somewhere',
    'forged QR': (r) => r['qr'] = 'https://example.invalid/collect',
    'terminal QR': (r) {
      r['status'] = 'collected';
      r['processed_at'] = '2026-06-15T09:01:00Z';
    },
    'non-UUID operation': (r) => r['operation_id'] = 'changed',
  };
  for (final entry in mutations.entries) {
    test('rejects receipt with ${entry.key}', () async {
      final server = CommerceFakeServer()
        ..purchased = true
        ..operation = key;
      final repository = server.repository();
      await repository.walletChoices();
      final receipt = server.receipt();
      entry.value(receipt);
      final page = server.orderPage();
      page['items'] = [receipt];
      server.api.routes['GET v2/commerce/orders'] = ok(page);
      await expectLater(repository.orders(), throwsA(isA<ApiException>()));
    });
  }

  test(
    'lost purchase reply reopens with only an opaque UUID and resolves without charging again',
    () async {
      final server = CommerceFakeServer()..losePurchaseReply = true;
      var cubit = CommerceCubit(server.repository());
      await cubit.load();
      await cubit.openVendor(81);
      await cubit.review(81, 6, {51: 1});
      expect(await cubit.purchase(), isFalse);
      expect(cubit.hasPendingMoney, isTrue);
      expect(cubit.state.orders, isNull);
      expect(cubit.state.quote, isNull);
      final prefs = await SharedPreferences.getInstance();
      final stored = prefs.getKeys();
      expect(stored, hasLength(1));
      expect(stored.single, startsWith('wallet_pending_v1_1_21_'));
      expect(prefs.get(stored.single), isTrue);
      expect(stored.single, isNot(contains('quote')));
      await cubit.close();
      cubit = CommerceCubit(server.repository());
      await cubit.load();
      expect(cubit.hasPendingMoney, isTrue);
      expect(await cubit.purchase(), isFalse);
      expect(await cubit.recover(), isTrue);
      expect(cubit.hasPendingMoney, isFalse);
      expect(cubit.state.orders!.items.single.orderNumber, 'VO-000001');
      expect(server.writes, 1);
      expect(server.balances[6], 3750);
      await cubit.close();
    },
  );

  test('lost cancellation reply is checked without refunding twice', () async {
    final server = CommerceFakeServer();
    final cubit = CommerceCubit(server.repository());
    await cubit.load();
    await cubit.openVendor(81);
    await cubit.review(81, 6, {51: 1});
    await cubit.purchase();
    server.loseCancelReply = true;
    expect(await cubit.cancel(901, 'Synthetic original cancellation'), isFalse);
    expect(cubit.hasPendingMoney, isTrue);
    expect(cubit.state.orders, isNull);
    expect(await cubit.cancel(901, 'Do not refund twice'), isFalse);
    expect(await cubit.recover(), isTrue);
    expect(server.cancellations, 1);
    expect(server.balances[6], 5000);
    expect(
      cubit.state.orders!.items.single.status,
      CommerceOrderReceiptStatusEnum.cancelled,
    );
    expect(cubit.state.orders!.items.single.qr, isNull);
    await cubit.close();
  });

  test(
    'refresh clears private orders before awaiting a fresh denial',
    () async {
      final server = CommerceFakeServer()
        ..purchased = true
        ..operation = key;
      final adapter = GateAdapter(server.api);
      final cubit = CommerceCubit(gatedRepository(server, adapter));
      await cubit.load();
      expect(cubit.state.orders!.items, hasLength(1));
      adapter.gate = Completer<FakeResponse>();
      final loading = cubit.load();
      expect(cubit.state.orders, isNull);
      expect(cubit.state.quote, isNull);
      await adapter.started.future;
      adapter.gate!.complete(CommerceFakeServer.denied);
      expect(await loading, isFalse);
      expect(cubit.state.accessLost, isTrue);
      expect(cubit.state.wallets, isNull);
      expect(cubit.state.catalogue, isNull);
      expect(cubit.state.orders, isNull);
      await cubit.close();
    },
  );

  test('scope invalidation drops a delayed private response', () async {
    final server = CommerceFakeServer()
      ..purchased = true
      ..operation = key;
    var active = true;
    final adapter = GateAdapter(server.api);
    final repository = gatedRepository(server, adapter, active: () => active);
    final cubit = CommerceCubit(repository);
    await cubit.load();
    adapter.gate = Completer<FakeResponse>();
    final loading = cubit.load();
    await adapter.started.future;
    active = false;
    adapter.gate!.complete(FakeResponse(ok(server.orderPage())));
    expect(await loading, isFalse);
    expect(cubit.state.accessLost, isTrue);
    expect(cubit.state.orders, isNull);
    await cubit.close();
  });

  test(
    'read-only commerce retains orders without purchase or cancellation writes',
    () async {
      final server = CommerceFakeServer()
        ..purchased = true
        ..operation = key
        ..readOnly = true;
      final cubit = CommerceCubit(server.repository());
      await cubit.load();
      await cubit.openVendor(81);
      expect(cubit.state.orders!.items.single.orderNumber, 'VO-000001');
      expect(await cubit.review(81, 6, {51: 1}), isFalse);
      expect(await cubit.purchase(), isFalse);
      expect(await cubit.cancel(901, 'Paused'), isFalse);
      expect(server.writes, 0);
      expect(server.cancellations, 0);
      await cubit.close();
    },
  );

  for (final width in [390.0, 820.0, 1280.0]) {
    testWidgets(
      'quoted checkout receipt QR and cancellation at ${width.toInt()}px',
      (tester) async {
        tester.view.physicalSize = Size(width, 1200);
        tester.view.devicePixelRatio = 1;
        addTearDown(tester.view.resetPhysicalSize);
        addTearDown(tester.view.resetDevicePixelRatio);
        final server = CommerceFakeServer();
        await tester.pumpWidget(
          MaterialApp(home: CommerceScreen(repository: server.repository())),
        );
        await tester.pumpAndSettle();
        Future<void> tap(String text) async {
          final finder = find.text(text);
          await tester.ensureVisible(finder);
          await tester.tap(finder);
          await tester.pumpAndSettle();
        }

        await tap('Synthetic tuckshop');
        await tester.enterText(
          find.byKey(const ValueKey('commerce-quantity-51')),
          '1',
        );
        await tester.pumpAndSettle();
        await tap('Review cart');
        expect(
          find.text('Total from original wallet: ZAR 12.50'),
          findsOneWidget,
        );
        expect(server.writes, 0);
        await tap(
          'I reviewed the original wallet, items, quantities and total. Purchase once.',
        );
        await tap('Confirm quoted purchase');
        expect(server.writes, 1);
        await tap('VO-000001');
        expect(find.byType(QrImageView), findsOneWidget);
        final reason = find.byWidgetPredicate(
          (w) =>
              w is TextField &&
              w.decoration?.labelText == 'Private order cancellation reason',
        );
        await tester.ensureVisible(reason);
        await tester.enterText(reason, 'Synthetic uncollected order');
        await tap(
          'Cancel only this uncollected order and return money to its original wallet.',
        );
        await tap('Cancel uncollected order');
        expect(server.balances[6], 5000);
        expect(server.cancellations, 1);
        expect(find.byType(QrImageView), findsNothing);
        expect(find.textContaining('Cancelled'), findsOneWidget);
        expect(tester.takeException(), isNull);
        await tester.pumpWidget(const SizedBox());
      },
    );
  }
}
