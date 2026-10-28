import 'dart:async';
import 'dart:convert';
import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:eschool_core/testing.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:eschool_teacher/vendor/vendor_repository.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../integration_test/support/vendor_fixture.dart';

const operation = '9c1184b0-8ce6-4cc5-8eea-1e145ec62100';
Map<String, dynamic> input() => {
  'school': 'jacaranda',
  'email': 'vendor@example.test',
  'password': 'SyntheticVendor2026',
};
VendorRepository repository(VendorFixture fixture) => VendorRepository(
  mode: AuthMode.bearer,
  createDio: () => Dio()..httpClientAdapter = fixture.api,
);

class _Gate implements HttpClientAdapter {
  _Gate(this.fallback);
  final HttpClientAdapter fallback;
  final reached = Completer<void>(), release = Completer<FakeResponse>();
  bool held = false;
  @override
  Future<ResponseBody> fetch(
    RequestOptions options,
    Stream<Uint8List>? stream,
    Future<void>? cancel,
  ) async {
    if (held && options.uri.path.endsWith('/vendor/session')) {
      if (!reached.isCompleted) reached.complete();
      final reply = await release.future;
      return ResponseBody.fromString(
        replyBody(reply.body),
        reply.status,
        headers: {
          Headers.contentTypeHeader: [Headers.jsonContentType],
        },
      );
    }
    return fallback.fetch(options, stream, cancel);
  }

  @override
  void close({bool force = false}) => fallback.close(force: force);
}

