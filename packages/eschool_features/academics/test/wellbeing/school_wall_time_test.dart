import 'package:eschool_academics/src/wellbeing/school_wall_time.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('school wall clock does not use device UTC or device timezone', () {
    expect(
      SchoolWallTime.utc('2026-02-10T10:15', 'Africa/Johannesburg'),
      DateTime.utc(2026, 2, 10, 8, 15),
    );
    expect(
      SchoolWallTime.local(
        DateTime.utc(2026, 2, 10, 8, 15),
        'Africa/Johannesburg',
      ),
      '2026-02-10T10:15',
    );
    expect(
      SchoolWallTime.utc('2026-07-10T10:15', 'America/New_York'),
      DateTime.utc(2026, 7, 10, 14, 15),
    );
  });
  test('missing and ambiguous daylight saving wall clocks are refused', () {
    expect(
      () => SchoolWallTime.utc('2026-03-08T02:30', 'America/New_York'),
      throwsFormatException,
    );
    expect(
      () => SchoolWallTime.utc('2026-11-01T01:30', 'America/New_York'),
      throwsFormatException,
    );
    expect(
      () => SchoolWallTime.utc('2026-02-30T10:00', 'Africa/Johannesburg'),
      throwsFormatException,
    );
  });
}
