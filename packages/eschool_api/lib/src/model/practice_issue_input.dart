//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'practice_issue_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeIssueInput {
  /// Returns a new [PracticeIssueInput] instance.
  PracticeIssueInput({required this.operationId, required this.practiceOn});

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  @JsonKey(name: r'practice_on', required: true, includeIfNull: false)
  final String practiceOn;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeIssueInput &&
          other.operationId == operationId &&
          other.practiceOn == practiceOn;

  @override
  int get hashCode => operationId.hashCode + practiceOn.hashCode;

  factory PracticeIssueInput.fromJson(Map<String, dynamic> json) =>
      _$PracticeIssueInputFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeIssueInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
