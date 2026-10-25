import 'package:eschool_api/eschool_api.dart';
import 'package:test/test.dart';

void main() {
  test('sanitized private photo metadata has an exact round trip', () {
    final json = <String, dynamic>{
      'id': '11111111-1111-4111-8111-111111111111',
      'mime': 'image/jpeg',
      'bytes': 2400,
      'width': 80,
      'height': 40,
      'thumbnail_base64': 'aW1hZ2U=',
      'created_at': '2026-02-10T08:00:00.000Z',
      'report_revision': 2,
    };
    expect(IncidentPhoto.fromJson(json).toJson(), json);
    expect(
      json.keys.any(
        (key) =>
            ['path', 'secret', 'user_id', 'operation_id', 'url'].contains(key),
      ),
      isFalse,
    );
  });
  test('older public configuration omits photo capability', () {
    final configuration = IncidentPublicConfiguration.fromJson({
      'emergency_guidance': 'Speak to a trusted adult.',
      'categories': [],
    });
    expect(configuration.photoConstraints, isNull);
    expect(configuration.toJson().containsKey('photo_constraints'), isFalse);
  });
  test('new configuration retains exact safe MIME and size constraints', () {
    final json = <String, dynamic>{
      'maximum_bytes': 4194304,
      'maximum_photos': 5,
      'allowed_mimes': ['image/jpeg', 'image/png'],
    };
    expect(IncidentPhotoConstraints.fromJson(json).toJson(), json);
  });
  test(
    'new optional staff flags retain old omission without default grants',
    () {
      final old = FacilitatorFeatures.fromJson({
        'groups': true,
        'messages': true,
        'counselling': false,
      });
      expect(old.discipline, isNull);
      expect(old.housePoints, isNull);
      expect(old.awards, isNull);
      expect(old.toJson().containsKey('discipline'), isFalse);
      expect(old.toJson().containsKey('house_points'), isFalse);
      expect(old.toJson().containsKey('awards'), isFalse);
      final enabled = FacilitatorFeatures.fromJson({
        'groups': true,
        'messages': true,
        'counselling': false,
        'discipline': true,
        'house_points': false,
        'awards': true,
      });
      expect(enabled.discipline, isTrue);
      expect(enabled.housePoints, isFalse);
      expect(enabled.awards, isTrue);
    },
  );
}
