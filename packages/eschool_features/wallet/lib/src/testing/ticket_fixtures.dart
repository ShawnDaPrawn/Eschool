import 'package:dio/dio.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';

import '../ticket_repository.dart';
import '../wallet_repository.dart';
import 'commerce_fixtures.dart';

/// Synthetic admission boundary for wallet widgets and compiled profile journeys.
class TicketFakeServer {
  TicketFakeServer({
    int actor = 21,
    bool learner = false,
    this.named = false,
    FakeApi? api,
  }) : commerce = CommerceFakeServer(api: api, actor: actor, learner: learner) {
    this.api.routes.addAll({
      'GET v2/commerce/vendors': (RequestOptions _) =>
          answer({...commerce.vendors(), 'tickets_available': true}),
      'GET v2/commerce/tickets': (RequestOptions _) => answer(page()),
      'POST v2/commerce/tickets/receive-code': (RequestOptions _) => answer({
        ...scope,
        'receive_code': 'SYNTHETIC-RECEIVE-$actor',
        'expires_at': DateTime.now()
            .toUtc()
            .add(const Duration(minutes: 5))
            .toIso8601String(),
      }),
      'POST v2/commerce/tickets/1701/transfer-preview': (RequestOptions q) {
        if (!held) return denied;
        if ((q.data as Map)['receive_code'] != 'SYNTHETIC-RECEIVE-99') {
          return const FakeResponse({
            'error': true,
            'message': 'Use the intended current receiving code',
            'code': 422,
          }, status: 422);
        }
        return answer({
          ...scope,
          'ticket_id': 1701,
          'revision': 0,
          'recipient_id': 99,
          'recipient_name': 'Gina Synthetic',
          'kind': named ? 'named' : 'general',
          'event_name': 'Synthetic school admission',
        });
      },
      'POST v2/commerce/tickets/1701/transfer': (RequestOptions q) {
        if (!allowed) return denied;
        final data = Map<String, dynamic>.from(q.data as Map);
        final key = data['operation_id'] as String;
        if (!transfers.contains(key)) {
          if (!held) return denied;
          transfers.add(key);
          held = false;
          writes++;
        }
        this.api.routes['POST v2/wallets/operations/$key/resolve'] =
            (RequestOptions _) =>
                answer({...scope, 'operation_id': key, 'status': 'committed'});
        if (loseReply) {
          return const FakeResponse({
            'error': true,
            'message': 'Unavailable',
            'code': 503,
          }, status: 503);
        }
        return answer({
          ...scope,
          'ticket_id': 1701,
          'operation_id': key,
          'revision': 1,
          'status': 'transferred',
        });
      },
    });
  }
  final CommerceFakeServer commerce;
  final bool named;
  FakeApi get api => commerce.api;
  int get actor => commerce.actor;
  bool allowed = true, held = true, loseReply = false, active = true;
  int writes = 0;
  int? forgedOwner;
  final Set<String> transfers = {};
  Map<String, dynamic> get scope => {'school_id': 1, 'actor_user_id': actor};
  static const denied = FakeResponse({
    'error': true,
    'message': 'Ticket authority changed',
    'code': 403,
  }, status: 403);
  Object answer(Object data) => allowed
      ? {'error': false, 'message': '', 'data': data, 'code': 200}
      : denied;
  Map<String, dynamic> ticket() => {
    ...scope,
    'id': 1701,
    'holder_user_id': forgedOwner ?? actor,
    'revision': 0,
    'status': 'valid',
    'can_transfer': true,
    'event': {
      'id': 1501,
      'name': 'Synthetic school admission',
      'description': null,
      'status': 'active',
      'starts_at': '2026-10-28T06:00:00.000Z',
      'ends_at': '2026-10-28T23:59:00.000Z',
    },
    'type_id': 1601,
    'kind': named ? 'named' : 'general',
    'transfer_rule': 'families',
    'product_name': 'Synthetic original admission',
    'currency': 'ZAR',
    'unit_price_cents': 1250,
    'order_number': 'VO-000001',
    'named_learner_id': named ? 101 : null,
    'named_learner_name': named ? 'Asha Synthetic' : null,
    'qr': 'navigateed:ticket:${'a' * 64}',
    'ticket_number': 'TK-${'A' * 20}',
    'issued_at': '2026-10-28T05:00:00.000Z',
    'redeemed_at': null,
    'redeemed_by': null,
  };
  Map<String, dynamic> page() => {
    ...scope,
    'items': held ? [ticket()] : <Object>[],
    'page': 1,
    'last_page': 1,
  };
  ApiTicketRepository repository() {
    final client = ApiClient(
      createDio: () => Dio()..httpClientAdapter = api,
      authToken: () => 'synthetic',
      onUnauthorized: () {},
    );
    return ApiTicketRepository(
      client,
      wallets: ApiWalletRepository(
        client,
        schoolId: 1,
        accountId: actor,
        contextActive: () => active,
      ),
    );
  }
}
