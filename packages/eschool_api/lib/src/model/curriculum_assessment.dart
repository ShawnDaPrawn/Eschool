//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/curriculum_task.dart';
import 'package:json_annotation/json_annotation.dart';

part 'curriculum_assessment.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CurriculumAssessment {
  /// Returns a new [CurriculumAssessment] instance.
  CurriculumAssessment({
    required this.id,

    required this.termId,

    required this.components,

    required this.tasks,

    required this.state,

    required this.publishedAt,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'term_id', required: true, includeIfNull: false)
  final int termId;

  @JsonKey(name: r'components', required: true, includeIfNull: false)
  final Map<String, int> components;

  @JsonKey(name: r'tasks', required: true, includeIfNull: false)
  final List<CurriculumTask> tasks;

  @JsonKey(
    name: r'state',
    required: true,
    includeIfNull: false,
    unknownEnumValue: CurriculumAssessmentStateEnum.unknownDefaultOpenApi,
  )
  final CurriculumAssessmentStateEnum state;

  @JsonKey(name: r'published_at', required: true, includeIfNull: true)
  final DateTime? publishedAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CurriculumAssessment &&
          other.id == id &&
          other.termId == termId &&
          other.components == components &&
          other.tasks == tasks &&
          other.state == state &&
          other.publishedAt == publishedAt;

  @override
  int get hashCode =>
      id.hashCode +
      termId.hashCode +
      components.hashCode +
      tasks.hashCode +
      state.hashCode +
      (publishedAt == null ? 0 : publishedAt.hashCode);

  factory CurriculumAssessment.fromJson(Map<String, dynamic> json) =>
      _$CurriculumAssessmentFromJson(json);

  Map<String, dynamic> toJson() => _$CurriculumAssessmentToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum CurriculumAssessmentStateEnum {
  @JsonValue(r'draft')
  draft(r'draft'),
  @JsonValue(r'published')
  published(r'published'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const CurriculumAssessmentStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
