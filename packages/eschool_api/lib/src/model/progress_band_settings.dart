//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'progress_band_settings.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ProgressBandSettings {
  /// Returns a new [ProgressBandSettings] instance.
  ProgressBandSettings({
    required this.highBp,

    required this.secureBp,

    required this.minimumCoverageBp,

    required this.revision,
  });

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

  // minimum: 0
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ProgressBandSettings &&
          other.highBp == highBp &&
          other.secureBp == secureBp &&
          other.minimumCoverageBp == minimumCoverageBp &&
          other.revision == revision;

  @override
  int get hashCode =>
      highBp.hashCode +
      secureBp.hashCode +
      minimumCoverageBp.hashCode +
      revision.hashCode;

  factory ProgressBandSettings.fromJson(Map<String, dynamic> json) =>
      _$ProgressBandSettingsFromJson(json);

  Map<String, dynamic> toJson() => _$ProgressBandSettingsToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
