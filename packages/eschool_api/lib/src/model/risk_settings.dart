//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'risk_settings.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RiskSettings {
  /// Returns a new [RiskSettings] instance.
  RiskSettings({
    required this.academicThresholdBp,

    required this.minimumCoverageBp,

    required this.absenceThresholdBp,

    required this.minimumRegisters,

    required this.revision,

    required this.ruleVersion,
  });

  // minimum: 1
  // maximum: 10000
  @JsonKey(name: r'academic_threshold_bp', required: true, includeIfNull: false)
  final int academicThresholdBp;

  // minimum: 1
  // maximum: 10000
  @JsonKey(name: r'minimum_coverage_bp', required: true, includeIfNull: false)
  final int minimumCoverageBp;

  // minimum: 1
  // maximum: 10000
  @JsonKey(name: r'absence_threshold_bp', required: true, includeIfNull: false)
  final int absenceThresholdBp;

  // minimum: 1
  // maximum: 30
  @JsonKey(name: r'minimum_registers', required: true, includeIfNull: false)
  final int minimumRegisters;

  // minimum: 0
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'rule_version', required: true, includeIfNull: false)
  final String ruleVersion;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RiskSettings &&
          other.academicThresholdBp == academicThresholdBp &&
          other.minimumCoverageBp == minimumCoverageBp &&
          other.absenceThresholdBp == absenceThresholdBp &&
          other.minimumRegisters == minimumRegisters &&
          other.revision == revision &&
          other.ruleVersion == ruleVersion;

  @override
  int get hashCode =>
      academicThresholdBp.hashCode +
      minimumCoverageBp.hashCode +
      absenceThresholdBp.hashCode +
      minimumRegisters.hashCode +
      revision.hashCode +
      ruleVersion.hashCode;

  factory RiskSettings.fromJson(Map<String, dynamic> json) =>
      _$RiskSettingsFromJson(json);

  Map<String, dynamic> toJson() => _$RiskSettingsToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
