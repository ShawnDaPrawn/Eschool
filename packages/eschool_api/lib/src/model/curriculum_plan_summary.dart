//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'curriculum_plan_summary.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CurriculumPlanSummary {
  /// Returns a new [CurriculumPlanSummary] instance.
  CurriculumPlanSummary({
    required this.id,

    required this.subjectId,

    required this.gradeLevelId,

    required this.sessionYearId,

    required this.title,

    required this.version,

    required this.revision,

    required this.state,
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
    unknownEnumValue: CurriculumPlanSummaryStateEnum.unknownDefaultOpenApi,
  )
  final CurriculumPlanSummaryStateEnum state;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CurriculumPlanSummary &&
          other.id == id &&
          other.subjectId == subjectId &&
          other.gradeLevelId == gradeLevelId &&
          other.sessionYearId == sessionYearId &&
          other.title == title &&
          other.version == version &&
          other.revision == revision &&
          other.state == state;

  @override
  int get hashCode =>
      id.hashCode +
      subjectId.hashCode +
      gradeLevelId.hashCode +
      sessionYearId.hashCode +
      title.hashCode +
      version.hashCode +
      revision.hashCode +
      state.hashCode;

  factory CurriculumPlanSummary.fromJson(Map<String, dynamic> json) =>
      _$CurriculumPlanSummaryFromJson(json);

  Map<String, dynamic> toJson() => _$CurriculumPlanSummaryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum CurriculumPlanSummaryStateEnum {
  @JsonValue(r'draft')
  draft(r'draft'),
  @JsonValue(r'published')
  published(r'published'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const CurriculumPlanSummaryStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
