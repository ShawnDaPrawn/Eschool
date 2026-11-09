//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'practice_quick_quiz_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeQuickQuizInput {
  /// Returns a new [PracticeQuickQuizInput] instance.
  PracticeQuickQuizInput({
    required this.operationId,

    required this.topicIds,

    required this.questionCount,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'topic_ids', required: true, includeIfNull: false)
  final Set<int> topicIds;

  // minimum: 3
  // maximum: 10
  @JsonKey(name: r'question_count', required: true, includeIfNull: false)
  final int questionCount;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeQuickQuizInput &&
          other.operationId == operationId &&
          other.topicIds == topicIds &&
          other.questionCount == questionCount;

  @override
  int get hashCode =>
      operationId.hashCode + topicIds.hashCode + questionCount.hashCode;

  factory PracticeQuickQuizInput.fromJson(Map<String, dynamic> json) =>
      _$PracticeQuickQuizInputFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeQuickQuizInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
