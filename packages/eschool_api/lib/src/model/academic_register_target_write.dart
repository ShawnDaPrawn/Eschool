//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'academic_register_target_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicRegisterTargetWrite {
  /// Returns a new [AcademicRegisterTargetWrite] instance.
  AcademicRegisterTargetWrite({
    required this.sessionYearId,

    required this.onDate,

    this.classSectionId,

    this.timetableLessonId,
  });

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(name: r'on_date', required: true, includeIfNull: false)
  final String onDate;

  // minimum: 1
  @JsonKey(name: r'class_section_id', required: false, includeIfNull: false)
  final int? classSectionId;

  // minimum: 1
  @JsonKey(name: r'timetable_lesson_id', required: false, includeIfNull: false)
  final int? timetableLessonId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicRegisterTargetWrite &&
          other.sessionYearId == sessionYearId &&
          other.onDate == onDate &&
          other.classSectionId == classSectionId &&
          other.timetableLessonId == timetableLessonId;

  @override
  int get hashCode =>
      sessionYearId.hashCode +
      onDate.hashCode +
      (classSectionId == null ? 0 : classSectionId.hashCode) +
      (timetableLessonId == null ? 0 : timetableLessonId.hashCode);

  factory AcademicRegisterTargetWrite.fromJson(Map<String, dynamic> json) =>
      _$AcademicRegisterTargetWriteFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicRegisterTargetWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
