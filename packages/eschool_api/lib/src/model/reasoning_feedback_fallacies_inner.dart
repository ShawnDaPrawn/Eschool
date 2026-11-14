//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'reasoning_feedback_fallacies_inner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ReasoningFeedbackFallaciesInner {
  /// Returns a new [ReasoningFeedbackFallaciesInner] instance.
  ReasoningFeedbackFallaciesInner({
    required this.name,

    required this.explanation,
  });

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'explanation', required: true, includeIfNull: false)
  final String explanation;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ReasoningFeedbackFallaciesInner &&
          other.name == name &&
          other.explanation == explanation;

  @override
  int get hashCode => name.hashCode + explanation.hashCode;

  factory ReasoningFeedbackFallaciesInner.fromJson(Map<String, dynamic> json) =>
      _$ReasoningFeedbackFallaciesInnerFromJson(json);

  Map<String, dynamic> toJson() =>
      _$ReasoningFeedbackFallaciesInnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
