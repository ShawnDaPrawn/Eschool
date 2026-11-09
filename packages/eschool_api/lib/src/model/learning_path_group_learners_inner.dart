//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/learning_path_topic.dart';
import 'package:json_annotation/json_annotation.dart';

part 'learning_path_group_learners_inner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LearningPathGroupLearnersInner {
  /// Returns a new [LearningPathGroupLearnersInner] instance.
  LearningPathGroupLearnersInner({
    required this.learnerId,

    required this.displayName,

    required this.step,

    required this.stepLabel,

    required this.topic,

    required this.restingTopic,

    required this.needsSupport,

    required this.nextReviewOn,

    required this.updatedAt,
  });

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'display_name', required: true, includeIfNull: false)
  final String displayName;

  @JsonKey(
    name: r'step',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        LearningPathGroupLearnersInnerStepEnum.unknownDefaultOpenApi,
  )
  final LearningPathGroupLearnersInnerStepEnum step;

  @JsonKey(name: r'step_label', required: true, includeIfNull: false)
  final String stepLabel;

  @JsonKey(name: r'topic', required: true, includeIfNull: true)
  final LearningPathTopic? topic;

  @JsonKey(name: r'resting_topic', required: true, includeIfNull: true)
  final String? restingTopic;

  @JsonKey(name: r'needs_support', required: true, includeIfNull: false)
  final bool needsSupport;

  @JsonKey(name: r'next_review_on', required: true, includeIfNull: true)
  final String? nextReviewOn;

  @JsonKey(name: r'updated_at', required: true, includeIfNull: true)
  final DateTime? updatedAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LearningPathGroupLearnersInner &&
          other.learnerId == learnerId &&
          other.displayName == displayName &&
          other.step == step &&
          other.stepLabel == stepLabel &&
          other.topic == topic &&
          other.restingTopic == restingTopic &&
          other.needsSupport == needsSupport &&
          other.nextReviewOn == nextReviewOn &&
          other.updatedAt == updatedAt;

  @override
  int get hashCode =>
      learnerId.hashCode +
      displayName.hashCode +
      step.hashCode +
      stepLabel.hashCode +
      (topic == null ? 0 : topic.hashCode) +
      (restingTopic == null ? 0 : restingTopic.hashCode) +
      needsSupport.hashCode +
      (nextReviewOn == null ? 0 : nextReviewOn.hashCode) +
      (updatedAt == null ? 0 : updatedAt.hashCode);

  factory LearningPathGroupLearnersInner.fromJson(Map<String, dynamic> json) =>
      _$LearningPathGroupLearnersInnerFromJson(json);

  Map<String, dynamic> toJson() => _$LearningPathGroupLearnersInnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum LearningPathGroupLearnersInnerStepEnum {
  @JsonValue(r'not_started')
  notStarted(r'not_started'),
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

  const LearningPathGroupLearnersInnerStepEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
