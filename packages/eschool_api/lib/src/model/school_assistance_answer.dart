//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'school_assistance_answer.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SchoolAssistanceAnswer {
  /// Returns a new [SchoolAssistanceAnswer] instance.
  SchoolAssistanceAnswer({
    required this.language,

    required this.text,

    required this.citations,

    required this.handoff,
  });

  @JsonKey(name: r'language', required: true, includeIfNull: false)
  final String language;

  @JsonKey(name: r'text', required: true, includeIfNull: false)
  final String text;

  @JsonKey(name: r'citations', required: true, includeIfNull: false)
  final List<String> citations;

  @JsonKey(name: r'handoff', required: true, includeIfNull: false)
  final bool handoff;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SchoolAssistanceAnswer &&
          other.language == language &&
          other.text == text &&
          other.citations == citations &&
          other.handoff == handoff;

  @override
  int get hashCode =>
      language.hashCode + text.hashCode + citations.hashCode + handoff.hashCode;

  factory SchoolAssistanceAnswer.fromJson(Map<String, dynamic> json) =>
      _$SchoolAssistanceAnswerFromJson(json);

  Map<String, dynamic> toJson() => _$SchoolAssistanceAnswerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
