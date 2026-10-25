//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'risk_settings_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RiskSettingsWrite {
  /// Returns a new [RiskSettingsWrite] instance.
  RiskSettingsWrite({
    required this.operationId,

    required this.expectedRevision,

    required this.academicThresholdBp,

    required this.minimumCoverageBp,

    required this.absenceThresholdBp,

    required this.minimumRegisters,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 0
  @JsonKey(name: r'expected_revision', required: true, includeIfNull: false)
  final int expectedRevision;

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

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RiskSettingsWrite &&
          other.operationId == operationId &&
          other.expectedRevision == expectedRevision &&
          other.academicThresholdBp == academicThresholdBp &&
          other.minimumCoverageBp == minimumCoverageBp &&
          other.absenceThresholdBp == absenceThresholdBp &&
          other.minimumRegisters == minimumRegisters;

  @override
  int get hashCode =>
      operationId.hashCode +
      expectedRevision.hashCode +
      academicThresholdBp.hashCode +
      minimumCoverageBp.hashCode +
      absenceThresholdBp.hashCode +
      minimumRegisters.hashCode;

  factory RiskSettingsWrite.fromJson(Map<String, dynamic> json) =>
      _$RiskSettingsWriteFromJson(json);

  Map<String, dynamic> toJson() => _$RiskSettingsWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
