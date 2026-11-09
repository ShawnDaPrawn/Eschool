//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'practice_test_result_topic.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeTestResultTopic {
  /// Returns a new [PracticeTestResultTopic] instance.
  PracticeTestResultTopic({
    required this.topicId,

    required this.name,

    required this.correct,

    required this.answered,

    required this.total,
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

  // minimum: 1
  @JsonKey(name: r'total', required: true, includeIfNull: false)
  final int total;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeTestResultTopic &&
          other.topicId == topicId &&
          other.name == name &&
          other.correct == correct &&
          other.answered == answered &&
          other.total == total;

  @override
  int get hashCode =>
      topicId.hashCode +
      name.hashCode +
      correct.hashCode +
      answered.hashCode +
      total.hashCode;

  factory PracticeTestResultTopic.fromJson(Map<String, dynamic> json) =>
      _$PracticeTestResultTopicFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeTestResultTopicToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
