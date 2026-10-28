import 'dart:async';
import 'package:dio/dio.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:eschool_wallet/eschool_wallet.dart';
import 'package:eschool_wallet/testing.dart';
import 'package:eschool_wallet/src/wallet_text.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

Map<String, Object?> ok(Object data) => {
  'error': false,
  'message': '',
  'data': data,
  'code': 200,
};

class Repo implements WalletRepository {
  Repo({this.viewer = 21, this.readOnly = false, this.issued = true});
  final int viewer;
  final bool readOnly;
  bool issued;
  bool active = true, failNext = false;
  Object? error;
  Completer<WalletStatement>? pending;
  final List<String> operations = [];
  @override
  int get schoolId => 1;
  @override
  int get accountId => viewer;
  @override
  bool get isActive => active;
  @override
  Future<PersonalWalletIndex> index() async {
    if (error != null) throw error!;
    return PersonalWalletIndex.fromJson(
      walletIndexFixture(viewer: viewer, readOnly: readOnly, issued: issued),
    );
  }

  @override
  Future<WalletStatement> statement(
    int id, {
    int page = 1,
    int perPage = 30,
  }) async {
    if (error != null) throw error!;
    return pending?.future ??
        Future.value(
          WalletStatement.fromJson(
            walletStatementFixture(
              owner: id == 7 ? 31 : viewer,
              viewer: viewer,
              readOnly: readOnly,
            ),
          ),
        );
  }

  @override
  Future<WalletStatement> open(int owner, String operation) async {
    operations.add(operation);
    if (failNext) {
      failNext = false;
      throw ApiException('transport', isTransportFailure: true);
    }
    issued = true;
    return WalletStatement.fromJson(
      walletStatementFixture(owner: owner, viewer: viewer),
    );
  }

  @override
  Future<WalletStatement> controls(
    int id, {
    required int revision,
    required int dailyCents,
    required bool allCategories,
    required List<String> categories,
    required String operation,
  }) async {
    operations.add(operation);
    return WalletStatement.fromJson(
      walletStatementFixture(owner: id == 7 ? 31 : viewer, viewer: viewer),
    );
  }
}

