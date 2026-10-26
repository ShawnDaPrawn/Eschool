//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'tutor_checkpoint_answer_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TutorCheckpointAnswerWrite {
  /// Returns a new [TutorCheckpointAnswerWrite] instance.
  TutorCheckpointAnswerWrite({required this.operationId, required this.answer});

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'answer', required: true, includeIfNull: false)
  final String answer;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TutorCheckpointAnswerWrite &&
          other.operationId == operationId &&
          other.answer == answer;

  @override
  int get hashCode => operationId.hashCode + answer.hashCode;

  factory TutorCheckpointAnswerWrite.fromJson(Map<String, dynamic> json) =>
      _$TutorCheckpointAnswerWriteFromJson(json);

  Map<String, dynamic> toJson() => _$TutorCheckpointAnswerWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