String replyBody(Object? data) => jsonEncode(data);
void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  setUp(() => SharedPreferences.setMockInitialValues({}));
  test(
    'vendor owns its session, header scope and private order queue',
    () async {
      final fake = VendorFixture(), repo = repository(VendorFixture());
      repo.dispose();
      final actual = repository(fake);
      addTearDown(actual.dispose);
      expect(await actual.signIn(input()), isTrue);
      expect(actual.profile!.schoolId, 1);
      expect(actual.orders.single.number, 'VO-000001');
      expect(actual.orders.single.amountCents, 1250);
      final requests = fake.api.requests.where(
        (r) => !r.uri.path.endsWith('sign-in'),
      );
      expect(requests, isNotEmpty);
      for (final r in requests) {
        expect(r.uri.path, contains('/v2/vendor/'));
        expect(r.headers['X-School-Id'], '1');
        expect(r.headers['X-Account-Id'], '71');
        expect(r.headers['Authorization'], 'Bearer vendor-only-token');
      }
      await actual.lookup('VO-000001');
      expect(actual.receipt!.canCollect, isTrue);
      await actual.signOut();
      expect(actual.profile, isNull);
      expect(actual.receipt, isNull);
      expect(actual.orders, isEmpty);
    },
  );
  test(
    'first sign-in returns setup without private data or a usable token',
    () async {
      final fake = VendorFixture()..needsSetup = true,
          repo = repository(VendorFixture());
      repo.dispose();
      final actual = repository(fake);
      addTearDown(actual.dispose);
      expect(await actual.signIn(input()), isTrue);
      expect(actual.profile, isNull);
      expect(actual.setup!.passwordRequired, isTrue);
      expect(actual.orders, isEmpty);
      expect(
        await actual.signIn({
          ...input(),
          'new_password': 'NewSyntheticVendor2026',
          'acceptances': {'navigateed': 'a' * 64},
        }),
        isTrue,
      );
      expect(actual.profile!.userId, 71);
    },
  );
  test('a lost collection response persists only an opaque scoped operation and recovers without another handover', () async {
    final fake = VendorFixture()..loseReply = true;
    final repo = repository(fake);
    addTearDown(repo.dispose);
    await repo.signIn(input());
    await repo.openOrder(901);
    expect(await repo.collect(), isFalse);
    expect(repo.hasPending, isTrue);
    expect(fake.writes, 1);
    final prefs = await SharedPreferences.getInstance();
    final keys = prefs.getStringList('vendor-collection-pending:1:71')!;
    expect(keys, hasLength(1));
    expect(keys.single, matches(RegExp(r'^81:[a-f0-9-]{36}$')));
    expect(keys.single, isNot(contains('navigateed:order:')));
    final restored = repository(fake);
    addTearDown(restored.dispose);
    await restored.signIn(input());
    expect(restored.hasPending, isTrue);
    expect(await restored.recover(), isTrue);
    expect(restored.receipt!.status, 'collected');
    expect(restored.receipt!.newlyCollected, isFalse);
    expect(restored.message, contains('do not hand over'));
    expect(fake.writes, 1);
    expect(restored.hasPending, isFalse);
    expect(prefs.getStringList('vendor-collection-pending:1:71'), isEmpty);
  });
  test(
    'an unknown saved operation is abandoned before a new confirmation',
    () async {
      SharedPreferences.setMockInitialValues({
        'vendor-collection-pending:1:71': ['81:$operation'],
      });
      final fake = VendorFixture()..resolve(operation, committed: false);
      final repo = repository(fake);
      addTearDown(repo.dispose);
      await repo.signIn(input());
      expect(repo.hasPending, isTrue);
      await repo.openOrder(901);
      expect(await repo.collect(), isFalse);
      expect(await repo.recover(), isTrue);
      expect(repo.hasPending, isFalse);
      expect(repo.receipt, isNull);
      expect(fake.writes, 0);
      await repo.openOrder(901);
      expect(await repo.collect(), isTrue);
      expect(fake.writes, 1);
      expect(repo.receipt!.newlyCollected, isTrue);
    },
  );
  test(
    'pending handovers stay private to the original operator namespace',
    () async {
      SharedPreferences.setMockInitialValues({
        'vendor-collection-pending:1:71': ['81:$operation'],
      });
      final fake = VendorFixture()..actor = 72;
      final repo = repository(fake);
      addTearDown(repo.dispose);
      await repo.signIn(input());
      expect(repo.profile!.userId, 72);
      expect(repo.hasPending, isFalse);
      expect(
        (await SharedPreferences.getInstance()).getStringList(
          'vendor-collection-pending:1:71',
        ),
        ['81:$operation'],
      );
    },
  );
  test('a revoked session clears all displayed order data', () async {
    final fake = VendorFixture(), repo = repository(VendorFixture());
    repo.dispose();
    final actual = repository(fake);
    addTearDown(actual.dispose);
    await actual.signIn(input());
    await actual.openOrder(901);
    fake.allowed = false;
    expect(await actual.refresh(), isFalse);
    expect(actual.profile, isNull);
    expect(actual.orders, isEmpty);
    expect(actual.receipt, isNull);
  });
  test(
    'read-only access retains receipts and refuses a new confirmation',
    () async {
      final fake = VendorFixture()..readOnly = true;
      final repo = repository(fake);
      addTearDown(repo.dispose);
      await repo.signIn(input());
      expect(repo.orders, hasLength(1));
      await repo.openOrder(901);
      expect(repo.receipt!.canCollect, isFalse);
      expect(await repo.collect(), isFalse);
      expect(fake.writes, 0);
    },
  );
  test(
    'a stale asynchronous response cannot clear or replace a new actor session',
    () async {
      final fake = VendorFixture();
      final gate = _Gate(fake.api);
      final repo = VendorRepository(
        mode: AuthMode.bearer,
        createDio: () => Dio()..httpClientAdapter = gate,
      );
      addTearDown(repo.dispose);
      await repo.signIn(input());
      gate.held = true;
      final refreshing = repo.refresh();
      await gate.reached.future;
      final old = fake.ok(fake.profile());
      repo.clear();
      gate.held = false;
      fake.actor = 72;
      await repo.signIn(input());
      gate.release.complete(FakeResponse(old));
      expect(await refreshing, isFalse);
      expect(repo.profile!.userId, 72);
      expect(repo.orders.single.number, 'VO-000001');
    },
  );
  test(
    'suspension clears private receipt views and invalidates active work',
    () async {
      final fake = VendorFixture();
      final repo = repository(fake);
      addTearDown(repo.dispose);
      await repo.signIn(input());
      await repo.openOrder(901);
      repo.suspend();
      expect(repo.receipt, isNull);
      expect(repo.orders, isEmpty);
      expect(await repo.lookup('VO-000001'), isFalse);
      expect(await repo.resume(), isTrue);
      expect(repo.orders, hasLength(1));
    },
  );
  test(
    'a different login school never becomes an authenticated vendor view',
    () async {
      final fake = VendorFixture();
      fake.api.routes['POST v2/vendor/sign-in'] = fake.ok({
        'authenticated': true,
        'token': 'opaque',
        'profile': {...fake.profile(), 'school_slug': 'another-school'},
        'password_change_required': false,
        'documents': [],
      });
      final repo = repository(fake);
      addTearDown(repo.dispose);
      expect(await repo.signIn(input()), isFalse);
      expect(repo.profile, isNull);
      expect(repo.orders, isEmpty);
    },
  );
  for (final field in [
    'school_id',
    'actor_user_id',
    'vendor_id',
    'amount_cents',
    'net_cents',
    'vat_cents',
    'currency',
    'status',
    'created_at',
    'processed_at',
    'lines',
    'order_number',
  ]) {
    test('malformed $field is refused before exposing an order', () async {
      final fake = VendorFixture();
      final profile = VendorProfile(fake.profile());
      final data = fake.receipt();
      data[field] = switch (field) {
        'school_id' => 2,
        'actor_user_id' => 72,
        'vendor_id' => 82,
        'amount_cents' => 1250.5,
        'net_cents' => 1250.5,
        'vat_cents' => 1,
        'currency' => 'USD',
        'status' => 'unknown',
        'created_at' => '2026-13-99T09:00:00.000Z',
        'processed_at' => '2026-10-28T09:00:00.000Z',
        'lines' => <Object>[],
        'order_number' => 'OTHER-1',
        _ => null,
      };
      expect(
        () => VendorReceipt(data, profile, profile.counters.single),
        throwsFormatException,
      );
    });
  }
  test(
    'settlement reads preserve exact cents and clear private history on denial',
    () async {
      final fixture = VendorFixture();
      final repo = repository(fixture);
      addTearDown(repo.dispose);
      expect(await repo.signIn(input()), isTrue);
      expect(await repo.loadSettlements(), isTrue);
      expect(repo.settlements.single.payoutCents, 1250);
      expect(await repo.openSettlement(501), isTrue);
      expect(repo.settlement!.lines.single.number, 'VO-000001');
      expect(repo.settlement!.collectorFeeCents, 0);
      fixture.allowed = false;
      expect(await repo.loadSettlements(), isFalse);
      expect(repo.profile, isNull);
      expect(repo.settlement, isNull);
      expect(repo.settlements, isEmpty);
      expect(fixture.writes, 0);
    },
  );
  for (final field in ['school_id', 'actor_user_id', 'vendor_id']) {
    test(
      'settlement list refuses changed $field before exposing totals',
      () async {
        final fixture = VendorFixture();
        fixture.api.routes['GET v2/vendor/counters/81/settlements'] = fixture
            .ok({
              'school_id': 1,
              'actor_user_id': 71,
              'vendor_id': 81,
              'items': [fixture.settlement()],
              'page': 1,
              'last_page': 1,
              field: 999,
            });
        final repo = repository(fixture);
        addTearDown(repo.dispose);
        expect(await repo.signIn(input()), isTrue);
        expect(await repo.loadSettlements(), isFalse);
        expect(repo.settlements, isEmpty);
        expect(repo.settlement, isNull);
      },
    );
  }
  for (final field in [
    'gross_cents',
    'sales_net_cents',
    'vat_cents',
    'collector_fee_cents',
    'payout_cents',
    'currency',
    'collected_by',
    'status',
    'period_from',
    'order_count',
    'settled_at',
    'paid_on',
    'lines',
  ]) {
    test('malformed settlement $field is refused', () {
      final fixture = VendorFixture();
      final data = fixture.settlement(detail: true);
      data[field] = switch (field) {
        'gross_cents' => 1250.5,
        'sales_net_cents' => 1250.5,
        'vat_cents' => 1,
        'collector_fee_cents' => 10,
        'payout_cents' => 1240,
        'currency' => 'USD',
        'collected_by' => 'navigateed',
        'status' => 'unknown',
        'period_from' => '2026-02-30',
        'order_count' => 2,
        'settled_at' => null,
        'paid_on' => null,
        'lines' => <Object>[],
        _ => null,
      };
      expect(
        () => VendorSettlement(
          data,
          VendorProfile(fixture.profile()).counters.single,
          detail: true,
        ),
        throwsFormatException,
      );
    });
  }
}
