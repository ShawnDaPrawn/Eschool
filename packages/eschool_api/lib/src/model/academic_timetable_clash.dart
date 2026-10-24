//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_timetable_clash.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicTimetableClash {
  /// Returns a new [AcademicTimetableClash] instance.
  AcademicTimetableClash({
    required this.row,

    required this.lessonId,

    required this.date,

    required this.reasons,
  });

  // minimum: 0
  @JsonKey(name: r'row', required: true, includeIfNull: false)
  final int row;

  // minimum: 1
  @JsonKey(name: r'lesson_id', required: true, includeIfNull: true)
  final int? lessonId;

  @JsonKey(name: r'date', required: true, includeIfNull: true)
  final String? date;

  @JsonKey(name: r'reasons', required: true, includeIfNull: false)
  final List<String> reasons;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicTimetableClash &&
          other.row == row &&
          other.lessonId == lessonId &&
          other.date == date &&
          other.reasons == reasons;

  @override
  int get hashCode =>
      row.hashCode +
      (lessonId == null ? 0 : lessonId.hashCode) +
      (date == null ? 0 : date.hashCode) +
      reasons.hashCode;

  factory AcademicTimetableClash.fromJson(Map<String, dynamic> json) =>
      _$AcademicTimetableClashFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicTimetableClashToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
