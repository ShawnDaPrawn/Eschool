//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'practice_test_topic_share.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeTestTopicShare {
  /// Returns a new [PracticeTestTopicShare] instance.
  PracticeTestTopicShare({
    required this.topicId,

    required this.weight,

    required this.questions,
  });

  // minimum: 1
  @JsonKey(name: r'topic_id', required: true, includeIfNull: false)
  final int topicId;

  // minimum: 1
  @JsonKey(name: r'weight', required: true, includeIfNull: false)
  final int weight;

  // minimum: 1
  @JsonKey(name: r'questions', required: true, includeIfNull: false)
  final int questions;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeTestTopicShare &&
          other.topicId == topicId &&
          other.weight == weight &&
          other.questions == questions;

  @override
  int get hashCode => topicId.hashCode + weight.hashCode + questions.hashCode;

  factory PracticeTestTopicShare.fromJson(Map<String, dynamic> json) =>
      _$PracticeTestTopicShareFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeTestTopicShareToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
