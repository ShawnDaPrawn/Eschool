//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'practice_question_option.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeQuestionOption {
  /// Returns a new [PracticeQuestionOption] instance.
  PracticeQuestionOption({required this.id, required this.text});

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  @JsonKey(name: r'text', required: true, includeIfNull: false)
  final String text;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeQuestionOption && other.id == id && other.text == text;

  @override
  int get hashCode => id.hashCode + text.hashCode;

  factory PracticeQuestionOption.fromJson(Map<String, dynamic> json) =>
      _$PracticeQuestionOptionFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeQuestionOptionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
