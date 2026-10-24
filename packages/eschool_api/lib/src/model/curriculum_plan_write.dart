//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/curriculum_topic_write.dart';
import 'package:eschool_api/src/model/curriculum_assessment_write.dart';
import 'package:json_annotation/json_annotation.dart';

part 'curriculum_plan_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CurriculumPlanWrite {
  /// Returns a new [CurriculumPlanWrite] instance.
  CurriculumPlanWrite({
    required this.subjectId,

    required this.gradeLevelId,

    required this.sessionYearId,

    required this.title,

    this.revision,

    this.standardId,

    this.annualComponents,

    required this.rounding,

    required this.absentPolicy,

    required this.topics,

    required this.assessments,
  });

  // minimum: 1
  @JsonKey(name: r'subject_id', required: true, includeIfNull: false)
  final int subjectId;

  // minimum: 1
  @JsonKey(name: r'grade_level_id', required: true, includeIfNull: false)
  final int gradeLevelId;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  // minimum: 1
  @JsonKey(name: r'revision', required: false, includeIfNull: false)
  final int? revision;

  // minimum: 1
  @JsonKey(name: r'standard_id', required: false, includeIfNull: false)
  final int? standardId;

  @JsonKey(name: r'annual_components', required: false, includeIfNull: false)
  final Map<String, int>? annualComponents;

  @JsonKey(
    name: r'rounding',
    required: true,
    includeIfNull: false,
    unknownEnumValue: CurriculumPlanWriteRoundingEnum.unknownDefaultOpenApi,
  )
  final CurriculumPlanWriteRoundingEnum rounding;

  @JsonKey(
    name: r'absent_policy',
    required: true,
    includeIfNull: false,
    unknownEnumValue: CurriculumPlanWriteAbsentPolicyEnum.unknownDefaultOpenApi,
  )
  final CurriculumPlanWriteAbsentPolicyEnum absentPolicy;

  @JsonKey(name: r'topics', required: true, includeIfNull: false)
  final List<CurriculumTopicWrite> topics;

  @JsonKey(name: r'assessments', required: true, includeIfNull: false)
  final List<CurriculumAssessmentWrite> assessments;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CurriculumPlanWrite &&
          other.subjectId == subjectId &&
          other.gradeLevelId == gradeLevelId &&
          other.sessionYearId == sessionYearId &&
          other.title == title &&
          other.revision == revision &&
          other.standardId == standardId &&
          other.annualComponents == annualComponents &&
          other.rounding == rounding &&
          other.absentPolicy == absentPolicy &&
          other.topics == topics &&
          other.assessments == assessments;

  @override
  int get hashCode =>
      subjectId.hashCode +
      gradeLevelId.hashCode +
      sessionYearId.hashCode +
      title.hashCode +
      revision.hashCode +
      (standardId == null ? 0 : standardId.hashCode) +
      (annualComponents == null ? 0 : annualComponents.hashCode) +
      rounding.hashCode +
      absentPolicy.hashCode +
      topics.hashCode +
      assessments.hashCode;

  factory CurriculumPlanWrite.fromJson(Map<String, dynamic> json) =>
      _$CurriculumPlanWriteFromJson(json);

  Map<String, dynamic> toJson() => _$CurriculumPlanWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum CurriculumPlanWriteRoundingEnum {
  @JsonValue(r'half_up')
  halfUp(r'half_up'),
  @JsonValue(r'half_even')
  halfEven(r'half_even'),
  @JsonValue(r'floor')
  floor(r'floor'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const CurriculumPlanWriteRoundingEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum CurriculumPlanWriteAbsentPolicyEnum {
  @JsonValue(r'zero')
  zero(r'zero'),
  @JsonValue(r'incomplete')
  incomplete(r'incomplete'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const CurriculumPlanWriteAbsentPolicyEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
