//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/exam_coach_plan_summary.dart';
import 'package:json_annotation/json_annotation.dart';

part 'exam_coach_plan_list.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ExamCoachPlanList {
  /// Returns a new [ExamCoachPlanList] instance.
  ExamCoachPlanList({
    required this.plans,

    required this.page,

    required this.hasMore,
  });

  @JsonKey(name: r'plans', required: true, includeIfNull: false)
  final List<ExamCoachPlanSummary> plans;

  // minimum: 1
  // maximum: 1000
  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  @JsonKey(name: r'has_more', required: true, includeIfNull: false)
  final bool hasMore;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ExamCoachPlanList &&
          other.plans == plans &&
          other.page == page &&
          other.hasMore == hasMore;

  @override
  int get hashCode => plans.hashCode + page.hashCode + hasMore.hashCode;

  factory ExamCoachPlanList.fromJson(Map<String, dynamic> json) =>
      _$ExamCoachPlanListFromJson(json);

  Map<String, dynamic> toJson() => _$ExamCoachPlanListToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
