//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'wellbeing_access.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WellbeingAccess {
  /// Returns a new [WellbeingAccess] instance.
  WellbeingAccess({
    required this.canConfigure,

    required this.professional,

    required this.canTriage,

    required this.canCasework,

    required this.canWrite,

    required this.today,

    required this.schoolSlug,

    required this.emergencyGuidance,

    this.timezone,
  });

  @JsonKey(name: r'can_configure', required: true, includeIfNull: false)
  final bool canConfigure;

  @JsonKey(name: r'professional', required: true, includeIfNull: false)
  final bool professional;

  @JsonKey(name: r'can_triage', required: true, includeIfNull: false)
  final bool canTriage;

  @JsonKey(name: r'can_casework', required: true, includeIfNull: false)
  final bool canCasework;

  @JsonKey(name: r'can_write', required: true, includeIfNull: false)
  final bool canWrite;

  @JsonKey(name: r'today', required: true, includeIfNull: false)
  final String today;

  @JsonKey(name: r'school_slug', required: true, includeIfNull: false)
  final String schoolSlug;

  @JsonKey(name: r'emergency_guidance', required: true, includeIfNull: false)
  final String emergencyGuidance;

  /// Trusted selected-school IANA timezone for appointment wall times; optional for older servers.
  @JsonKey(name: r'timezone', required: false, includeIfNull: false)
  final String? timezone;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WellbeingAccess &&
          other.canConfigure == canConfigure &&
          other.professional == professional &&
          other.canTriage == canTriage &&
          other.canCasework == canCasework &&
          other.canWrite == canWrite &&
          other.today == today &&
          other.schoolSlug == schoolSlug &&
          other.emergencyGuidance == emergencyGuidance &&
          other.timezone == timezone;

  @override
  int get hashCode =>
      canConfigure.hashCode +
      professional.hashCode +
      canTriage.hashCode +
      canCasework.hashCode +
      canWrite.hashCode +
      today.hashCode +
      schoolSlug.hashCode +
      emergencyGuidance.hashCode +
      timezone.hashCode;

  factory WellbeingAccess.fromJson(Map<String, dynamic> json) =>
      _$WellbeingAccessFromJson(json);

  Map<String, dynamic> toJson() => _$WellbeingAccessToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
