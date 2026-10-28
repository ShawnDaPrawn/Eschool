import 'package:dio/dio.dart';
import 'package:eschool_core/testing.dart';

class VendorFixture {
  VendorFixture() {
    api = FakeApi(
      routes: {
        'POST v2/vendor/sign-in': (RequestOptions q) {
          if (needsSetup && (q.data as Map)['new_password'] == null)
            return FakeResponse({
              'error': true,
              'message': 'Complete setup',
              'code': 'VENDOR_SETUP_REQUIRED',
              'data': {
                'authenticated': false,
                'token': null,
                'profile': null,
                'password_change_required': true,
                'documents': [
                  {
                    'document': 'navigateed',
                    'version': 'a' * 64,
                    'title': 'Synthetic current policy',
                    'text': 'Synthetic policy for private counter operation.',
                  },
                ],
              },
            }, status: 409);
          return ok({
            'authenticated': true,
            'token': 'vendor-only-token',
            'profile': profile(),
            'password_change_required': false,
            'documents': [],
          });
        },
        'GET v2/vendor/session': (RequestOptions _) => answer(profile()),
        'POST v2/vendor/sign-out': (RequestOptions _) => ok(null),
        'GET v2/vendor/counters/81/orders': (RequestOptions q) => answer({
          'school_id': 1,
          'actor_user_id': actor,
          'vendor_id': 81,
          'status': q.uri.queryParameters['status'] ?? 'ready',
          'items': (q.uri.queryParameters['status'] ?? 'ready') == status
              ? [receipt()]
              : <Object>[],
          'page': 1,
          'last_page': 1,
        }),
        'GET v2/vendor/counters/81/orders/901': (RequestOptions _) =>
            answer(receipt()),
        'POST v2/vendor/counters/81/lookup': (RequestOptions _) =>
            answer(receipt()),
        'POST v2/vendor/counters/81/collect': (RequestOptions q) {
          if (!allowed) return denied;
          final key = (q.data as Map)['operation_id'] as String;
          final replay = operations.contains(key);
          if (status != 'ready' && !replay)
            return const FakeResponse({
              'error': true,
              'message': 'Already collected',
              'code': 409,
            }, status: 409);
          if (!replay) {
            writes++;
            status = 'collected';
            operations.add(key);
          }
          resolve(key, committed: true);
          if (loseReply)
            return const FakeResponse({
              'error': true,
              'message': 'Unavailable',
              'code': 503,
            }, status: 503);
          return answer({...receipt(), 'newly_collected': !replay});
        },
      },
    );
  }
  late final FakeApi api;
  bool allowed = true, readOnly = false, loseReply = false, needsSetup = false;
  int actor = 71, writes = 0;
  String status = 'ready';
  final operations = <String>{};
  static const denied = FakeResponse({
    'error': true,
    'message': 'Vendor access changed',
    'code': 403,
  }, status: 403);
  Object answer(Object? data) => allowed ? ok(data) : denied;
  Map<String, dynamic> ok(Object? data) => {
    'error': false,
    'message': '',
    'data': data,
    'code': null,
  };
  Map<String, dynamic> profile() => {
    'school_id': 1,
    'school_name': 'Synthetic School',
    'school_slug': 'jacaranda',
    'user_id': actor,
    'name': 'Synthetic Vendor',
    'counters': [
      {
        'id': 81,
        'name': 'Synthetic tuckshop',
        'currency': 'ZAR',
        'can_collect': !readOnly,
      },
    ],
  };
  Map<String, dynamic> receipt() => {
    'school_id': 1,
    'actor_user_id': actor,
    'vendor_id': 81,
    'id': 901,
    'order_number': 'VO-000001',
    'status': status,
    'currency': 'ZAR',
    'amount_cents': 1250,
    'net_cents': 1250,
    'vat_cents': 0,
    'created_at': '2026-10-28T09:00:00.000Z',
    'processed_at': status == 'ready' ? null : '2026-10-28T09:01:00.000Z',
    'can_collect': !readOnly && status == 'ready',
    'lines': [
      {
        'product_name': 'Synthetic sandwich',
        'sku': 'SANDWICH-01',
        'unit_label': 'each',
        'quantity': 1,
        'gross_cents': 1250,
        'net_cents': 1250,
        'vat_cents': 0,
      },
    ],
  };
  void resolve(String key, {required bool committed}) {
    api.routes['POST v2/vendor/counters/81/operations/$key/resolve'] =
        (RequestOptions _) => answer({
          'school_id': 1,
          'actor_user_id': actor,
          'vendor_id': 81,
          'operation_id': key,
          'status': committed ? 'committed' : 'abandoned',
          'order': committed ? receipt() : null,
        });
  }
}
