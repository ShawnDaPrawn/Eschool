//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'tutor_feedback.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TutorFeedback {
  /// Returns a new [TutorFeedback] instance.
  TutorFeedback({
    required this.subjectToken,

    required this.kind,

    required this.text,

    required this.question,
  });

  @JsonKey(name: r'subject_token', required: true, includeIfNull: false)
  final String subjectToken;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: TutorFeedbackKindEnum.unknownDefaultOpenApi,
  )
  final TutorFeedbackKindEnum kind;

  @JsonKey(name: r'text', required: true, includeIfNull: false)
  final String text;

  @JsonKey(name: r'question', required: true, includeIfNull: false)
  final String question;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TutorFeedback &&
          other.subjectToken == subjectToken &&
          other.kind == kind &&
          other.text == text &&
          other.question == question;

  @override
  int get hashCode =>
      subjectToken.hashCode + kind.hashCode + text.hashCode + question.hashCode;

  factory TutorFeedback.fromJson(Map<String, dynamic> json) =>
      _$TutorFeedbackFromJson(json);

  Map<String, dynamic> toJson() => _$TutorFeedbackToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum TutorFeedbackKindEnum {
  @JsonValue(r'hint')
  hint(r'hint'),
  @JsonValue(r'question')
  question(r'question'),
  @JsonValue(r'correction')
  correction(r'correction'),
  @JsonValue(r'refusal')
  refusal(r'refusal'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const TutorFeedbackKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
