//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/exam_coach_study_day.dart';
import 'package:eschool_api/src/model/exam_coach_plan_paper.dart';
import 'package:json_annotation/json_annotation.dart';

part 'exam_coach_study_plan.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ExamCoachStudyPlan {
  /// Returns a new [ExamCoachStudyPlan] instance.
  ExamCoachStudyPlan({
    required this.id,

    required this.learnerId,

    required this.groupId,

    required this.subjectId,

    required this.examOn,

    required this.papers,

    required this.days,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

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

  @JsonKey(name: r'days', required: true, includeIfNull: false)
  final List<ExamCoachStudyDay> days;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ExamCoachStudyPlan &&
          other.id == id &&
          other.learnerId == learnerId &&
          other.groupId == groupId &&
          other.subjectId == subjectId &&
          other.examOn == examOn &&
          other.papers == papers &&
          other.days == days;

  @override
  int get hashCode =>
      id.hashCode +
      learnerId.hashCode +
      groupId.hashCode +
      subjectId.hashCode +
      examOn.hashCode +
      papers.hashCode +
      days.hashCode;

  factory ExamCoachStudyPlan.fromJson(Map<String, dynamic> json) =>
      _$ExamCoachStudyPlanFromJson(json);

  Map<String, dynamic> toJson() => _$ExamCoachStudyPlanToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
