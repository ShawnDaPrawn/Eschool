//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/consultation_vote_answer.dart';
import 'package:json_annotation/json_annotation.dart';

part 'consultation_vote_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ConsultationVoteInput {
  /// Returns a new [ConsultationVoteInput] instance.
  ConsultationVoteInput({required this.answers});

  @JsonKey(name: r'answers', required: true, includeIfNull: false)
  final List<ConsultationVoteAnswer> answers;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ConsultationVoteInput && other.answers == answers;

  @override
  int get hashCode => answers.hashCode;

  factory ConsultationVoteInput.fromJson(Map<String, dynamic> json) =>
      _$ConsultationVoteInputFromJson(json);

  Map<String, dynamic> toJson() => _$ConsultationVoteInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
