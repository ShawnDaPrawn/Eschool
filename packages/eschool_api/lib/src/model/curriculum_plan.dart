//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/curriculum_assessment.dart';
import 'package:eschool_api/src/model/curriculum_topic.dart';
import 'package:eschool_api/src/model/subject_standard.dart';
import 'package:json_annotation/json_annotation.dart';

part 'curriculum_plan.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CurriculumPlan {
  /// Returns a new [CurriculumPlan] instance.
  CurriculumPlan({
    required this.id,

    required this.subjectId,

    required this.gradeLevelId,

    required this.sessionYearId,

    required this.title,

    required this.version,

    required this.revision,

    required this.state,

    required this.standardId,

    required this.annualComponents,

    required this.rounding,

    required this.absentPolicy,

    required this.topics,

    required this.assessments,

    required this.sourcePlanId,

    required this.standardSnapshot,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

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
  @JsonKey(name: r'version', required: true, includeIfNull: false)
  final int version;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(
    name: r'state',
    required: true,
    includeIfNull: false,
    unknownEnumValue: CurriculumPlanStateEnum.unknownDefaultOpenApi,
  )
  final CurriculumPlanStateEnum state;

  // minimum: 1
  @JsonKey(name: r'standard_id', required: true, includeIfNull: true)
  final int? standardId;

  @JsonKey(name: r'annual_components', required: true, includeIfNull: true)
  final Map<String, int>? annualComponents;

  @JsonKey(
    name: r'rounding',
    required: true,
    includeIfNull: false,
    unknownEnumValue: CurriculumPlanRoundingEnum.unknownDefaultOpenApi,
  )
  final CurriculumPlanRoundingEnum rounding;

  @JsonKey(
    name: r'absent_policy',
    required: true,
    includeIfNull: false,
    unknownEnumValue: CurriculumPlanAbsentPolicyEnum.unknownDefaultOpenApi,
  )
  final CurriculumPlanAbsentPolicyEnum absentPolicy;

  @JsonKey(name: r'topics', required: true, includeIfNull: false)
  final List<CurriculumTopic> topics;

  @JsonKey(name: r'assessments', required: true, includeIfNull: false)
  final List<CurriculumAssessment> assessments;

  // minimum: 1
  @JsonKey(name: r'source_plan_id', required: true, includeIfNull: true)
  final int? sourcePlanId;

  @JsonKey(name: r'standard_snapshot', required: true, includeIfNull: true)
  final SubjectStandard? standardSnapshot;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CurriculumPlan &&
          other.id == id &&
          other.subjectId == subjectId &&
          other.gradeLevelId == gradeLevelId &&
          other.sessionYearId == sessionYearId &&
          other.title == title &&
          other.version == version &&
          other.revision == revision &&
          other.state == state &&
          other.standardId == standardId &&
          other.annualComponents == annualComponents &&
          other.rounding == rounding &&
          other.absentPolicy == absentPolicy &&
          other.topics == topics &&
          other.assessments == assessments &&
          other.sourcePlanId == sourcePlanId &&
          other.standardSnapshot == standardSnapshot;

  @override
  int get hashCode =>
      id.hashCode +
      subjectId.hashCode +
      gradeLevelId.hashCode +
      sessionYearId.hashCode +
      title.hashCode +
      version.hashCode +
      revision.hashCode +
      state.hashCode +
      (standardId == null ? 0 : standardId.hashCode) +
      (annualComponents == null ? 0 : annualComponents.hashCode) +
      rounding.hashCode +
      absentPolicy.hashCode +
      topics.hashCode +
      assessments.hashCode +
      (sourcePlanId == null ? 0 : sourcePlanId.hashCode) +
      (standardSnapshot == null ? 0 : standardSnapshot.hashCode);

  factory CurriculumPlan.fromJson(Map<String, dynamic> json) =>
      _$CurriculumPlanFromJson(json);

  Map<String, dynamic> toJson() => _$CurriculumPlanToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum CurriculumPlanStateEnum {
  @JsonValue(r'draft')
  draft(r'draft'),
  @JsonValue(r'published')
  published(r'published'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const CurriculumPlanStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum CurriculumPlanRoundingEnum {
  @JsonValue(r'half_up')
  halfUp(r'half_up'),
  @JsonValue(r'half_even')
  halfEven(r'half_even'),
  @JsonValue(r'floor')
  floor(r'floor'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const CurriculumPlanRoundingEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum CurriculumPlanAbsentPolicyEnum {
  @JsonValue(r'zero')
  zero(r'zero'),
  @JsonValue(r'incomplete')
  incomplete(r'incomplete'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const CurriculumPlanAbsentPolicyEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
