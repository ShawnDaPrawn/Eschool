import 'dart:async';
import 'package:dio/dio.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:eschool_fees/eschool_fees.dart';
import 'package:eschool_fees/testing.dart';
import 'package:eschool_fees/src/fee_text.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

class _Repository implements FeeStatementRepository {
  _Repository({this.withCredits = false, this.withItems = false});
  final bool withItems;
  final bool withCredits;
  bool active = true;
  Object? error;
  Completer<ParentFeeStatement>? pending;
  @override
  int get schoolId => 1;
  @override
  int get accountId => 21;
  @override
  bool get isActive => active;
  @override
  Future<ParentFeeAccountsPage> accounts({
    int page = 1,
    int perPage = 20,
  }) async {
    if (error != null) throw error!;
    return ParentFeeAccountsPage.fromJson(
      (withCredits || withItems)
          ? feeCreditedAccountsFixture()
          : feeAccountsFixture(),
    );
  }

  @override
  Future<ParentFeeStatement> statement(
    int familyId, {
    int chargesPage = 1,
    int receiptsPage = 1,
    int creditsPage = 1,
    int perPage = 20,
  }) async {
    if (error != null) throw error!;
    return pending?.future ??
        Future.value(
          ParentFeeStatement.fromJson(
            withItems
                ? feeItemStatementFixture()
                : withCredits
                ? feeCreditedStatementFixture()
                : feeStatementFixture(),
          ),
        );
  }
}

Map<String, Object?> _ok(Object data) => {
  'error': false,
  'message': '',
  'code': 200,
  'data': data,
};

