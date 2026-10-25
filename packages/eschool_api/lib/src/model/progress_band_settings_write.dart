//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'progress_band_settings_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ProgressBandSettingsWrite {
  /// Returns a new [ProgressBandSettingsWrite] instance.
  ProgressBandSettingsWrite({
    required this.operationId,

    required this.expectedRevision,

    required this.highBp,

    required this.secureBp,

    required this.minimumCoverageBp,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 0
  @JsonKey(name: r'expected_revision', required: true, includeIfNull: false)
  final int expectedRevision;

  // minimum: 1
  // maximum: 10000
  @JsonKey(name: r'high_bp', required: true, includeIfNull: false)
  final int highBp;

  // minimum: 0
  // maximum: 9999
  @JsonKey(name: r'secure_bp', required: true, includeIfNull: false)
  final int secureBp;

  // minimum: 1
  // maximum: 10000
  @JsonKey(name: r'minimum_coverage_bp', required: true, includeIfNull: false)
  final int minimumCoverageBp;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ProgressBandSettingsWrite &&
          other.operationId == operationId &&
          other.expectedRevision == expectedRevision &&
          other.highBp == highBp &&
          other.secureBp == secureBp &&
          other.minimumCoverageBp == minimumCoverageBp;

  @override
  int get hashCode =>
      operationId.hashCode +
      expectedRevision.hashCode +
      highBp.hashCode +
      secureBp.hashCode +
      minimumCoverageBp.hashCode;

  factory ProgressBandSettingsWrite.fromJson(Map<String, dynamic> json) =>
      _$ProgressBandSettingsWriteFromJson(json);

  Map<String, dynamic> toJson() => _$ProgressBandSettingsWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
