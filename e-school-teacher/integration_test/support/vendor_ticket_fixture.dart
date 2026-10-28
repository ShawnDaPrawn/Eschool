import 'package:dio/dio.dart';
import 'package:eschool_core/testing.dart';

import 'vendor_fixture.dart';

class VendorTicketFixture extends VendorFixture {
  VendorTicketFixture() {
    api.routes.addAll({
      'POST v2/vendor/counters/81/tickets/lookup': (RequestOptions _) =>
          answer(admission()),
      'POST v2/vendor/counters/81/tickets/redeem': (RequestOptions q) {
        if (!allowed) return VendorFixture.denied;
        final key = (q.data as Map)['operation_id'] as String;
        final replay = admissionKeys.contains(key);
        if (admitted && !replay)
          return const FakeResponse({
            'error': true,
            'message': 'Admission already recorded',
            'code': 409,
          }, status: 409);
        if (!replay) {
          admitted = true;
          admissionKeys.add(key);
          admissionWrites++;
        }
        api.routes['POST v2/vendor/counters/81/ticket-operations/$key/resolve'] =
            (RequestOptions _) => answer({
              'school_id': 1,
              'actor_user_id': actor,
              'vendor_id': 81,
              'operation_id': key,
              'status': 'committed',
              'receipt': admission(),
            });
        if (loseAdmissionReply)
          return const FakeResponse({
            'error': true,
            'message': 'Unavailable',
            'code': 503,
          }, status: 503);
        return answer({
          'school_id': 1,
          'actor_user_id': actor,
          'vendor_id': 81,
          'receipt': admission(),
          'replayed': replay,
        });
      },
    });
  }
  bool admitted = false, loseAdmissionReply = false;
  int admissionWrites = 0;
  final Set<String> admissionKeys = {};
  @override
  Map<String, dynamic> profile() => {
    ...super.profile(),
    'ticketing_available': true,
  };
  Map<String, dynamic> admission() => {
    'school_id': 1,
    'actor_user_id': actor,
    'vendor_id': 81,
    'id': 1701,
    'revision': admitted ? 1 : 0,
    'status': admitted ? 'redeemed' : 'valid',
    'event_name': 'Synthetic school admission',
    'product_name': 'Synthetic original admission',
    'kind': 'named',
    'named_learner_name': 'Asha Synthetic',
    'starts_at': '2026-10-28T06:00:00.000Z',
    'ends_at': '2026-10-28T23:59:00.000Z',
    'can_redeem': !admitted && !readOnly,
    'redeemed_at': admitted ? '2026-10-28T07:00:00.000Z' : null,
    'redeemed_by': admitted ? actor : null,
  };
}
