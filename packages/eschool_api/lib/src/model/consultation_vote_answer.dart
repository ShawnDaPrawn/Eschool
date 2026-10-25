//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'consultation_vote_answer.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ConsultationVoteAnswer {
  /// Returns a new [ConsultationVoteAnswer] instance.
  ConsultationVoteAnswer({required this.questionId, this.optionIds, this.text});

  // minimum: 1
  @JsonKey(name: r'question_id', required: true, includeIfNull: false)
  final int questionId;

  @JsonKey(name: r'option_ids', required: false, includeIfNull: false)
  final List<int>? optionIds;

  @JsonKey(name: r'text', required: false, includeIfNull: false)
  final String? text;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ConsultationVoteAnswer &&
          other.questionId == questionId &&
          other.optionIds == optionIds &&
          other.text == text;

  @override
  int get hashCode =>
      questionId.hashCode +
      optionIds.hashCode +
      (text == null ? 0 : text.hashCode);

  factory ConsultationVoteAnswer.fromJson(Map<String, dynamic> json) =>
      _$ConsultationVoteAnswerFromJson(json);

  Map<String, dynamic> toJson() => _$ConsultationVoteAnswerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
