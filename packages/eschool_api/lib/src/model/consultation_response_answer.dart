//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'consultation_response_answer.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ConsultationResponseAnswer {
  /// Returns a new [ConsultationResponseAnswer] instance.
  ConsultationResponseAnswer({
    required this.questionId,

    required this.optionIds,

    required this.text,
  });

  // minimum: 1
  @JsonKey(name: r'question_id', required: true, includeIfNull: false)
  final int questionId;

  @JsonKey(name: r'option_ids', required: true, includeIfNull: false)
  final List<int> optionIds;

  @JsonKey(name: r'text', required: true, includeIfNull: true)
  final String? text;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ConsultationResponseAnswer &&
          other.questionId == questionId &&
          other.optionIds == optionIds &&
          other.text == text;

  @override
  int get hashCode =>
      questionId.hashCode +
      optionIds.hashCode +
      (text == null ? 0 : text.hashCode);

  factory ConsultationResponseAnswer.fromJson(Map<String, dynamic> json) =>
      _$ConsultationResponseAnswerFromJson(json);

  Map<String, dynamic> toJson() => _$ConsultationResponseAnswerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
