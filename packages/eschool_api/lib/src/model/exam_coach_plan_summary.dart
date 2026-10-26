//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/exam_coach_plan_paper.dart';
import 'package:json_annotation/json_annotation.dart';

part 'exam_coach_plan_summary.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ExamCoachPlanSummary {
  /// Returns a new [ExamCoachPlanSummary] instance.
  ExamCoachPlanSummary({
    required this.id,

    required this.groupId,

    required this.subjectId,

    required this.examOn,

    required this.papers,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  // minimum: 1
  @JsonKey(name: r'group_id', required: true, includeIfNull: false)
  final int groupId;

  // minimum: 1
  @JsonKey(name: r'subject_id', required: true, includeIfNull: false)
  final int subjectId;

  @JsonKey(name: r'exam_on', required: true, includeIfNull: false)
  final String examOn;

  @JsonKey(name: r'papers', required: true, includeIfNull: false)
  final List<ExamCoachPlanPaper> papers;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ExamCoachPlanSummary &&
          other.id == id &&
          other.groupId == groupId &&
          other.subjectId == subjectId &&
          other.examOn == examOn &&
          other.papers == papers;

  @override
  int get hashCode =>
      id.hashCode +
      groupId.hashCode +
      subjectId.hashCode +
      examOn.hashCode +
      papers.hashCode;

  factory ExamCoachPlanSummary.fromJson(Map<String, dynamic> json) =>
      _$ExamCoachPlanSummaryFromJson(json);

  Map<String, dynamic> toJson() => _$ExamCoachPlanSummaryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
