//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_timetable_lesson.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_timetable_commit_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicTimetableCommitData {
  /// Returns a new [AcademicTimetableCommitData] instance.
  AcademicTimetableCommitData({required this.lessons});

  @JsonKey(name: r'lessons', required: true, includeIfNull: false)
  final List<AcademicTimetableLesson> lessons;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicTimetableCommitData && other.lessons == lessons;

  @override
  int get hashCode => lessons.hashCode;

  factory AcademicTimetableCommitData.fromJson(Map<String, dynamic> json) =>
      _$AcademicTimetableCommitDataFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicTimetableCommitDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
