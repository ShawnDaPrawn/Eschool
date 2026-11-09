//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'learning_path_answer_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LearningPathAnswerInput {
  /// Returns a new [LearningPathAnswerInput] instance.
  LearningPathAnswerInput({required this.operationId, required this.answer});

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'answer', required: true, includeIfNull: false)
  final String answer;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LearningPathAnswerInput &&
          other.operationId == operationId &&
          other.answer == answer;

  @override
  int get hashCode => operationId.hashCode + answer.hashCode;

  factory LearningPathAnswerInput.fromJson(Map<String, dynamic> json) =>
      _$LearningPathAnswerInputFromJson(json);

  Map<String, dynamic> toJson() => _$LearningPathAnswerInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
