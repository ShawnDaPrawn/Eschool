import 'package:eschool_api/eschool_api.dart';
import 'package:test/test.dart';

void main() {
  test('text values preserve leading zeroes and only encode their own member', () {
    final value = ProfileValue(text: '00010.20');
    expect(value.toJson(), {'text': '00010.20'});
    expect(ProfileValue.fromJson(value.toJson()).text, '00010.20');
  });

  test('false and an explicit clear keep their distinct wire meanings', () {
    expect(ProfileValue(flag: false).toJson(), {'flag': false});
    expect(ProfileValue(clear: true).toJson(), {'clear': true});
  });

  test('a write acknowledgement accepts null without decrypted values', () {
    final saved = ProfileSavedResponse.fromJson({
      'error': false, 'message': 'Saved', 'data': null, 'code': 200,
    });
    expect(saved.data, isNull);
    expect(saved.error, isFalse);
  });
}
