import 'dart:async';
import 'package:dio/dio.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:eschool_fees/eschool_fees.dart';
import 'package:eschool_fees/testing.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

FakeResponse _ok(Object data) =>
    FakeResponse({'error': false, 'message': 'ok', 'data': data, 'code': 200});

class _Repository implements FeeOptInRepository {
  final data = feeOptionalItemsFixture();
  bool active = true, failFirst = false;
  Object? error;
  Completer<ParentFeeOptionalItemsPage>? pending;
  final changes = <({bool active, String key})>[];
  @override
  int get schoolId => 1;
  @override
  int get accountId => 21;
  @override
  bool get isActive => active;
  @override
  Future<ParentFeeOptionalItemsPage> items(int familyId, {int page = 1}) async {
    if (error != null) throw error!;
    return pending?.future ?? ParentFeeOptionalItemsPage.fromJson(data);
  }

  @override
  Future<ParentFeeItemConsentReceipt> change(
    int familyId,
    int year,
    ParentFeeOptionalItem offer, {
    required bool active,
    required String operationId,
  }) async {
    changes.add((active: active, key: operationId));
    if (failFirst && changes.length == 1) throw ApiException('network_error');
    final row = (data['items'] as List).cast<Map<String, dynamic>>().firstWhere(
      (r) => r['fee_item_id'] == offer.feeItemId,
    );
    row['selected'] = active;
    row['current_terms_approved'] = active;
    row['revision'] = offer.revision + 1;
    return ParentFeeItemConsentReceipt(
      id: 40 + changes.length,
      schoolId: 1,
      holderUserId: 21,
      feeAccountId: 7,
      feeItemId: offer.feeItemId,
      sessionYearId: 3,
      learnerId: offer.learnerId,
      revision: offer.revision + 1,
      active: active,
      recordedAt: DateTime.utc(2026, 6, 15),
    );
  }
}

class _Statements implements FeeStatementRepository {
  Object? error;
  @override
  int get schoolId => 1;
  @override
  int get accountId => 21;
  @override
  bool get isActive => true;
  @override
  Future<ParentFeeAccountsPage> accounts({
    int page = 1,
    int perPage = 20,
  }) async {
    if (error != null) throw error!;
    return ParentFeeAccountsPage.fromJson(feeAccountsFixture());
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
    return ParentFeeStatement.fromJson(feeStatementFixture());
  }
}

ApiFeeOptInRepository _api(FakeApi api, {String Function()? token}) =>
    ApiFeeOptInRepository(
      ApiClient(
        authToken: token ?? () => 'synthetic',
        onUnauthorized: () {},
        createDio: () => Dio()..httpClientAdapter = api,
      ),
      schoolId: 1,
      accountId: 21,
      contextActive: () => true,
    );
