//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'risk_classroom_signal.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RiskClassroomSignal {
  /// Returns a new [RiskClassroomSignal] instance.
  RiskClassroomSignal({
    required this.source_,

    required this.score,

    required this.reasonCode,

    required this.asOf,
  });

  @JsonKey(
    name: r'source',
    required: true,
    includeIfNull: false,
    unknownEnumValue: RiskClassroomSignalSource_Enum.unknownDefaultOpenApi,
  )
  final RiskClassroomSignalSource_Enum source_;

  // minimum: 0
  // maximum: 100
  @JsonKey(name: r'score', required: true, includeIfNull: true)
  final int? score;

  @JsonKey(name: r'reason_code', required: true, includeIfNull: false)
  final String reasonCode;

  @JsonKey(name: r'as_of', required: true, includeIfNull: false)
  final String asOf;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RiskClassroomSignal &&
          other.source_ == source_ &&
          other.score == score &&
          other.reasonCode == reasonCode &&
          other.asOf == asOf;

  @override
  int get hashCode =>
      source_.hashCode +
      (score == null ? 0 : score.hashCode) +
      reasonCode.hashCode +
      asOf.hashCode;

  factory RiskClassroomSignal.fromJson(Map<String, dynamic> json) =>
      _$RiskClassroomSignalFromJson(json);

  Map<String, dynamic> toJson() => _$RiskClassroomSignalToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum RiskClassroomSignalSource_Enum {
  @JsonValue(r'academic')
  academic(r'academic'),
  @JsonValue(r'attendance')
  attendance(r'attendance'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const RiskClassroomSignalSource_Enum(this.value);

  final String value;

  @override
  String toString() => value;
}
