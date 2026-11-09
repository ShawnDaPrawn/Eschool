//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/practice_test_answer.dart';
import 'package:json_annotation/json_annotation.dart';

part 'practice_test_submit_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeTestSubmitInput {
  /// Returns a new [PracticeTestSubmitInput] instance.
  PracticeTestSubmitInput({required this.operationId, this.answers});

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'answers', required: false, includeIfNull: false)
  final List<PracticeTestAnswer>? answers;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeTestSubmitInput &&
          other.operationId == operationId &&
          other.answers == answers;

  @override
  int get hashCode => operationId.hashCode + answers.hashCode;

  factory PracticeTestSubmitInput.fromJson(Map<String, dynamic> json) =>
      _$PracticeTestSubmitInputFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeTestSubmitInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
