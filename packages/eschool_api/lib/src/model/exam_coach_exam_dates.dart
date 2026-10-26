//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/exam_coach_exam_choice.dart';
import 'package:json_annotation/json_annotation.dart';

part 'exam_coach_exam_dates.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ExamCoachExamDates {
  /// Returns a new [ExamCoachExamDates] instance.
  ExamCoachExamDates({required this.groupId, required this.exams});

  // minimum: 1
  @JsonKey(name: r'group_id', required: true, includeIfNull: false)
  final int groupId;

  @JsonKey(name: r'exams', required: true, includeIfNull: false)
  final List<ExamCoachExamChoice> exams;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ExamCoachExamDates &&
          other.groupId == groupId &&
          other.exams == exams;

  @override
  int get hashCode => groupId.hashCode + exams.hashCode;

  factory ExamCoachExamDates.fromJson(Map<String, dynamic> json) =>
      _$ExamCoachExamDatesFromJson(json);

  Map<String, dynamic> toJson() => _$ExamCoachExamDatesToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
