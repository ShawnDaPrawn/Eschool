//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'risk_learner_choice.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RiskLearnerChoice {
  /// Returns a new [RiskLearnerChoice] instance.
  RiskLearnerChoice({required this.learnerId, required this.name});

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RiskLearnerChoice &&
          other.learnerId == learnerId &&
          other.name == name;

  @override
  int get hashCode => learnerId.hashCode + name.hashCode;

  factory RiskLearnerChoice.fromJson(Map<String, dynamic> json) =>
      _$RiskLearnerChoiceFromJson(json);

  Map<String, dynamic> toJson() => _$RiskLearnerChoiceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
