//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_lesson_write.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_timetable_preview_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicTimetablePreviewWrite {
  /// Returns a new [AcademicTimetablePreviewWrite] instance.
  AcademicTimetablePreviewWrite({required this.rows});

  @JsonKey(name: r'rows', required: true, includeIfNull: false)
  final List<AcademicLessonWrite> rows;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicTimetablePreviewWrite && other.rows == rows;

  @override
  int get hashCode => rows.hashCode;

  factory AcademicTimetablePreviewWrite.fromJson(Map<String, dynamic> json) =>
      _$AcademicTimetablePreviewWriteFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicTimetablePreviewWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
