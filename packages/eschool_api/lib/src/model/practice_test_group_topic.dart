//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'practice_test_group_topic.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeTestGroupTopic {
  /// Returns a new [PracticeTestGroupTopic] instance.
  PracticeTestGroupTopic({
    required this.topicId,

    required this.name,

    required this.correct,

    required this.answered,
  });

  // minimum: 1
  @JsonKey(name: r'topic_id', required: true, includeIfNull: false)
  final int topicId;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  // minimum: 0
  @JsonKey(name: r'correct', required: true, includeIfNull: false)
  final int correct;

  // minimum: 0
  @JsonKey(name: r'answered', required: true, includeIfNull: false)
  final int answered;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeTestGroupTopic &&
          other.topicId == topicId &&
          other.name == name &&
          other.correct == correct &&
          other.answered == answered;

  @override
  int get hashCode =>
      topicId.hashCode + name.hashCode + correct.hashCode + answered.hashCode;

  factory PracticeTestGroupTopic.fromJson(Map<String, dynamic> json) =>
      _$PracticeTestGroupTopicFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeTestGroupTopicToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
