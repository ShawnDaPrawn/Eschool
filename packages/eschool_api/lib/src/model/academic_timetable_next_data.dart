//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/academic_timetable_lesson.dart';
import 'package:json_annotation/json_annotation.dart';

part 'academic_timetable_next_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AcademicTimetableNextData {
  /// Returns a new [AcademicTimetableNextData] instance.
  AcademicTimetableNextData({required this.lesson});

  @JsonKey(name: r'lesson', required: true, includeIfNull: true)
  final AcademicTimetableLesson? lesson;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AcademicTimetableNextData && other.lesson == lesson;

  @override
  int get hashCode => (lesson == null ? 0 : lesson.hashCode);

  factory AcademicTimetableNextData.fromJson(Map<String, dynamic> json) =>
      _$AcademicTimetableNextDataFromJson(json);

  Map<String, dynamic> toJson() => _$AcademicTimetableNextDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
