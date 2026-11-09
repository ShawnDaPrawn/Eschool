//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_timetable_week_data_breaks_inner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicTimetableWeekDataBreaksInner {
  /// Returns a new [AcademicTimetableWeekDataBreaksInner] instance.
  AcademicTimetableWeekDataBreaksInner({
    required this.date,

    required this.name,

    required this.startsAt,

    required this.endsAt,
  });

  @JsonKey(name: r'date', required: true, includeIfNull: false)
  final String date;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'starts_at', required: true, includeIfNull: false)
  final String startsAt;

  @JsonKey(name: r'ends_at', required: true, includeIfNull: false)
  final String endsAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicTimetableWeekDataBreaksInner &&
          other.date == date &&
          other.name == name &&
          other.startsAt == startsAt &&
          other.endsAt == endsAt;

  @override
  int get hashCode =>
      date.hashCode + name.hashCode + startsAt.hashCode + endsAt.hashCode;

  factory AcademicTimetableWeekDataBreaksInner.fromJson(
    Map<String, dynamic> json,
  ) => _$AcademicTimetableWeekDataBreaksInnerFromJson(json);

  Map<String, dynamic> toJson() =>
      _$AcademicTimetableWeekDataBreaksInnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
