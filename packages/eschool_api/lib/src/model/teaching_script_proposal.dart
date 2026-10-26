//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'teaching_script_proposal.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TeachingScriptProposal {
  /// Returns a new [TeachingScriptProposal] instance.
  TeachingScriptProposal({
    required this.questionId,

    required this.mark,

    required this.confidenceBp,

    required this.reason,
  });

  // minimum: 1
  @JsonKey(name: r'question_id', required: true, includeIfNull: false)
  final int questionId;

  @JsonKey(name: r'mark', required: true, includeIfNull: true)
  final String? mark;

  // minimum: 0
  // maximum: 10000
  @JsonKey(name: r'confidence_bp', required: true, includeIfNull: false)
  final int confidenceBp;

  @JsonKey(name: r'reason', required: true, includeIfNull: false)
  final String reason;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TeachingScriptProposal &&
          other.questionId == questionId &&
          other.mark == mark &&
          other.confidenceBp == confidenceBp &&
          other.reason == reason;

  @override
  int get hashCode =>
      questionId.hashCode +
      (mark == null ? 0 : mark.hashCode) +
      confidenceBp.hashCode +
      reason.hashCode;

  factory TeachingScriptProposal.fromJson(Map<String, dynamic> json) =>
      _$TeachingScriptProposalFromJson(json);

  Map<String, dynamic> toJson() => _$TeachingScriptProposalToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
