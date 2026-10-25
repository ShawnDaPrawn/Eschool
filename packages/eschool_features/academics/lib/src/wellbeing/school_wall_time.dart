import 'package:timezone/data/latest.dart' as data;
import 'package:timezone/timezone.dart' as tz;

/// Resolve a school wall clock without guessing a device zone or DST fold.
class SchoolWallTime {
  static bool _ready = false;
  static tz.Location _location(String zone) {
    if (!_ready) {
      data.initializeTimeZones();
      _ready = true;
    }
    try {
      return tz.getLocation(zone);
    } catch (_) {
      throw const FormatException(
        'Refresh your school settings before entering appointment or note times.',
      );
    }
  }

  static String local(DateTime instant, String zone) {
    final value = tz.TZDateTime.from(instant.toUtc(), _location(zone));
    String pad(int value) => '$value'.padLeft(2, '0');
    return '${value.year}-${pad(value.month)}-${pad(value.day)}T${pad(value.hour)}:${pad(value.minute)}';
  }

  static DateTime utc(String wall, String zone) {
    if (!RegExp(r'^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$').hasMatch(wall))
      throw const FormatException('Enter a school date and time.');
    final raw = DateTime.parse('${wall}Z');
    if (raw.toIso8601String().substring(0, 16) != wall)
      throw const FormatException('Enter a valid school date and time.');
    final location = _location(zone), candidates = <int>{};
    for (final offset
        in location.zones.map((zone) => zone.offset.inMilliseconds).toSet()) {
      final instant = raw.millisecondsSinceEpoch - offset;
      final local = tz.TZDateTime.fromMillisecondsSinceEpoch(location, instant);
      if (local.year == raw.year &&
          local.month == raw.month &&
          local.day == raw.day &&
          local.hour == raw.hour &&
          local.minute == raw.minute)
        candidates.add(instant);
    }
    if (candidates.length != 1)
      throw const FormatException(
        'This school time is missing or ambiguous. Choose another time.',
      );
    return DateTime.fromMillisecondsSinceEpoch(candidates.single, isUtc: true);
  }
}
