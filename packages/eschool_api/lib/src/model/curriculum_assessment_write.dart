//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/curriculum_task_write.dart';
import 'package:json_annotation/json_annotation.dart';

part 'curriculum_assessment_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CurriculumAssessmentWrite {
  /// Returns a new [CurriculumAssessmentWrite] instance.
  CurriculumAssessmentWrite({
    required this.termId,

    required this.components,

    required this.tasks,
  });

  // minimum: 1
  @JsonKey(name: r'term_id', required: true, includeIfNull: false)
  final int termId;

  @JsonKey(name: r'components', required: true, includeIfNull: false)
  final Map<String, int> components;

  @JsonKey(name: r'tasks', required: true, includeIfNull: false)
  final List<CurriculumTaskWrite> tasks;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CurriculumAssessmentWrite &&
          other.termId == termId &&
          other.components == components &&
          other.tasks == tasks;

  @override
  int get hashCode => termId.hashCode + components.hashCode + tasks.hashCode;

  factory CurriculumAssessmentWrite.fromJson(Map<String, dynamic> json) =>
      _$CurriculumAssessmentWriteFromJson(json);

  Map<String, dynamic> toJson() => _$CurriculumAssessmentWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
