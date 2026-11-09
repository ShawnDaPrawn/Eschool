//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'learning_path_family_summary_subjects_inner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LearningPathFamilySummarySubjectsInner {
  /// Returns a new [LearningPathFamilySummarySubjectsInner] instance.
  LearningPathFamilySummarySubjectsInner({
    required this.subjectId,

    required this.subjectName,

    required this.step,

    required this.stepLabel,

    required this.topicName,

    required this.nextReviewOn,

    required this.updatedOn,
  });

  // minimum: 1
  @JsonKey(name: r'subject_id', required: true, includeIfNull: false)
  final int subjectId;

  @JsonKey(name: r'subject_name', required: true, includeIfNull: false)
  final String subjectName;

  @JsonKey(
    name: r'step',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        LearningPathFamilySummarySubjectsInnerStepEnum.unknownDefaultOpenApi,
  )
  final LearningPathFamilySummarySubjectsInnerStepEnum step;

  @JsonKey(name: r'step_label', required: true, includeIfNull: false)
  final String stepLabel;

  @JsonKey(name: r'topic_name', required: true, includeIfNull: true)
  final String? topicName;

  @JsonKey(name: r'next_review_on', required: true, includeIfNull: true)
  final String? nextReviewOn;

  @JsonKey(name: r'updated_on', required: true, includeIfNull: true)
  final String? updatedOn;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LearningPathFamilySummarySubjectsInner &&
          other.subjectId == subjectId &&
          other.subjectName == subjectName &&
          other.step == step &&
          other.stepLabel == stepLabel &&
          other.topicName == topicName &&
          other.nextReviewOn == nextReviewOn &&
          other.updatedOn == updatedOn;

  @override
  int get hashCode =>
      subjectId.hashCode +
      subjectName.hashCode +
      step.hashCode +
      stepLabel.hashCode +
      (topicName == null ? 0 : topicName.hashCode) +
      (nextReviewOn == null ? 0 : nextReviewOn.hashCode) +
      (updatedOn == null ? 0 : updatedOn.hashCode);

  factory LearningPathFamilySummarySubjectsInner.fromJson(
    Map<String, dynamic> json,
  ) => _$LearningPathFamilySummarySubjectsInnerFromJson(json);

  Map<String, dynamic> toJson() =>
      _$LearningPathFamilySummarySubjectsInnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum LearningPathFamilySummarySubjectsInnerStepEnum {
  @JsonValue(r'gap')
  gap(r'gap'),
  @JsonValue(r'teach')
  teach(r'teach'),
  @JsonValue(r'practise')
  practise(r'practise'),
  @JsonValue(r'check')
  check(r'check'),
  @JsonValue(r'review')
  review(r'review'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const LearningPathFamilySummarySubjectsInnerStepEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