void main() {
  test('exact cent formatting does not round one-cent or large balances', () {
    expect(feeAmount('ZAR', 1), 'ZAR 0.01');
    expect(feeAmount('ZAR', 1001), 'ZAR 10.01');
    expect(feeAmount('ZAR', 100000000000), 'ZAR 1000000000.00');
  });

  test(
    'repository pins owner and school and sends only private scoped reads',
    () async {
      final logs = <String>[];
      final originalPrint = debugPrint;
      debugPrint = (String? message, {int? wrapWidth}) {
        if (message != null) logs.add(message);
      };
      addTearDown(() => debugPrint = originalPrint);
      final api = FakeApi(
        routes: {
          'GET v2/parent/fees/accounts': _ok(feeAccountsFixture()),
          'GET v2/parent/fees/accounts/7/statement': _ok(feeStatementFixture()),
        },
      );
      final client = ApiClient(
        authToken: () => 'synthetic-token',
        onUnauthorized: () {},
        createDio: () => Dio()..httpClientAdapter = api,
      );
      final repo = ApiFeeStatementRepository(
        client,
        schoolId: 1,
        accountId: 21,
        contextActive: () => true,
      );
      expect((await repo.accounts()).items.single.outstandingCents, 102);
      expect((await repo.statement(7)).charges.items, hasLength(2));
      for (final request in api.requests) {
        expect(request.method, 'GET');
        expect(request.headers['X-School-Id'].toString(), '1');
        expect(request.headers['X-Account-Id'].toString(), '21');
        expect(request.headers['Authorization'], 'Bearer synthetic-token');
      }
      expect(
        logs.where((line) => line.contains('parent/fees/accounts')),
        isEmpty,
      );
    },
  );

  for (final wrong in ['school', 'holder', 'amount']) {
    test(
      'repository rejects a mismatched $wrong response before returning it',
      () async {
        final data = feeStatementFixture();
        if (wrong == 'school') data['school_id'] = 2;
        if (wrong == 'holder') data['holder_user_id'] = 99;
        if (wrong == 'amount') data['outstanding_cents'] = 0;
        final api = FakeApi(
          routes: {'GET v2/parent/fees/accounts/7/statement': _ok(data)},
        );
        final repo = ApiFeeStatementRepository(
          ApiClient(
            authToken: () => 'synthetic',
            onUnauthorized: () {},
            createDio: () => Dio()..httpClientAdapter = api,
          ),
          schoolId: 1,
          accountId: 21,
          contextActive: () => true,
        );
        await expectLater(repo.statement(7), throwsA(isA<ApiException>()));
      },
    );
  }

  test(
    'token replacement while a response is in flight rejects its private data',
    () async {
      var token = 'first-token';
      final api = FakeApi(
        routes: {
          'GET v2/parent/fees/accounts': (RequestOptions request) {
            token = 'second-token';
            return _ok(feeAccountsFixture());
          },
        },
      );
      final repo = ApiFeeStatementRepository(
        ApiClient(
          authToken: () => token,
          onUnauthorized: () {},
          createDio: () => Dio()..httpClientAdapter = api,
        ),
        schoolId: 1,
        accountId: 21,
        contextActive: () => true,
      );
      await expectLater(repo.accounts(), throwsA(isA<ApiException>()));
    },
  );

  test('future receipt methods keep readable financial history', () async {
    final data = feeStatementFixture();
    (data['receipts']['items'] as List).first['method'] = 'future_provider';
    final api = FakeApi(
      routes: {'GET v2/parent/fees/accounts/7/statement': _ok(data)},
    );
    final repo = ApiFeeStatementRepository(
      ApiClient(
        authToken: () => 'synthetic',
        onUnauthorized: () {},
        createDio: () => Dio()..httpClientAdapter = api,
      ),
      schoolId: 1,
      accountId: 21,
      contextActive: () => true,
    );
    expect(
      (await repo.statement(7)).receipts.items.first.method,
      ParentFeeReceiptMethodEnum.unknownDefaultOpenApi,
    );
  });

  test(
    'a late statement cannot replace a newer account view or survive revoked access',
    () async {
      final repo = _Repository()..pending = Completer<ParentFeeStatement>();
      final cubit = FeeStatementCubit(repo);
      final pending = cubit.statement(7);
      await cubit.accounts();
      repo.pending!.complete(
        ParentFeeStatement.fromJson(feeStatementFixture()),
      );
      await pending;
      expect(cubit.state, isA<FeeAccountsReady>());
      repo.active = false;
      await cubit.accounts();
      expect(cubit.state, isA<FeeStatementFailure>());
      expect((cubit.state as FeeStatementFailure).accessChanged, isTrue);
      await cubit.close();
    },
  );

  test(
    'a request completing after disposal never emits private data',
    () async {
      final repo = _Repository()..pending = Completer<ParentFeeStatement>();
      final cubit = FeeStatementCubit(repo);
      final pending = cubit.statement(7);
      await cubit.close();
      repo.pending!.complete(
        ParentFeeStatement.fromJson(feeStatementFixture()),
      );
      await pending;
      expect(cubit.isClosed, isTrue);
    },
  );

  test(
    'new credit statement retains gross, net and restored receipt arithmetic',
    () async {
      final api = FakeApi(
        routes: {
          'GET v2/parent/fees/accounts': _ok(feeCreditedAccountsFixture()),
          'GET v2/parent/fees/accounts/7/statement': _ok(
            feeCreditedStatementFixture(),
          ),
        },
      );
      final repo = ApiFeeStatementRepository(
        ApiClient(
          authToken: () => 'synthetic',
          onUnauthorized: () {},
          createDio: () => Dio()..httpClientAdapter = api,
        ),
        schoolId: 1,
        accountId: 21,
        contextActive: () => true,
      );
      expect((await repo.accounts()).items.single.creditCents, 350);
      final statement = await repo.statement(7);
      expect(statement.grossChargedCents, 1002);
      expect(statement.creditedCents, 300);
      expect(statement.chargedCents, 702);
      expect(statement.allocatedCents, 600);
      expect(statement.receipts.items.first.releasedCents, 300);
      expect(statement.creditNotes!.items.single.releasedCents, 300);
    },
  );
  for (final wrong in ['gross', 'note', 'page', 'released']) {
    test('malformed $wrong correction data is rejected', () async {
      final data = feeCreditedStatementFixture();
      if (wrong == 'gross') data['gross_charged_cents'] = 702;
      if (wrong == 'note') {
        ((data['credit_notes'] as Map)['items'] as List).first['amount_cents'] =
            301;
      }
      if (wrong == 'page') (data['credit_notes'] as Map)['current_page'] = 2;
      if (wrong == 'released') {
        ((data['receipts'] as Map)['items'] as List).first['released_cents'] =
            -1;
      }
      final api = FakeApi(
        routes: {'GET v2/parent/fees/accounts/7/statement': _ok(data)},
      );
      final repo = ApiFeeStatementRepository(
        ApiClient(
          authToken: () => 'synthetic',
          onUnauthorized: () {},
          createDio: () => Dio()..httpClientAdapter = api,
        ),
        schoolId: 1,
        accountId: 21,
        contextActive: () => true,
      );
      await expectLater(repo.statement(7), throwsA(isA<ApiException>()));
    });
  }
  test(
    'item scope and future item categories keep private statement history readable',
    () async {
      final data = feeItemStatementFixture();
      final rows = ((data['charges'] as Map)['items'] as List);
      (rows.first as Map)['charge_kind'] = 'future_category';
      final api = FakeApi(
        routes: {'GET v2/parent/fees/accounts/7/statement': _ok(data)},
      );
      final repo = ApiFeeStatementRepository(
        ApiClient(
          authToken: () => 'synthetic',
          onUnauthorized: () {},
          createDio: () => Dio()..httpClientAdapter = api,
        ),
        schoolId: 1,
        accountId: 21,
        contextActive: () => true,
      );
      final result = await repo.statement(7);
      expect(
        result.charges.items.first.description,
        'Synthetic family activity fee',
      );
      expect(result.charges.items.last.chargeScope?.value, 'family');
      expect(result.chargedCents, 702);
    },
  );
  for (final width in [390.0, 820.0, 1280.0]) {
    testWidgets(
      'family item scope and credited shares remain clear at $width',
      (tester) async {
        tester.view.physicalSize = Size(width, 1000);
        tester.view.devicePixelRatio = 1;
        addTearDown(tester.view.resetPhysicalSize);
        addTearDown(tester.view.resetDevicePixelRatio);
        final repo = _Repository(withItems: true);
        await tester.pumpWidget(
          MaterialApp(
            theme: AppTheme.light(),
            home: FeeStatementScreen(repository: repo),
          ),
        );
        await tester.pumpAndSettle();
        await tester.tap(find.text('Open statement'));
        await tester.pumpAndSettle();
        await tester.scrollUntilVisible(find.text('Asha Synthetic'), 200);
        expect(find.text('Additional fee'), findsWidgets);
        expect(
          find.text('Family charge apportioned to this learner'),
          findsWidgets,
        );
        expect(find.text('Synthetic family activity fee'), findsWidgets);
        expect(tester.takeException(), isNull);
      },
    );
  }
  for (final width in [390.0, 820.0, 1280.0]) {
    testWidgets(
      'credit notes and restored funds remain usable and clear on denial at $width',
      (tester) async {
        tester.view.physicalSize = Size(width, 1000);
        tester.view.devicePixelRatio = 1;
        addTearDown(tester.view.resetPhysicalSize);
        addTearDown(tester.view.resetDevicePixelRatio);
        final repo = _Repository(withCredits: true);
        await tester.pumpWidget(
          MaterialApp(
            theme: AppTheme.light(),
            home: FeeStatementScreen(
              repository: repo,
              learnerNames: const {12: 'Cara Synthetic'},
            ),
          ),
        );
        await tester.pumpAndSettle();
        expect(find.text('ZAR 3.50'), findsOneWidget);
        await tester.tap(find.text('Open statement'));
        await tester.pumpAndSettle();
        expect(find.text('Your original invoice shares'), findsOneWidget);
        expect(find.text('Your net charges'), findsOneWidget);
        await tester.scrollUntilVisible(find.text('FEE-CN-SYNTHETIC-1'), 250);
        expect(find.text('Billing correction'), findsOneWidget);
        expect(find.text('Restored to receipts'), findsOneWidget);
        expect(tester.takeException(), isNull);
        repo.error = ApiException('revoked', statusCode: 403);
        await tester.tap(find.byTooltip('Refresh fee statements'));
        await tester.pumpAndSettle();
        expect(find.text('Your fee access changed'), findsOneWidget);
        expect(find.text('FEE-CN-SYNTHETIC-1'), findsNothing);
        expect(find.text('Synthetic family'), findsNothing);
      },
    );
  }

  for (final width in [390.0, 820.0, 1280.0]) {
    testWidgets(
      'two-child statement and receipts remain usable at $width pixels',
      (tester) async {
        tester.view.physicalSize = Size(width, 1000);
        tester.view.devicePixelRatio = 1;
        addTearDown(tester.view.resetPhysicalSize);
        addTearDown(tester.view.resetDevicePixelRatio);
        final repo = _Repository();
        await tester.pumpWidget(
          MaterialApp(
            theme: AppTheme.light(),
            home: FeeStatementScreen(
              repository: repo,
              learnerNames: const {12: 'Cara Synthetic'},
            ),
          ),
        );
        await tester.pumpAndSettle();
        expect(find.text('Synthetic family'), findsOneWidget);
        expect(find.text('ZAR 1.02'), findsOneWidget);
        expect(find.text('ZAR 0.50'), findsOneWidget);
        await tester.tap(find.text('Open statement'));
        await tester.pumpAndSettle();
        await tester.scrollUntilVisible(find.text('Asha Synthetic'), 150);
        expect(find.text('Asha Synthetic'), findsOneWidget);
        await tester.scrollUntilVisible(find.text('Cara Synthetic'), 150);
        expect(find.text('Cara Synthetic'), findsOneWidget);
        await tester.scrollUntilVisible(find.text('FEE-PAY-SYNTHETIC-2'), 200);
        expect(
          find.text('Reserved for learner: Cara Synthetic'),
          findsOneWidget,
        );
        expect(tester.takeException(), isNull);
        repo.error = ApiException('revoked', statusCode: 403);
        await tester.tap(find.byTooltip('Refresh fee statements'));
        await tester.pumpAndSettle();
        expect(find.text('Your fee access changed'), findsOneWidget);
        expect(find.text('Synthetic family'), findsNothing);
        expect(find.text('Cara Synthetic'), findsNothing);
        expect(find.text('FEE-PAY-SYNTHETIC-2'), findsNothing);
        expect(tester.takeException(), isNull);
      },
    );
  }
}
