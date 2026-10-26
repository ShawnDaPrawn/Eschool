//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/practice_question.dart';
import 'package:json_annotation/json_annotation.dart';

part 'teaching_practice_question.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TeachingPracticeQuestion {
  /// Returns a new [TeachingPracticeQuestion] instance.
  TeachingPracticeQuestion({
    required this.topicId,

    required this.estimatedSeconds,

    required this.definition,
  });

  // minimum: 1
  @JsonKey(name: r'topic_id', required: true, includeIfNull: false)
  final int topicId;

  // minimum: 60
  // maximum: 300
  @JsonKey(name: r'estimated_seconds', required: true, includeIfNull: false)
  final int estimatedSeconds;

  @JsonKey(name: r'definition', required: true, includeIfNull: false)
  final PracticeQuestion definition;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TeachingPracticeQuestion &&
          other.topicId == topicId &&
          other.estimatedSeconds == estimatedSeconds &&
          other.definition == definition;

  @override
  int get hashCode =>
      topicId.hashCode + estimatedSeconds.hashCode + definition.hashCode;

  factory TeachingPracticeQuestion.fromJson(Map<String, dynamic> json) =>
      _$TeachingPracticeQuestionFromJson(json);

  Map<String, dynamic> toJson() => _$TeachingPracticeQuestionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
