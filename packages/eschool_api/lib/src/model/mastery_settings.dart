//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'mastery_settings.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class MasterySettings {
  /// Returns a new [MasterySettings] instance.
  MasterySettings({
    required this.familyMarksEnabled,

    required this.revision,

    required this.policyVersion,
  });

  @JsonKey(name: r'family_marks_enabled', required: true, includeIfNull: false)
  final bool familyMarksEnabled;

  // minimum: 0
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'policy_version', required: true, includeIfNull: false)
  final String policyVersion;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is MasterySettings &&
          other.familyMarksEnabled == familyMarksEnabled &&
          other.revision == revision &&
          other.policyVersion == policyVersion;

  @override
  int get hashCode =>
      familyMarksEnabled.hashCode + revision.hashCode + policyVersion.hashCode;

  factory MasterySettings.fromJson(Map<String, dynamic> json) =>
      _$MasterySettingsFromJson(json);

  Map<String, dynamic> toJson() => _$MasterySettingsToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