ApiWalletRepository apiRepo(
  FakeApi api, {
  bool Function()? active,
  String Function()? token,
}) => ApiWalletRepository(
  ApiClient(
    authToken: token ?? () => 'synthetic-wallet-token',
    onUnauthorized: () {},
    createDio: () => Dio()..httpClientAdapter = api,
  ),
  schoolId: 1,
  accountId: 21,
  contextActive: active ?? () => true,
);
void main() {
  test('cents parse without floats and zero stops spending', () {
    expect(walletCents('0.00'), 0);
    expect(walletCents('10.01'), 1001);
    expect(walletCents('1000000000.00'), 100000000000);
    for (final bad in ['-1', '0.001', '1e3', '.5', '2,50', '1000000000.01']) {
      expect(walletCents(bad), isNull);
    }
    expect(walletAmount('ZAR', 1), 'ZAR 0.01');
  });
  test(
    'private repository binds school and account on each read and validates current balances',
    () async {
      final api = FakeApi(
        routes: {
          'GET v2/wallets': ok(walletIndexFixture()),
          'GET v2/wallets/7/statement': ok(walletStatementFixture()),
        },
      );
      final repo = apiRepo(api);
      expect((await repo.index()).owners, hasLength(2));
      expect((await repo.statement(7)).balanceCents, 5000);
      for (final request in api.requests) {
        expect(request.headers['X-School-Id'].toString(), '1');
        expect(request.headers['X-Account-Id'].toString(), '21');
        expect(
          request.headers['Authorization'],
          'Bearer synthetic-wallet-token',
        );
      }
    },
  );
  for (final bad in ['school', 'actor', 'float', 'negative', 'controls']) {
    test(
      'rejects malformed or foreign wallet $bad before returning data',
      () async {
        final data = walletIndexFixture();
        final wallet =
            ((data['owners'] as List)[1]['wallets'] as List).first as Map;
        if (bad == 'school') data['school_id'] = 2;
        if (bad == 'actor') data['actor_user_id'] = 99;
        if (bad == 'float') wallet['balance_cents'] = 5000.5;
        if (bad == 'negative') wallet['balance_cents'] = -1;
        if (bad == 'controls') {
          (wallet['limits'] as Map)['effective_daily_spend_cents'] = 6000;
        }
        final repo = apiRepo(FakeApi(routes: {'GET v2/wallets': ok(data)}));
        await expectLater(repo.index(), throwsA(isA<ApiException>()));
      },
    );
  }
  test(
    'token replacement and scope invalidation refuse further private reads',
    () async {
      var token = 'first';
      var active = true;
      final api = FakeApi(routes: {'GET v2/wallets': ok(walletIndexFixture())});
      final repo = apiRepo(api, token: () => token, active: () => active);
      await repo.index();
      token = 'second';
      await expectLater(repo.index(), throwsA(isA<ApiException>()));
      token = 'first';
      active = false;
      await expectLater(repo.index(), throwsA(isA<ApiException>()));
      expect(api.requests, hasLength(1));
    },
  );
  test('unknown mutation retry keeps one operation UUID', () async {
    final repo = Repo(issued: false)..failNext = true;
    final cubit = WalletCubit(repo);
    addTearDown(cubit.close);
    await cubit.load();
    expect(await cubit.open(31), false);
    expect(cubit.canRetry, true);
    expect(await cubit.retry(), true);
    expect(repo.operations, hasLength(2));
    expect(repo.operations[0], repo.operations[1]);
  });
  test(
    'late response after access revocation clears the whole private state',
    () async {
      final repo = Repo();
      final cubit = WalletCubit(repo);
      addTearDown(cubit.close);
      await cubit.load();
      repo.pending = Completer<WalletStatement>();
      final pending = cubit.show(7);
      repo.active = false;
      repo.pending!.complete(
        WalletStatement.fromJson(walletStatementFixture()),
      );
      await pending;
      expect(cubit.state.accessLost, true);
      expect(cubit.state.index, isNull);
      expect(cubit.state.statement, isNull);
    },
  );
  for (final width in [390.0, 820.0, 1280.0]) {
    testWidgets('parent reads child wallet and edits controls at $width', (
      tester,
    ) async {
      tester.view.physicalSize = Size(width, 1000);
      tester.view.devicePixelRatio = 1;
      addTearDown(tester.view.resetPhysicalSize);
      addTearDown(tester.view.resetDevicePixelRatio);
      final repo = Repo();
      await tester.pumpWidget(
        MaterialApp(
          theme: AppTheme.light(),
          home: WalletScreen(repository: repo),
        ),
      );
      await tester.pumpAndSettle();
      expect(find.text('Asha Synthetic'), findsOneWidget);
      await tester.tap(find.text('Open wallet statement').last);
      await tester.pumpAndSettle();
      expect(find.text('ZAR 50.00'), findsWidgets);
      await tester.tap(find.text('Edit spending controls'));
      await tester.pumpAndSettle();
      await tester.enterText(
        find.widgetWithText(TextFormField, 'Daily spending limit'),
        '5.00',
      );
      await tester.scrollUntilVisible(
        find.text('Save spending controls'),
        150,
        scrollable: find.byType(Scrollable).first,
      );
      await tester.tap(find.text('Save spending controls'));
      await tester.pumpAndSettle();
      expect(repo.operations, hasLength(1));
      expect(tester.takeException(), isNull);
    });
  }
  testWidgets('learner reads own controls without an edit action', (
    tester,
  ) async {
    await tester.pumpWidget(
      MaterialApp(
        theme: AppTheme.light(),
        home: WalletScreen(repository: Repo(viewer: 31)),
      ),
    );
    await tester.pumpAndSettle();
    await tester.tap(find.text('Open wallet statement'));
    await tester.pumpAndSettle();
    expect(find.text('Edit spending controls'), findsNothing);
    expect(find.text('Effective daily spending cap'), findsOneWidget);
  });
  testWidgets('readonly staff wallet remains readable without write controls', (
    tester,
  ) async {
    await tester.pumpWidget(
      MaterialApp(
        theme: AppTheme.light(),
        home: WalletScreen(repository: Repo(viewer: 42, readOnly: true)),
      ),
    );
    await tester.pumpAndSettle();
    await tester.tap(find.text('Open wallet statement'));
    await tester.pumpAndSettle();
    expect(find.text('Wallet history is read-only.'), findsOneWidget);
    expect(find.text('Edit spending controls'), findsNothing);
  });
}
