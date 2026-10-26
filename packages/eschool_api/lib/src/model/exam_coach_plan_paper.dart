//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'exam_coach_plan_paper.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ExamCoachPlanPaper {
  /// Returns a new [ExamCoachPlanPaper] instance.
  ExamCoachPlanPaper({
    required this.id,

    required this.versionId,

    required this.title,

    required this.paperYear,

    required this.paperCode,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'version_id', required: true, includeIfNull: false)
  final String versionId;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  // minimum: 1850
  // maximum: 2100
  @JsonKey(name: r'paper_year', required: true, includeIfNull: false)
  final int paperYear;

  @JsonKey(name: r'paper_code', required: true, includeIfNull: false)
  final String paperCode;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ExamCoachPlanPaper &&
          other.id == id &&
          other.versionId == versionId &&
          other.title == title &&
          other.paperYear == paperYear &&
          other.paperCode == paperCode;

  @override
  int get hashCode =>
      id.hashCode +
      versionId.hashCode +
      title.hashCode +
      paperYear.hashCode +
      paperCode.hashCode;

  factory ExamCoachPlanPaper.fromJson(Map<String, dynamic> json) =>
      _$ExamCoachPlanPaperFromJson(json);

  Map<String, dynamic> toJson() => _$ExamCoachPlanPaperToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
