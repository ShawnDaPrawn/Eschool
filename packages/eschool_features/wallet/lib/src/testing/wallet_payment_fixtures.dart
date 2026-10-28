import 'package:dio/dio.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';

import '../wallet_payment_repository.dart';
import '../wallet_repository.dart';
import 'commerce_fixtures.dart';

class WalletPaymentFakeServer {
  WalletPaymentFakeServer({
    int actor = 21,
    bool learner = false,
    CommerceFakeServer? sharedCommerce,
    bool ticketsAvailable = false,
  }) : commerce =
           sharedCommerce ??
           CommerceFakeServer(actor: actor, learner: learner) {
    api.routes.addAll({
      'GET v2/commerce/vendors': (RequestOptions _) => answer({
        ...commerce.vendors(),
        'wallet_payments_available': true,
        'wallet_payments_writable': true,
        if (ticketsAvailable) 'tickets_available': true,
      }),
      'GET v2/commerce/wallet-payments': (RequestOptions _) => answer({
        'school_id': 1,
        'actor_user_id': commerce.actor,
        'items': issued ? [payment()] : <Object>[],
        'page': 1,
        'last_page': 1,
      }),
      'POST v2/commerce/wallet-payments': (RequestOptions q) {
        issued = true;
        writes++;
        final operation = (q.data as Map)['operation_id'] as String;
        api.routes['POST v2/wallets/operations/$operation/resolve'] =
            (RequestOptions _) => answer({
              'school_id': 1,
              'actor_user_id': commerce.actor,
              'operation_id': operation,
              'status': 'committed',
            });
        return loseReply
            ? const FakeResponse({
                'error': true,
                'message': 'Unavailable',
                'code': 503,
              }, status: 503)
            : answer(payment());
      },
      'GET v2/commerce/wallet-payments/$id': (RequestOptions _) =>
          answer(payment()),
      'POST v2/commerce/wallet-payments/$id/cancel': (RequestOptions _) {
        cancelled = true;
        return answer(payment());
      },
    });
  }
  static const id = '00000000-0000-4000-8000-000000001801';
  final CommerceFakeServer commerce;
  FakeApi get api => commerce.api;
  bool issued = false, paid = false, cancelled = false, loseReply = false;
  bool active = true, expired = false;
  int writes = 0;
  int? forgedActor;
  Object answer(Object data) => commerce.allowed
      ? {'error': false, 'message': '', 'data': data, 'code': 200}
      : CommerceFakeServer.denied;
  Map<String, dynamic> payment() => {
    ...commerce.quote(),
    'actor_user_id': forgedActor ?? commerce.actor,
    'wallet_owner_name': commerce.selectedWallet == 7 || commerce.learner
        ? 'Asha Synthetic'
        : commerce.actor == 21
        ? 'Farid Synthetic'
        : 'Staff Synthetic',
    'id': id,
    'kind': 'qr',
    'expires_at': DateTime.now()
        .toUtc()
        .add(Duration(minutes: expired ? -1 : 2))
        .toIso8601String(),
    'status': paid
        ? 'paid'
        : cancelled
        ? 'cancelled'
        : expired
        ? 'expired'
        : 'awaiting_counter',
    'qr': paid || cancelled || expired
        ? null
        : 'navigateed:wallet-pay:${'a' * 64}',
    'order_id': paid ? 901 : null,
  };
  ApiWalletPaymentRepository repository() {
    final client = ApiClient(
      createDio: () => Dio()..httpClientAdapter = api,
      authToken: () => 'synthetic',
      onUnauthorized: () {},
    );
    return ApiWalletPaymentRepository(
      client,
      wallets: ApiWalletRepository(
        client,
        schoolId: 1,
        accountId: commerce.actor,
        contextActive: () => active,
      ),
    );
  }
}
