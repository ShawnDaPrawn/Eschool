//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'reading_answer_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ReadingAnswerInput {
  /// Returns a new [ReadingAnswerInput] instance.
  ReadingAnswerInput({required this.choice});

  // minimum: 0
  // maximum: 4
  @JsonKey(name: r'choice', required: true, includeIfNull: false)
  final int choice;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ReadingAnswerInput && other.choice == choice;

  @override
  int get hashCode => choice.hashCode;

  factory ReadingAnswerInput.fromJson(Map<String, dynamic> json) =>
      _$ReadingAnswerInputFromJson(json);

  Map<String, dynamic> toJson() => _$ReadingAnswerInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
