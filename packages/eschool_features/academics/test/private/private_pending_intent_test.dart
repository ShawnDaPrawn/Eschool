import 'package:eschool_academics/src/private/private_pending_intent.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test(
    'submitted exact request is scope-bound, independent and expires after fifteen minutes',
    () {
      var now = DateTime.utc(2026, 10, 26, 10);
      final source = {
        'operation_id': 'de100bf4-c237-4d13-ae2c-21da6c1bda5a',
        'reason': 'PRIVATE-SUBMITTED-REASON',
      };
      final pending = PrivatePendingIntent(
        school: 1,
        reader: 7,
        body: source,
        clock: () => now,
      );
      addTearDown(pending.close);
      source['reason'] = 'changed';
      expect(
        () => pending.open(school: 2, reader: 7, authorized: true),
        throwsStateError,
      );
      expect(
        () => pending.open(school: 1, reader: 8, authorized: true),
        throwsStateError,
      );
      expect(
        () => pending.open(school: 1, reader: 7, authorized: false),
        throwsStateError,
      );
      expect(
        pending.open(school: 1, reader: 7, authorized: true)['reason'],
        'PRIVATE-SUBMITTED-REASON',
      );
      now = now.add(const Duration(minutes: 14));
      pending.open(school: 1, reader: 7, authorized: true);
      now = now.add(const Duration(minutes: 1));
      expect(
        () => pending.open(school: 1, reader: 7, authorized: true),
        throwsStateError,
      );
      expect(pending.expired, true);
    },
  );
  test(
    'explicit close destroys the in-memory request and cannot reactivate it',
    () {
      final pending = PrivatePendingIntent(
        school: 1,
        reader: 7,
        body: {'operation_id': 'original'},
      );
      pending.close();
      expect(pending.expired, true);
      expect(
        () => pending.open(school: 1, reader: 7, authorized: true),
        throwsStateError,
      );
    },
  );
  test('oversized request is refused before retention', () {
    expect(
      () => PrivatePendingIntent(
        school: 1,
        reader: 7,
        body: {'bytes': 'a' * 2097153},
      ),
      throwsFormatException,
    );
  });
}
