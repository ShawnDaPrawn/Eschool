import 'package:dio/dio.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';

import '../../testing.dart'
    show
        walletTransferPageFixture,
        walletDepositPageFixture,
        walletWithdrawalPageFixture;
import '../commerce_repository.dart';
import '../wallet_repository.dart';

/// Synthetic service boundary shared by wallet widgets and actual app journeys.
class CommerceFakeServer {
  CommerceFakeServer({this.actor = 21, this.learner = false, FakeApi? api})
    : api = api ?? FakeApi(routes: {}) {
    this.api.routes.addAll({
      'GET v2/wallets': (RequestOptions _) => _answer(walletIndex()),
      'GET v2/wallets/transfers': (RequestOptions _) =>
          _answer(walletTransferPageFixture(viewer: actor, canTransfer: false)),
      'GET v2/wallets/deposits': (RequestOptions _) =>
          _answer(walletDepositPageFixture(viewer: actor)),
      'GET v2/wallets/withdrawals': (RequestOptions _) =>
          _answer(walletWithdrawalPageFixture(viewer: actor)),
      'GET v2/commerce/vendors': (RequestOptions _) => _answer(vendors()),
      'GET v2/commerce/vendors/81': (RequestOptions _) => _answer(catalogue()),
      'GET v2/commerce/orders': (RequestOptions _) => _answer(orderPage()),
      'GET v2/commerce/orders/901': (RequestOptions _) => _answer(receipt()),
      'POST v2/commerce/quotes': (RequestOptions q) {
        if (!allowed) return denied;
        final data = Map<String, dynamic>.from(q.data as Map);
        selectedWallet = data['wallet_id'] as int;
        quantity = ((data['lines'] as List).single as Map)['quantity'] as int;
        return _answer(quote());
      },
      'POST v2/commerce/orders': (RequestOptions q) {
        if (!allowed) return denied;
        writes++;
        operation = (q.data as Map)['operation_id'] as String;
        if (!purchased) {
          balances[selectedWallet] = balances[selectedWallet]! - amount;
          purchased = true;
        }
        _resolve(operation!);
        return losePurchaseReply
            ? const FakeResponse({
                'error': true,
                'message': 'Unavailable',
                'code': 503,
              }, status: 503)
            : _answer(receipt());
      },
      'POST v2/commerce/orders/901/cancel': (RequestOptions q) {
        if (!allowed) return denied;
        cancellations++;
        if (status != 'ready') {
          return const FakeResponse({
            'error': true,
            'message': 'Order already processed',
            'code': 409,
          }, status: 409);
        }
        status = 'cancelled';
        balances[selectedWallet] = balances[selectedWallet]! + amount;
        _resolve((q.data as Map)['operation_id'] as String);
        return loseCancelReply
            ? const FakeResponse({
                'error': true,
                'message': 'Unavailable',
                'code': 503,
              }, status: 503)
            : _answer(receipt());
      },
    });
  }

  final FakeApi api;
  final int actor;
  final bool learner;
  final balances = <int, int>{6: 5000, 7: 5000};
  bool allowed = true, readOnly = false, purchased = false;
  bool losePurchaseReply = false, loseCancelReply = false;
  int writes = 0, cancellations = 0, quantity = 1, selectedWallet = 6;
  String? operation;
  String status = 'ready';
  int get amount => quantity * 1250;
  static const denied = FakeResponse({
    'error': true,
    'message': 'Access changed',
    'code': 403,
  }, status: 403);
  Object _answer(Object data) => allowed
      ? {'error': false, 'message': '', 'data': data, 'code': 200}
      : denied;
  void _resolve(String key) {
    api.routes['POST v2/wallets/operations/$key/resolve'] =
        (RequestOptions _) => _answer({
          'school_id': 1,
          'actor_user_id': actor,
          'operation_id': key,
          'status': 'committed',
        });
  }

  Map<String, dynamic> walletIndex() => {
    'school_id': 1,
    'actor_user_id': actor,
    'currency': 'ZAR',
    'owners': [
      for (final owner in actor == 21 ? [21, 11] : [actor])
        {
          'owner_user_id': owner,
          'learner_id': owner == 11 || learner ? 101 : null,
          'owner_name': owner == 21
              ? 'Farid Synthetic'
              : learner || owner == 11
              ? 'Asha Synthetic'
              : 'Staff Synthetic',
          'is_self': owner == actor,
          'can_open': !readOnly,
          'wallets': [
            {
              'id': owner == 11 || learner ? 7 : 6,
              'school_id': 1,
              'owner_user_id': owner,
              'owner_name': owner == 21
                  ? 'Farid Synthetic'
                  : learner || owner == 11
                  ? 'Asha Synthetic'
                  : 'Staff Synthetic',
              'currency': 'ZAR',
              'balance_cents': balances[owner == 11 || learner ? 7 : 6],
              'is_child': learner || owner == 11,
              'read_only': readOnly,
              'can_manage_limits':
                  !readOnly && (!learner && owner != 11 || actor == 21),
              'school_daily_spend_cents': 5000,
              'child_limits_required': true,
              'limits': {
                'revision': 1,
                'daily_spend_cents': 5000,
                'effective_daily_spend_cents': 5000,
                'all_categories': true,
                'categories': <String>[],
                'configured': true,
              },
            },
          ],
        },
    ],
  };

