import 'dart:convert';
import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:eschool_core/testing.dart';
import 'package:eschool_wallet/testing.dart';

import 'vendor_fixture.dart';
import 'vendor_ticket_fixture.dart';

class VendorPaymentFixture extends VendorTicketFixture {
  VendorPaymentFixture() {
    api.routes.addAll({
      'GET v2/vendor/counters/81/payments/catalogue': (RequestOptions _) =>
          answer({
            ...scope,
            'currency': 'ZAR',
            'page': 1,
            'last_page': 1,
            'items': [
              {
                'id': 51,
                'name': 'Synthetic sandwich',
                'unit_label': 'each',
                'revision': 1,
                'unit_price_cents': 1250,
                'vat_kind': 'none',
                'vat_rate_bps': 0,
                'available_quantity': 10,
              },
            ],
          }),
      'POST v2/vendor/counters/81/payments/lookup': (RequestOptions _) =>
          answer(instruction()),
      'POST v2/vendor/counters/81/payments/card': (RequestOptions _) => answer({
        ...instruction(card: true),
        'holder_name': 'Asha Synthetic',
        'portrait_revision': 1,
      }),
      'POST v2/vendor/counters/81/payments/pay': (RequestOptions q) {
        if (!allowed) return VendorFixture.denied;
        final operation = (q.data as Map)['operation_id'] as String;
        final replay = paymentKeys.contains(operation);
        if (paid && !replay)
          return const FakeResponse({
            'error': true,
            'message': 'Already paid',
            'code': 409,
          }, status: 409);
        if (!replay) {
          paid = true;
          paymentKeys.add(operation);
          paymentWrites++;
        }
        api.routes['POST v2/vendor/counters/81/payments/operations/$operation/resolve'] =
            (RequestOptions _) => answer({
              ...scope,
              'operation_id': operation,
              'status': 'committed',
              'payment': payment(operation),
            });
        if (losePaymentReply)
          return const FakeResponse({
            'error': true,
            'message': 'Unavailable',
            'code': 503,
          }, status: 503);
        return answer({
          ...payment(operation),
          'replayed': malformedReplay ? 'invalid' : replay,
        });
      },
    });
  }
  static const intentId = '00000000-0000-4000-8000-000000001901';
  static final readerCredential =
      'NED1.1.00000000-0000-4000-8000-000000001902.${'b' * 64}';
  bool paid = false, losePaymentReply = false, malformedReplay = false;
  int paymentWrites = 0;
  final paymentKeys = <String>{};
  Map<String, dynamic> get scope => {
    'school_id': 1,
    'actor_user_id': actor,
    'vendor_id': 81,
  };
  @override
  Map<String, dynamic> profile() => {
    ...super.profile(),
    'wallet_payments_available': true,
  };
  Map<String, dynamic> instruction({bool card = false}) {
    final quote = CommerceFakeServer(actor: 21).quote();
    return {
      ...scope,
      'id': intentId,
      'kind': card ? 'sun' : 'qr',
      'currency': 'ZAR',
      'amount_cents': 1250,
      'net_cents': 1250,
      'vat_cents': 0,
      'expires_at': DateTime.now()
          .toUtc()
          .add(const Duration(minutes: 2))
          .toIso8601String(),
      'seller': quote['seller'],
      'lines': quote['lines'],
    };
  }

  Map<String, dynamic> payment(String operation) => {
    ...scope,
    'id': '00000000-0000-4000-8000-000000001903',
    'intent_id': intentId,
    'operation_id': operation,
    'status': 'paid',
    'paid_at': '2026-10-28T09:01:00.000Z',
    'order': {
      ...receipt(),
      'id': 1905,
      'order_number': 'VO-001905',
      'status': 'collected',
      'can_collect': false,
      'processed_at': '2026-10-28T09:01:00.000Z',
    },
  };
}

class VendorPaymentAdapter implements HttpClientAdapter {
  VendorPaymentAdapter(this.fixture);
  final VendorPaymentFixture fixture;
  @override
  Future<ResponseBody> fetch(
    RequestOptions options,
    Stream<Uint8List>? stream,
    Future<void>? cancel,
  ) async {
    if (options.uri.path.endsWith('/portrait')) {
      fixture.api.requests.add(options);
      if (!fixture.allowed ||
          options.headers['X-Payment-Reader'] !=
              VendorPaymentFixture.readerCredential) {
        return ResponseBody.fromString(
          jsonEncode(VendorFixture.denied.body),
          403,
          headers: {
            Headers.contentTypeHeader: [Headers.jsonContentType],
          },
        );
      }
      return ResponseBody.fromBytes(
        base64Decode(syntheticPaymentPortrait),
        200,
        headers: {
          Headers.contentTypeHeader: ['image/jpeg'],
        },
      );
    }
    return fixture.api.fetch(options, stream, cancel);
  }

  @override
  void close({bool force = false}) => fixture.api.close(force: force);
}

// A generated solid-colour JPEG, never a real person's portrait or hardware proof.
const syntheticPaymentPortrait =
    '/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAoHBwgHBgoICAgLCgoLDhgQDg0NDh0VFhEYIx8lJCIfIiEmKzcvJik0KSEiMEExNDk7Pj4+JS5ESUM8SDc9Pjv/2wBDAQoLCw4NDhwQEBw7KCIoOzs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozs7Ozv/wAARCAAQABADASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/9oADAMBAAIRAxEAPwDLooorI88//9k=';
