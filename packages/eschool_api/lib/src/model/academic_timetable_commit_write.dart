//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_lesson_write.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_timetable_commit_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicTimetableCommitWrite {
  /// Returns a new [AcademicTimetableCommitWrite] instance.
  AcademicTimetableCommitWrite({required this.rows, required this.fingerprint});

  @JsonKey(name: r'rows', required: true, includeIfNull: false)
  final List<AcademicLessonWrite> rows;

  @JsonKey(name: r'fingerprint', required: true, includeIfNull: false)
  final String fingerprint;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicTimetableCommitWrite &&
          other.rows == rows &&
          other.fingerprint == fingerprint;

  @override
  int get hashCode => rows.hashCode + fingerprint.hashCode;

  factory AcademicTimetableCommitWrite.fromJson(Map<String, dynamic> json) =>
      _$AcademicTimetableCommitWriteFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicTimetableCommitWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
