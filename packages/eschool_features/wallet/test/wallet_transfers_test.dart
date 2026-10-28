import 'package:dio/dio.dart';
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
const operation = '00000000-0000-4000-8000-000000000901';

class PendingStore implements WalletPendingStore {
  final Map<String, Set<String>> values = {};
  bool failPut = false;
  String scope(int school, int actor) => '$school:$actor';
  @override
  Future<Set<String>> read(int school, int actor) async => {
    ...?values[scope(school, actor)],
  };
  @override
  Future<void> put(int school, int actor, String operation) async {
    if (failPut) throw StateError('Synthetic storage failure');
    (values[scope(school, actor)] ??= {}).add(operation);
  }

  @override
  Future<void> remove(int school, int actor, String operation) async =>
      values[scope(school, actor)]?.remove(operation);
}

ApiWalletRepository repository(FakeApi api, {bool Function()? active}) =>
    ApiWalletRepository(
      ApiClient(
        createDio: () => Dio()..httpClientAdapter = api,
        authToken: () => 'synthetic-wallet-token',
        onUnauthorized: () {},
      ),
      schoolId: 1,
      accountId: 21,
      contextActive: active ?? () => true,
    );

class Server {
  Server() {
    api.routes.addAll({
      'GET v2/wallets': (RequestOptions _) {
        final data = walletIndexFixture();
        if (committed) {
          ((data['owners'] as List).first['wallets'] as List)
                  .first['balance_cents'] =
              1000;
          ((data['owners'] as List).last['wallets'] as List)
                  .first['balance_cents'] =
              6000;
        }
        return ok(data);
      },
      'GET v2/wallets/withdrawals': ok(walletWithdrawalPageFixture()),
      'GET v2/wallets/transfers': (RequestOptions _) => ok(
        walletTransferPageFixture(
          items: committed
              ? [walletTransferReceiptFixture(operation: lastOperation!)]
              : [],
        ),
      ),
      'GET v2/wallets/deposits': (RequestOptions _) =>
          ok(walletDepositPageFixture()),
      'POST v2/wallets/transfers': (RequestOptions q) {
        writes++;
        lastOperation = (q.data as Map)['operation_id'] as String;
        committed = true;
        bindResolution(lastOperation!, 'committed');
        afterPost?.call();
        return loseReply
            ? const FakeResponse({
                'error': true,
                'message': 'Synthetic lost reply',
                'code': 503,
              }, status: 503)
            : ok(walletTransferReceiptFixture(operation: lastOperation!));
      },
    });
  }
  final FakeApi api = FakeApi(routes: {});
  bool committed = false, loseReply = false, failResolution = false;
  int writes = 0;
  String? lastOperation;
  void Function()? afterPost;
  void bindResolution(String key, String status) {
    api.routes['POST v2/wallets/operations/$key/resolve'] =
        (RequestOptions _) => failResolution
        ? const FakeResponse({
            'error': true,
            'message': 'Unavailable',
            'code': 503,
          }, status: 503)
        : ok({
            'school_id': 1,
            'actor_user_id': 21,
            'operation_id': key,
            'status': status,
          });
  }
}

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  setUp(() => SharedPreferences.setMockInitialValues({}));

  test(
    'a committed transfer with a lost reply survives a new cubit and resolves without another debit',
    () async {
      final server = Server()..loseReply = true;
      final store = PendingStore();
      final first = WalletCubit(repository(server.api), pendingStore: store);
      expect(await first.load(), isTrue);
      expect(await first.transfer(6, 7, 1000), isFalse);
      expect(first.hasPendingMoney, isTrue);
      expect(first.canRetry, isFalse);
      expect(server.writes, 1);
      final key = server.lastOperation!;
      expect(await store.read(1, 21), {key});
      await first.close();
      final reopened = WalletCubit(repository(server.api), pendingStore: store);
      expect(await reopened.load(), isTrue);
      expect(await reopened.transfer(6, 7, 1000), isFalse);
      expect(await reopened.resolvePending(), isTrue);
      expect(reopened.hasPendingMoney, isFalse);
      expect(reopened.resolutionMessage, contains('was recorded'));
      expect(reopened.transfers!.total, 1);
      expect(
        reopened.state.index!.owners.first.wallets.single.balanceCents,
        1000,
      );
      expect(server.writes, 1);
      expect(await store.read(1, 21), isEmpty);
      await reopened.close();
    },
  );

  test(
    'storage failure prevents sending money and unresolved recovery retains its original key',
    () async {
      final server = Server();
      final store = PendingStore()..failPut = true;
      final cubit = WalletCubit(repository(server.api), pendingStore: store);
      await cubit.load();
      expect(await cubit.transfer(6, 7, 1000), isFalse);
      expect(server.writes, 0);
      store.failPut = false;
      await store.put(1, 21, operation);
      server.bindResolution(operation, 'abandoned');
      server.failResolution = true;
      await cubit.load();
      expect(await cubit.resolvePending(), isFalse);
      expect(await store.read(1, 21), {operation});
      server.failResolution = false;
      expect(await cubit.resolvePending(), isTrue);
      expect(cubit.resolutionMessage, contains('was safely closed'));
      expect(server.writes, 0);
      await cubit.close();
    },
  );

  test(
    'context loss after commit clears private data while retaining a recoverable scoped UUID',
    () async {
      var active = true;
      final server = Server()..afterPost = () => active = false;
      final store = PendingStore();
      final cubit = WalletCubit(
        repository(server.api, active: () => active),
        pendingStore: store,
      );
      await cubit.load();
      expect(await cubit.transfer(6, 7, 1000), isFalse);
      expect(cubit.state.accessLost, isTrue);
      expect(cubit.transfers, isNull);
      expect(cubit.lastTransfer, isNull);
      expect(await store.read(1, 21), {server.lastOperation!});
      expect(await store.read(2, 21), isEmpty);
      expect(await store.read(1, 22), isEmpty);
      await cubit.close();
    },
  );

  test(
    'a changed owner index followed by a failed request cannot restore the old child view',
    () async {
      final server = Server();
      server.api.routes['GET v2/wallets/7/statement'] = ok(
        walletStatementFixture(),
      );
      final cubit = WalletCubit(
        repository(server.api),
        pendingStore: PendingStore(),
      );
      expect(await cubit.load(), isTrue);
      expect(await cubit.show(7), isTrue);
      expect(cubit.state.statement!.ownerUserId, 31);
      final reduced = walletIndexFixture();
      (reduced['owners'] as List).removeLast();
      server.api.routes['GET v2/wallets'] = ok(reduced);
      server.api.routes['GET v2/wallets/transfers'] = const FakeResponse({
        'error': true,
        'message': 'Unavailable',
        'code': 503,
      }, status: 503);
      expect(await cubit.load(), isFalse);
      expect(cubit.state.index, isNull);
      expect(cubit.state.statement, isNull);
      expect(cubit.state.deskReceipts, isNull);
      expect(cubit.transfers, isNull);
      expect(cubit.deposits, isNull);
      await cubit.close();
    },
  );

  test(
    'persistent storage keeps independent opaque keys and no financial payload',
    () async {
      final store = SharedPreferencesWalletPendingStore();
      const second = '00000000-0000-4000-8000-000000000902';
      await store.put(1, 21, operation);
      await store.put(1, 21, second);
      await store.put(2, 21, operation);
      expect(await store.read(1, 21), {operation, second});
      await store.remove(1, 21, operation);
      expect(await store.read(1, 21), {second});
      expect(await store.read(2, 21), {operation});
      final prefs = await SharedPreferences.getInstance();
      expect(prefs.getKeys().every((key) => prefs.get(key) == true), isTrue);
    },
  );

  for (final mutation in ['school', 'actor', 'owner', 'cents', 'direction']) {
    test(
      'transfer response refuses wrong $mutation before acknowledging the pending money intent',
      () async {
        final server = Server();
        server.api.routes['POST v2/wallets/transfers'] = (RequestOptions q) {
          final row = walletTransferReceiptFixture(
            operation: (q.data as Map)['operation_id'] as String,
          );
          if (mutation == 'school') row['school_id'] = 2;
          if (mutation == 'actor') row['actor_user_id'] = 99;
          if (mutation == 'owner') row['to_owner_id'] = 32;
          if (mutation == 'cents') row['amount_cents'] = 1000.5;
          if (mutation == 'direction') row['kind'] = 'to_parent';
          return ok(row);
        };
        final repo = repository(server.api);
        await repo.index();
        await expectLater(
          repo.transfer(6, 7, 1000, operation),
          throwsA(isA<ApiException>()),
        );
        final request = server.api.requests.last;
        expect(request.headers['X-School-Id'].toString(), '1');
        expect(request.headers['X-Account-Id'].toString(), '21');
        expect(request.headers['Idempotency-Key'], operation);
      },
    );
  }

  for (final width in [390.0, 820.0, 1280.0]) {
    testWidgets(
      'parent reviews and submits a transfer without overflow at $width',
      (tester) async {
        tester.view.physicalSize = Size(width, 1000);
        tester.view.devicePixelRatio = 1;
        addTearDown(tester.view.resetPhysicalSize);
        addTearDown(tester.view.resetDevicePixelRatio);
        final server = Server();
        await tester.pumpWidget(
          MaterialApp(home: WalletScreen(repository: repository(server.api))),
        );
        await tester.pumpAndSettle();
        final amount = find.byWidgetPredicate(
          (w) =>
              w is TextField &&
              w.decoration?.labelText == 'Wallet transfer amount',
        );
        await tester.enterText(amount, '10.00');
        await tester.ensureVisible(
          find.text(
            'I reviewed the source, recipient and amount for this wallet transfer.',
          ),
        );
        await tester.tap(
          find.text(
            'I reviewed the source, recipient and amount for this wallet transfer.',
          ),
        );
        await tester.pumpAndSettle();
        await tester.ensureVisible(find.text('Move wallet money'));
        await tester.tap(find.text('Move wallet money'));
        await tester.pumpAndSettle();
        expect(server.writes, 1);
        expect(find.text('WX-301'), findsOneWidget);
        expect(tester.takeException(), isNull);
        await tester.pumpWidget(const SizedBox());
      },
    );
  }
}
