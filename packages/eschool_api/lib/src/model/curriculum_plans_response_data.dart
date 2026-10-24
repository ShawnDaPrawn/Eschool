//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/curriculum_plan_summary.dart';
import 'package:json_annotation/json_annotation.dart';

part 'curriculum_plans_response_data.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CurriculumPlansResponseData {
  /// Returns a new [CurriculumPlansResponseData] instance.
  CurriculumPlansResponseData({
    required this.plans,

    required this.currentPage,

    required this.lastPage,

    required this.total,
  });

  @JsonKey(name: r'plans', required: true, includeIfNull: false)
  final List<CurriculumPlanSummary> plans;

  // minimum: 1
  @JsonKey(name: r'current_page', required: true, includeIfNull: false)
  final int currentPage;

  // minimum: 1
  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  // minimum: 0
  @JsonKey(name: r'total', required: true, includeIfNull: false)
  final int total;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CurriculumPlansResponseData &&
          other.plans == plans &&
          other.currentPage == currentPage &&
          other.lastPage == lastPage &&
          other.total == total;

  @override
  int get hashCode =>
      plans.hashCode +
      currentPage.hashCode +
      lastPage.hashCode +
      total.hashCode;

  factory CurriculumPlansResponseData.fromJson(Map<String, dynamic> json) =>
      _$CurriculumPlansResponseDataFromJson(json);

  Map<String, dynamic> toJson() => _$CurriculumPlansResponseDataToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
