import 'package:eschool_api/eschool_api.dart';
import 'package:test/test.dart';

void main() {
  final card = <String, dynamic>{
    'id': '8aef50e7-2429-4d2c-a523-668ae436c4d4',
    'school_id': 1,
    'person_id': 7,
    'type': 'learner',
    'status': 'active',
    'revision': 1,
    'issued_at': '2026-10-25T12:00:00.000Z',
    'frozen_at': null,
    'replaced_by': null,
    'has_chip': false,
  };

  test('card metadata preserves required explicit nullable history fields', () {
    expect(SecureCard.fromJson(card).toJson(), card);
    expect(
      SecureCardPage.fromJson({
        'items': [card],
        'next_page': null,
      }).toJson(),
      {
        'items': [card],
        'next_page': null,
      },
    );
    final missing = {...card}..remove('frozen_at');
    expect(() => SecureCard.fromJson(missing), throwsA(isA<Exception>()));
  });

  test('digital proof and reader credentials retain their distinct shapes', () {
    final digital = <String, dynamic>{
      'card': card,
      'credential': 'NEQ1.synthetic.signed',
      'expires_at': '2026-10-25T12:00:25.000Z',
      'key_id': '8aef50e7-2429-4d2c-a523-668ae436c4d4',
      'public_key': 'synthetic-public-key',
    };
    expect(SecureCardCredential.fromJson(digital).toJson(), digital);
    final reader = <String, dynamic>{
      'id': card['id'],
      'credential': 'NED1.synthetic-private-device-credential',
      'kind': 'gate',
      'expires_at': '2026-10-26T12:00:00.000Z',
    };
    expect(SecureCardReaderCredential.fromJson(reader).toJson(), reader);
  });

  test(
    'accepted scan contains references without raw proof or chip secrets',
    () {
      final scan = <String, dynamic>{
        'scan_id': card['id'],
        'card_id': card['id'],
        'person_id': 7,
        'type': 'learner',
        'outcome': 'accepted',
        'scanned_at': '2026-10-25T12:00:01.000Z',
      };
      expect(SecureCardScanReceipt.fromJson(scan).toJson(), scan);
      expect(
        SecureCardScanReceipt.fromJson({
          ...scan,
          'metadata_key': 'never-client',
        }).toJson(),
        isNot(contains('metadata_key')),
      );
    },
  );

  test('older facilitator payloads omit optional card capability', () {
    final old = <String, dynamic>{
      'groups': true,
      'messages': false,
      'counselling': false,
    };
    expect(FacilitatorFeatures.fromJson(old).toJson(), old);
    expect(
      FacilitatorFeatures.fromJson({...old, 'cards': false}).toJson()['cards'],
      false,
    );
  });
}
