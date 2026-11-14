//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/reasoning_feedback_fallacies_inner.dart';
import 'package:json_annotation/json_annotation.dart';

part 'reasoning_feedback.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ReasoningFeedback {
  /// Returns a new [ReasoningFeedback] instance.
  ReasoningFeedback({
    required this.structure,

    required this.evidence,

    required this.rebuttal,

    required this.fallacies,

    required this.summary,
  });

  @JsonKey(name: r'structure', required: true, includeIfNull: false)
  final String structure;

  @JsonKey(name: r'evidence', required: true, includeIfNull: false)
  final String evidence;

  @JsonKey(name: r'rebuttal', required: true, includeIfNull: false)
  final String rebuttal;

  @JsonKey(name: r'fallacies', required: true, includeIfNull: false)
  final List<ReasoningFeedbackFallaciesInner> fallacies;

  @JsonKey(name: r'summary', required: true, includeIfNull: false)
  final String summary;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ReasoningFeedback &&
          other.structure == structure &&
          other.evidence == evidence &&
          other.rebuttal == rebuttal &&
          other.fallacies == fallacies &&
          other.summary == summary;

  @override
  int get hashCode =>
      structure.hashCode +
      evidence.hashCode +
      rebuttal.hashCode +
      fallacies.hashCode +
      summary.hashCode;

  factory ReasoningFeedback.fromJson(Map<String, dynamic> json) =>
      _$ReasoningFeedbackFromJson(json);

  Map<String, dynamic> toJson() => _$ReasoningFeedbackToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
