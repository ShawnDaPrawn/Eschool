//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'exam_coach_plan_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ExamCoachPlanWrite {
  /// Returns a new [ExamCoachPlanWrite] instance.
  ExamCoachPlanWrite({
    required this.operationId,

    required this.examTimetableId,

    required this.scheduleSignature,

    required this.paperVersionIds,

    required this.studyMinutes,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'exam_timetable_id', required: true, includeIfNull: false)
  final int examTimetableId;

  @JsonKey(name: r'schedule_signature', required: true, includeIfNull: false)
  final String scheduleSignature;

  @JsonKey(name: r'paper_version_ids', required: true, includeIfNull: false)
  final List<String> paperVersionIds;

  // minimum: 10
  // maximum: 60
  @JsonKey(name: r'study_minutes', required: true, includeIfNull: false)
  final int studyMinutes;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ExamCoachPlanWrite &&
          other.operationId == operationId &&
          other.examTimetableId == examTimetableId &&
          other.scheduleSignature == scheduleSignature &&
          other.paperVersionIds == paperVersionIds &&
          other.studyMinutes == studyMinutes;

  @override
  int get hashCode =>
      operationId.hashCode +
      examTimetableId.hashCode +
      scheduleSignature.hashCode +
      paperVersionIds.hashCode +
      studyMinutes.hashCode;

  factory ExamCoachPlanWrite.fromJson(Map<String, dynamic> json) =>
      _$ExamCoachPlanWriteFromJson(json);

  Map<String, dynamic> toJson() => _$ExamCoachPlanWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
