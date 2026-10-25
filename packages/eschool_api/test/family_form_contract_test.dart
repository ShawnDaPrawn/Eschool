import 'dart:convert';

import 'package:eschool_api/eschool_api.dart';
import 'package:test/test.dart';

Map<String, dynamic> encoded(Object value) =>
    jsonDecode(jsonEncode(value)) as Map<String, dynamic>;

void main() {
  test(
    'family configuration binds reader, school and learner/person identity',
    () {
      final value = FamilyFormConfiguration.fromJson({
        'school_id': 2,
        'reader_id': 7,
        'children': [
          {'id': 4, 'person_id': 40, 'name': 'Synthetic learner'},
        ],
        'groups': [
          {'id': 12, 'name': 'Synthetic group', 'session_year_id': 3},
        ],
        'writable': false,
        'signature_name': 'Synthetic guardian',
        'timezone': 'Africa/Johannesburg',
        'session_year_id': 3,
      });
      expect(value.schoolId, 2);
      expect(value.readerId, 7);
      expect(value.children.single.personId, 40);
      expect(value.writable, isFalse);
      expect(encoded(value)['timezone'], 'Africa/Johannesburg');
    },
  );

  test(
    'unsigned consent preserves explicit null decision and signing instant',
    () {
      final value = FamilyConsentDetail.fromJson({
        'id': '8aef50e7-2429-4d2c-a523-668ae436c4d4',
        'learner_id': 4,
        'title': 'Synthetic trip',
        'wording': 'Exact synthetic wording',
        'fingerprint': 'a' * 64,
        'status': 'open',
        'revision': 0,
        'event_starts_at': '2026-11-20T08:00:00Z',
        'event_ends_at': '2026-11-20T10:00:00Z',
        'respond_by': '2026-11-19T10:00:00Z',
        'decision': null,
        'signed_at': null,
      });
      expect(value.decision, isNull);
      expect(value.signedAt, isNull);
      expect(value.eventStartsAt.isUtc, isTrue);
      expect(encoded(value).containsKey('signed_at'), isTrue);
      expect(encoded(value)['signed_at'], isNull);
    },
  );

  test(
    'consent attestation retains exact fingerprint operation and revision',
    () {
      final json = {
        'operation_id': '8aef50e7-2429-4d2c-a523-668ae436c4d4',
        'revision': 2,
        'fingerprint': 'b' * 64,
        'decision': 'no',
        'acknowledged': true,
        'signed_name': 'Synthetic guardian',
      };
      expect(encoded(FamilyConsentSign.fromJson(json)), json);
    },
  );

  test(
    'health values serialize only the intended text or explicit clear member',
    () {
      final json = {
        'values': {
          'allergies': {'text': 'Synthetic private value'},
          'medical_alerts': {'clear': true},
        },
      };
      expect(encoded(FamilyHealthWrite.fromJson(json)), json);
    },
  );

  test('old facilitator feature payload omits the new optional family bit', () {
    final value = FacilitatorFeatures.fromJson({
      'groups': true,
      'messages': false,
      'counselling': false,
    });
    expect(value.familyForms, isNull);
    expect(encoded(value).containsKey('family_forms'), isFalse);
  });
}
