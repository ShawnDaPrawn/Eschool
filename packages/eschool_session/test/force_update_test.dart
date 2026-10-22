import 'package:eschool_session/eschool_session.dart';
import 'package:flutter_test/flutter_test.dart';

bool required(String installed, String wanted) => ForceUpdate.isRequired(
  installedVersion: installed,
  requiredVersion: wanted,
);

void main() {
  test('an empty server version never asks for an update', () {
    expect(required('3.5.0+39', ''), isFalse);
  });

  test('a newer server version asks for an update', () {
    expect(required('3.5.0+39', '3.5.1'), isTrue);
    expect(required('3.5.0+39', '3.6.0'), isTrue);
    expect(required('3.5.0+39', '4.0.0'), isTrue);
  });

  test('the same or an older version does not', () {
    expect(required('3.5.0+39', '3.5.0'), isFalse);
    expect(required('3.5.0+39', '3.4.0'), isFalse);
  });

  test('positions are compared one at a time (kept as the apps had it)', () {
    // A larger patch number asks for an update even if the minor is lower.
    expect(required('3.5.0+39', '3.4.1'), isTrue);
  });

  test('a larger build number asks for an update when both have one', () {
    expect(required('3.5.0+39', '3.5.0+40'), isTrue);
    expect(required('3.5.0+39', '3.5.0+39'), isFalse);
    expect(required('3.5.0', '3.5.0+40'), isFalse);
  });

  test('a malformed version throws, so the apps show no dialog', () {
    expect(() => required('3.5.0+39', '3.5'), throwsA(isA<RangeError>()));
    expect(() => required('3.5.0+39', 'x.y.z'), throwsFormatException);
  });

  test('the value for this platform is the Android one in tests', () {
    expect(ForceUpdate.forPlatform(android: '3.5.0', ios: '3.4.0'), '3.5.0');
  });
}
