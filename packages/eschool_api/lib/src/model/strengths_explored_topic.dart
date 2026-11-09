//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'strengths_explored_topic.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class StrengthsExploredTopic {
  /// Returns a new [StrengthsExploredTopic] instance.
  StrengthsExploredTopic({required this.topicId, required this.topic});

  @JsonKey(name: r'topic_id', required: true, includeIfNull: false)
  final int topicId;

  @JsonKey(name: r'topic', required: true, includeIfNull: false)
  final String topic;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is StrengthsExploredTopic &&
          other.topicId == topicId &&
          other.topic == topic;

  @override
  int get hashCode => topicId.hashCode + topic.hashCode;

  factory StrengthsExploredTopic.fromJson(Map<String, dynamic> json) =>
      _$StrengthsExploredTopicFromJson(json);

  Map<String, dynamic> toJson() => _$StrengthsExploredTopicToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
