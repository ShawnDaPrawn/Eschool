//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'reading_week.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ReadingWeek {
  /// Returns a new [ReadingWeek] instance.
  ReadingWeek({
    required this.weekStart,

    required this.minutesThisWeek,

    required this.daysThisWeek,

    required this.booksFinished,
  });

  @JsonKey(name: r'week_start', required: true, includeIfNull: false)
  final DateTime weekStart;

  @JsonKey(name: r'minutes_this_week', required: true, includeIfNull: false)
  final int minutesThisWeek;

  @JsonKey(name: r'days_this_week', required: true, includeIfNull: false)
  final int daysThisWeek;

  @JsonKey(name: r'books_finished', required: true, includeIfNull: false)
  final int booksFinished;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ReadingWeek &&
          other.weekStart == weekStart &&
          other.minutesThisWeek == minutesThisWeek &&
          other.daysThisWeek == daysThisWeek &&
          other.booksFinished == booksFinished;

  @override
  int get hashCode =>
      weekStart.hashCode +
      minutesThisWeek.hashCode +
      daysThisWeek.hashCode +
      booksFinished.hashCode;

  factory ReadingWeek.fromJson(Map<String, dynamic> json) =>
      _$ReadingWeekFromJson(json);

  Map<String, dynamic> toJson() => _$ReadingWeekToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