  Map<String, dynamic> seller() => {
    'id': 81,
    'name': 'Synthetic tuckshop',
    'legal_name': 'Synthetic school vendor',
    'legal_address': null,
    'kind': 'school',
    'vat_registered': false,
    'vat_number': null,
  };
  Map<String, dynamic> product() => {
    'id': 51,
    'name': 'Synthetic sandwich',
    'description': null,
    'sku': 'SANDWICH-01',
    'unit_label': 'each',
    'category_key': 'food',
    'currency': 'ZAR',
    'price_cents': 1250,
    'vat_kind': 'none',
    'vat_rate_bps': 0,
    'in_stock': true,
  };
  Map<String, dynamic> vendors() => {
    'school_id': 1,
    'actor_user_id': actor,
    'can_purchase': !readOnly,
    'items': [
      {
        'id': 81,
        'name': 'Synthetic tuckshop',
        'description': null,
        'kind': 'school',
        'currency': 'ZAR',
      },
    ],
    'page': 1,
    'last_page': 1,
  };
  Map<String, dynamic> catalogue() => {
    'school_id': 1,
    'actor_user_id': actor,
    'can_purchase': !readOnly,
    'vendor': {
      'id': 81,
      'name': 'Synthetic tuckshop',
      'description': null,
      'kind': 'school',
    },
    'items': [product()],
    'page': 1,
    'last_page': 1,
  };
  Map<String, dynamic> quote() => {
    'school_id': 1,
    'actor_user_id': actor,
    'wallet_id': selectedWallet,
    'wallet_owner_id': selectedWallet == 7 ? 11 : actor,
    'learner_id': selectedWallet == 7 ? 1 : null,
    'vendor_id': 81,
    'currency': 'ZAR',
    'amount_cents': amount,
    'net_cents': amount,
    'vat_cents': 0,
    'seller': seller(),
    'lines': [
      {
        'product_id': 51,
        'product_revision': 1,
        'product_name': 'Synthetic sandwich',
        'sku': 'SANDWICH-01',
        'unit_label': 'each',
        'category_key': 'food',
        'quantity': quantity,
        'unit_price_cents': 1250,
        'gross_cents': amount,
        'net_cents': amount,
        'vat_cents': 0,
        'vat_kind': 'none',
        'vat_rate_bps': 0,
        'tracks_stock': true,
      },
    ],
    'quote': 'synthetic-opaque-checkout',
    'expires_at': '2026-11-01T09:15:00.000000Z',
  };
  Map<String, dynamic> receipt() => {
    'id': 901,
    'school_id': 1,
    'actor_user_id': actor,
    'operation_id': operation ?? '00000000-0000-4000-8000-000000000901',
    'order_number': 'VO-000001',
    'wallet_id': selectedWallet,
    'wallet_owner_id': selectedWallet == 7 ? 11 : actor,
    'learner_id': selectedWallet == 7 ? 1 : null,
    'vendor_id': 81,
    'currency': 'ZAR',
    'amount_cents': amount,
    'net_cents': amount,
    'vat_cents': 0,
    'status': status,
    'can_cancel': status == 'ready' && !readOnly,
    'qr': status == 'ready'
        ? 'navigateed:order:${List.filled(64, 'a').join()}'
        : null,
    'created_at': '2026-06-15T09:00:00.000000Z',
    'processed_at': status == 'ready' ? null : '2026-06-15T09:01:00.000000Z',
    'seller': seller(),
    'lines': [
      {
        'product_id': 51,
        'name': 'Synthetic sandwich',
        'sku': 'SANDWICH-01',
        'unit_label': 'each',
        'category_key': 'food',
        'quantity': quantity,
        'unit_price_cents': 1250,
        'gross_cents': amount,
        'net_cents': amount,
        'vat_cents': 0,
        'vat_kind': 'none',
        'vat_rate_bps': 0,
      },
    ],
  };
  Map<String, dynamic> orderPage() => {
    'school_id': 1,
    'actor_user_id': actor,
    'read_only': readOnly,
    'items': purchased ? [receipt()] : <Map<String, dynamic>>[],
    'page': 1,
    'last_page': 1,
  };

  ApiCommerceRepository repository({bool Function()? current}) {
    final client = ApiClient(
      createDio: () => Dio()..httpClientAdapter = api,
      authToken: () => 'synthetic',
      onUnauthorized: () {},
    );
    return ApiCommerceRepository(
      client,
      wallets: ApiWalletRepository(
        client,
        schoolId: 1,
        accountId: actor,
        contextActive: current ?? () => true,
      ),
    );
  }
}
