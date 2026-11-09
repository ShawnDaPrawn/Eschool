//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'strengths_topic.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class StrengthsTopic {
  /// Returns a new [StrengthsTopic] instance.
  StrengthsTopic({
    required this.topicId,

    required this.topic,

    required this.subjectId,

    required this.subject,
  });

  @JsonKey(name: r'topic_id', required: true, includeIfNull: false)
  final int topicId;

  @JsonKey(name: r'topic', required: true, includeIfNull: false)
  final String topic;

  @JsonKey(name: r'subject_id', required: true, includeIfNull: false)
  final int subjectId;

  @JsonKey(name: r'subject', required: true, includeIfNull: false)
  final String subject;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is StrengthsTopic &&
          other.topicId == topicId &&
          other.topic == topic &&
          other.subjectId == subjectId &&
          other.subject == subject;

  @override
  int get hashCode =>
      topicId.hashCode + topic.hashCode + subjectId.hashCode + subject.hashCode;

  factory StrengthsTopic.fromJson(Map<String, dynamic> json) =>
      _$StrengthsTopicFromJson(json);

  Map<String, dynamic> toJson() => _$StrengthsTopicToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
