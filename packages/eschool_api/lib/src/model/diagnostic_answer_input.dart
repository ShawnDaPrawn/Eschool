//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'diagnostic_answer_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class DiagnosticAnswerInput {
  /// Returns a new [DiagnosticAnswerInput] instance.
  DiagnosticAnswerInput({required this.answer});

  @JsonKey(name: r'answer', required: true, includeIfNull: false)
  final String answer;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is DiagnosticAnswerInput && other.answer == answer;

  @override
  int get hashCode => answer.hashCode;

  factory DiagnosticAnswerInput.fromJson(Map<String, dynamic> json) =>
      _$DiagnosticAnswerInputFromJson(json);

  Map<String, dynamic> toJson() => _$DiagnosticAnswerInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
