//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'learning_path_action_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class LearningPathActionInput {
  /// Returns a new [LearningPathActionInput] instance.
  LearningPathActionInput({
    required this.operationId,

    required this.expectedRevision,

    required this.action,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 0
  @JsonKey(name: r'expected_revision', required: true, includeIfNull: false)
  final int expectedRevision;

  @JsonKey(
    name: r'action',
    required: true,
    includeIfNull: false,
    unknownEnumValue: LearningPathActionInputActionEnum.unknownDefaultOpenApi,
  )
  final LearningPathActionInputActionEnum action;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is LearningPathActionInput &&
          other.operationId == operationId &&
          other.expectedRevision == expectedRevision &&
          other.action == action;

  @override
  int get hashCode =>
      operationId.hashCode + expectedRevision.hashCode + action.hashCode;

  factory LearningPathActionInput.fromJson(Map<String, dynamic> json) =>
      _$LearningPathActionInputFromJson(json);

  Map<String, dynamic> toJson() => _$LearningPathActionInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum LearningPathActionInputActionEnum {
  @JsonValue(r'next')
  next(r'next'),
  @JsonValue(r'open_tutor')
  openTutor(r'open_tutor'),
  @JsonValue(r'practise')
  practise(r'practise'),
  @JsonValue(r'check')
  check(r'check'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const LearningPathActionInputActionEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
