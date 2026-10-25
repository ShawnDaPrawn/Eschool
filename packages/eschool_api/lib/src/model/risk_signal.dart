//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'risk_signal.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RiskSignal {
  /// Returns a new [RiskSignal] instance.
  RiskSignal({
    required this.source_,

    required this.score,

    required this.signalCount,

    required this.reasonCode,

    required this.gradeLevelId,

    required this.asOf,
  });

  @JsonKey(
    name: r'source',
    required: true,
    includeIfNull: false,
    unknownEnumValue: RiskSignalSource_Enum.unknownDefaultOpenApi,
  )
  final RiskSignalSource_Enum source_;

  // minimum: 0
  // maximum: 100
  @JsonKey(name: r'score', required: true, includeIfNull: true)
  final int? score;

  // minimum: 0
  @JsonKey(name: r'signal_count', required: true, includeIfNull: true)
  final int? signalCount;

  @JsonKey(name: r'reason_code', required: true, includeIfNull: false)
  final String reasonCode;

  // minimum: 1
  @JsonKey(name: r'grade_level_id', required: true, includeIfNull: false)
  final int gradeLevelId;

  @JsonKey(name: r'as_of', required: true, includeIfNull: false)
  final String asOf;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RiskSignal &&
          other.source_ == source_ &&
          other.score == score &&
          other.signalCount == signalCount &&
          other.reasonCode == reasonCode &&
          other.gradeLevelId == gradeLevelId &&
          other.asOf == asOf;

  @override
  int get hashCode =>
      source_.hashCode +
      (score == null ? 0 : score.hashCode) +
      (signalCount == null ? 0 : signalCount.hashCode) +
      reasonCode.hashCode +
      gradeLevelId.hashCode +
      asOf.hashCode;

  factory RiskSignal.fromJson(Map<String, dynamic> json) =>
      _$RiskSignalFromJson(json);

  Map<String, dynamic> toJson() => _$RiskSignalToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum RiskSignalSource_Enum {
  @JsonValue(r'academic')
  academic(r'academic'),
  @JsonValue(r'attendance')
  attendance(r'attendance'),
  @JsonValue(r'discipline')
  discipline(r'discipline'),
  @JsonValue(r'wellbeing')
  wellbeing(r'wellbeing'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const RiskSignalSource_Enum(this.value);

  final String value;

  @override
  String toString() => value;
}
