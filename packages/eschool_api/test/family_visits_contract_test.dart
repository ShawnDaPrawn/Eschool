import 'package:eschool_api/eschool_api.dart';
import 'package:test/test.dart';

void main() {
  const operation = '8aef50e7-2429-4d2c-a523-668ae436c4d4';
  test(
    'meeting wall-clock fields retain school-local input without UTC guessing',
    () {
      final body = FamilyMeetingCreate(
        operationId: operation,
        hostId: 12,
        title: 'Synthetic parent appointment',
        venue: 'Synthetic room',
        startsAt: '2026-10-30T17:00',
        endsAt: '2026-10-30T17:15',
        bookingClosesAt: '2026-10-29T12:00',
      ).toJson();
      expect(body['starts_at'], '2026-10-30T17:00');
      expect(body['ends_at'], '2026-10-30T17:15');
      expect(body['operation_id'], operation);
    },
  );
  test('unbooked slots do not require or invent a private participant', () {
    final slot = FamilyMeetingSlot.fromJson({
      'id': operation,
      'host_id': 12,
      'host_name': 'Synthetic host',
      'title': 'Synthetic appointment',
      'venue': 'Synthetic room',
      'starts_at': '2026-10-30T15:00:00Z',
      'ends_at': '2026-10-30T15:15:00Z',
      'booking_closes_at': '2026-10-29T10:00:00Z',
      'status': 'open',
      'revision': 1,
    });
    expect(slot.learnerId, isNull);
    expect(slot.learnerName, isNull);
    expect(slot.guardianUserId, isNull);
  });
  test(
    'optional visits unavailability cannot be mistaken for collection approval',
    () {
      final result = FamilyGateLookup.fromJson({
        'available': false,
        'scan_id': operation,
        'learner_id': 41,
        'learner_name': '',
        'passes': <dynamic>[],
        'collector_check_required': true,
      });
      expect(result.available, isFalse);
      expect(result.passes, isEmpty);
      expect(result.collectorCheckRequired, isTrue);
    },
  );
  test(
    'collection preserves the original scan, revision and explicit confirmation',
    () {
      final body = FamilyGateCollection(
        operationId: operation,
        revision: 2,
        scanId: operation,
        collectorConfirmed: true,
      );
      expect(body.toJson(), {
        'operation_id': operation,
        'revision': 2,
        'scan_id': operation,
        'collector_confirmed': true,
      });
      expect(FamilyGateCollection.fromJson(body.toJson()), body);
    },
  );
}