void main() {
  for (final defect in [
    'school',
    'holder',
    'family',
    'fraction',
    'tax',
    'target',
    'fingerprint',
    'approval',
    'duplicate',
  ]) {
    test('private optional offers reject $defect corruption', () async {
      final data = feeOptionalItemsFixture();
      final row = (data['items'] as List).first as Map<String, dynamic>;
      if (defect == 'school') data['school_id'] = 2;
      if (defect == 'holder') data['holder_user_id'] = 22;
      if (defect == 'family') data['fee_account_id'] = 8;
      if (defect == 'fraction') row['share_cents'] = 500.5;
      if (defect == 'tax') row['vat_cents'] = 502;
      if (defect == 'target') row['learner_id'] = 11;
      if (defect == 'fingerprint') row['fingerprint'] = 'invalid';
      if (defect == 'approval') row['current_terms_approved'] = true;
      if (defect == 'duplicate') {
        (data['items'] as List)[1] = Map<String, dynamic>.from(row);
      }
      final api = FakeApi(
        routes: {'GET v2/parent/fees/accounts/7/optional-items': _ok(data)},
      );
      await expectLater(_api(api).items(7), throwsA(isA<ApiException>()));
    });
  }
  test(
    'token replacement rejects an otherwise valid private offer response',
    () async {
      var token = 'first';
      final api = FakeApi(
        routes: {
          'GET v2/parent/fees/accounts/7/optional-items': (RequestOptions _) {
            token = 'second';
            return _ok(feeOptionalItemsFixture());
          },
        },
      );
      final repo = _api(api, token: () => token);
      await expectLater(repo.items(7), throwsA(isA<ApiException>()));
    },
  );
  test(
    'unavailable selected history remains withdrawable with explicit null prices',
    () async {
      final data = feeOptionalItemsFixture();
      final row = (data['items'] as List).first as Map<String, dynamic>;
      row.addAll({
        'available': false,
        'price_cents': null,
        'share_cents': null,
        'vat_cents': null,
        'fingerprint': null,
        'selected': true,
        'revision': 1,
      });
      final api = FakeApi(
        routes: {'GET v2/parent/fees/accounts/7/optional-items': _ok(data)},
      );
      final result = await _api(api).items(7);
      expect(result.items.first.priceCents, isNull);
      expect(result.items.first.selected, isTrue);
    },
  );
  test(
    'unknown future billing modes stay readable without granting approval',
    () async {
      final data = feeOptionalItemsFixture();
      ((data['items'] as List).first as Map)['billing'] = 'future_terms';
      final api = FakeApi(
        routes: {'GET v2/parent/fees/accounts/7/optional-items': _ok(data)},
      );
      final result = await _api(api).items(7);
      expect(supportedOptionalFee(result.items.first), isFalse);
    },
  );
  test(
    'writes use the reviewed UUID, scope and revision and reject a foreign receipt',
    () async {
      final data = feeOptionalItemsFixture();
      final offer = ParentFeeOptionalItemsPage.fromJson(data).items.first;
      final api = FakeApi(
        routes: {
          'POST v2/parent/fees/accounts/7/optional-items/91/consents': _ok({
            'id': 41,
            'school_id': 2,
            'holder_user_id': 21,
            'fee_account_id': 7,
            'fee_item_id': 91,
            'session_year_id': 3,
            'learner_id': null,
            'revision': 1,
            'active': true,
            'recorded_at': '2026-06-15T12:00:00Z',
          }),
        },
      );
      await expectLater(
        _api(api).change(
          7,
          3,
          offer,
          active: true,
          operationId: '8aef50e7-2429-4d2c-a523-668ae436c4d4',
        ),
        throwsA(isA<ApiException>()),
      );
      final request = api.requests.single;
      expect(request.headers['X-School-Id'], '1');
      expect(request.headers['X-Account-Id'], '21');
      expect(
        request.headers['Idempotency-Key'],
        '8aef50e7-2429-4d2c-a523-668ae436c4d4',
      );
      expect((request.data as Map)['revision'], 0);
      expect((request.data as Map)['confirmed'], true);
    },
  );
  test('uncertain approval retries retain the exact reviewed UUID', () async {
    final repo = _Repository()..failFirst = true;
    final cubit = FeeOptInCubit(repo, 7);
    await cubit.load();
    cubit.review((cubit.state as FeeOptInReady).page.items.first, active: true);
    final key = (cubit.state as FeeOptInReview).operationId;
    await cubit.confirm();
    expect((cubit.state as FeeOptInReview).retry, isTrue);
    expect((cubit.state as FeeOptInReview).operationId, key);
    await cubit.confirm();
    expect(repo.changes.map((c) => c.key), [key, key]);
    expect(cubit.state, isA<FeeOptInReady>());
    await cubit.close();
  });
  test(
    'a late private response cannot survive context loss or disposal',
    () async {
      final repo = _Repository()
        ..pending = Completer<ParentFeeOptionalItemsPage>();
      final cubit = FeeOptInCubit(repo, 7);
      final pending = cubit.load();
      repo.active = false;
      repo.pending!.complete(
        ParentFeeOptionalItemsPage.fromJson(feeOptionalItemsFixture()),
      );
      await pending;
      expect((cubit.state as FeeOptInFailure).accessChanged, isTrue);
      await cubit.close();
      final other = _Repository()
        ..pending = Completer<ParentFeeOptionalItemsPage>();
      final disposed = FeeOptInCubit(other, 7);
      final late = disposed.load();
      await disposed.close();
      other.pending!.complete(
        ParentFeeOptionalItemsPage.fromJson(feeOptionalItemsFixture()),
      );
      await late;
      expect(disposed.state, isA<FeeOptInLoading>());
    },
  );
  testWidgets(
    'leaving a denied optional view never restores a cached private statement',
    (tester) async {
      final statements = _Statements();
      final optional = _Repository();
      await tester.pumpWidget(
        MaterialApp(
          theme: AppTheme.light(),
          home: FeeStatementScreen(
            repository: statements,
            optionalItems: optional,
          ),
        ),
      );
      await tester.pumpAndSettle();
      await tester.tap(find.text('Open statement'));
      await tester.pumpAndSettle();
      expect(find.text('Asha Synthetic'), findsOneWidget);
      await tester.tap(find.text('Optional fee items'));
      await tester.pumpAndSettle();
      expect(find.text('Synthetic optional family excursion'), findsOneWidget);
      optional.error = ApiException('forbidden', statusCode: 403);
      statements.error = ApiException('forbidden', statusCode: 403);
      await tester.tap(find.byTooltip('Refresh optional fee items'));
      await tester.pumpAndSettle();
      expect(find.text('Synthetic optional family excursion'), findsNothing);
      await tester.tap(find.byTooltip('Back'));
      await tester.pumpAndSettle();
      expect(find.text('Your fee access changed'), findsOneWidget);
      expect(find.text('Asha Synthetic'), findsNothing);
    },
  );
  for (final width in [390.0, 820.0, 1280.0]) {
    testWidgets('parent reviews price, VAT, billing and withdrawal at $width', (
      tester,
    ) async {
      tester.view.physicalSize = Size(width, 1000);
      tester.view.devicePixelRatio = 1;
      addTearDown(tester.view.resetPhysicalSize);
      addTearDown(tester.view.resetDevicePixelRatio);
      final repo = _Repository();
      await tester.pumpWidget(
        MaterialApp(
          theme: AppTheme.light(),
          home: FeeOptInScreen(repository: repo, familyId: 7, onBack: () {}),
        ),
      );
      await tester.pumpAndSettle();
      expect(find.text('Once per school year'), findsOneWidget);
      expect(find.text('ZAR 5.01'), findsOneWidget);
      expect(find.text('ZAR 0.66'), findsOneWidget);
      final review = find.widgetWithText(FilledButton, 'Review optional fee');
      await tester.ensureVisible(review);
      await tester.tap(review);
      await tester.pumpAndSettle();
      final confirm = find.widgetWithText(FilledButton, 'Record my approval');
      expect(tester.widget<FilledButton>(confirm).onPressed, isNull);
      await tester.ensureVisible(find.byType(CheckboxListTile));
      await tester.tap(find.byType(CheckboxListTile));
      await tester.pumpAndSettle();
      await tester.ensureVisible(confirm);
      await tester.tap(confirm);
      await tester.pumpAndSettle();
      expect(find.text('Your approval was recorded.'), findsOneWidget);
      expect(repo.changes.single.active, isTrue);
      final family = find.byKey(const ValueKey('optional-91-null'));
      final withdraw = find.descendant(
        of: family,
        matching: find.widgetWithText(OutlinedButton, 'Withdraw approval'),
      );
      await tester.ensureVisible(withdraw);
      await tester.tap(withdraw);
      await tester.pumpAndSettle();
      expect(
        find.textContaining('It does not cancel invoices'),
        findsOneWidget,
      );
      await tester.ensureVisible(find.byType(CheckboxListTile));
      await tester.tap(find.byType(CheckboxListTile));
      await tester.pumpAndSettle();
      final stop = find.widgetWithText(FilledButton, 'Confirm withdrawal');
      await tester.ensureVisible(stop);
      await tester.tap(stop);
      await tester.pumpAndSettle();
      expect(find.text('Your approval was withdrawn.'), findsOneWidget);
      expect(repo.changes.map((c) => c.active), [true, false]);
      expect(tester.takeException(), isNull);
    });
  }
  testWidgets(
    'read-only offers and guardian viewing permission cannot submit approval',
    (tester) async {
      final repo = _Repository();
      repo.data['writable'] = false;
      await tester.pumpWidget(
        MaterialApp(
          theme: AppTheme.light(),
          home: FeeOptInScreen(repository: repo, familyId: 7, onBack: () {}),
        ),
      );
      await tester.pumpAndSettle();
      expect(
        find.text('Optional fee items are read-only while fees are disabled.'),
        findsOneWidget,
      );
      expect(
        tester
            .widget<FilledButton>(
              find.widgetWithText(FilledButton, 'Review optional fee'),
            )
            .onPressed,
        isNull,
      );
      expect(repo.changes, isEmpty);
      repo.active = false;
      await tester.pumpWidget(
        MaterialApp(
          home: FeeOptInScreen(repository: repo, familyId: 7, onBack: () {}),
        ),
      );
      await tester.pumpAndSettle();
      expect(find.text('Your fee access changed'), findsOneWidget);
      expect(find.text('Synthetic optional family excursion'), findsNothing);
    },
  );
}
