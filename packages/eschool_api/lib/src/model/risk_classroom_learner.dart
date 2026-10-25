//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/risk_classroom_signal.dart';
import 'package:json_annotation/json_annotation.dart';

part 'risk_classroom_learner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RiskClassroomLearner {
  /// Returns a new [RiskClassroomLearner] instance.
  RiskClassroomLearner({
    required this.learnerId,

    required this.name,

    required this.observedScore,

    required this.signals,
  });

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  // minimum: 0
  // maximum: 200
  @JsonKey(name: r'observed_score', required: true, includeIfNull: true)
  final int? observedScore;

  @JsonKey(name: r'signals', required: true, includeIfNull: false)
  final List<RiskClassroomSignal> signals;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RiskClassroomLearner &&
          other.learnerId == learnerId &&
          other.name == name &&
          other.observedScore == observedScore &&
          other.signals == signals;

  @override
  int get hashCode =>
      learnerId.hashCode +
      name.hashCode +
      (observedScore == null ? 0 : observedScore.hashCode) +
      signals.hashCode;

  factory RiskClassroomLearner.fromJson(Map<String, dynamic> json) =>
      _$RiskClassroomLearnerFromJson(json);

  Map<String, dynamic> toJson() => _$RiskClassroomLearnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
