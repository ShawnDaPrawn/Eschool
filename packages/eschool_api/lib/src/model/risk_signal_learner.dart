//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/risk_signal.dart';
import 'package:json_annotation/json_annotation.dart';

part 'risk_signal_learner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RiskSignalLearner {
  /// Returns a new [RiskSignalLearner] instance.
  RiskSignalLearner({
    required this.learnerId,

    required this.name,

    required this.signals,
  });

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'signals', required: true, includeIfNull: false)
  final List<RiskSignal> signals;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RiskSignalLearner &&
          other.learnerId == learnerId &&
          other.name == name &&
          other.signals == signals;

  @override
  int get hashCode => learnerId.hashCode + name.hashCode + signals.hashCode;

  factory RiskSignalLearner.fromJson(Map<String, dynamic> json) =>
      _$RiskSignalLearnerFromJson(json);

  Map<String, dynamic> toJson() => _$RiskSignalLearnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
