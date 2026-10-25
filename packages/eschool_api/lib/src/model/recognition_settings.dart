//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'recognition_settings.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RecognitionSettings {
  /// Returns a new [RecognitionSettings] instance.
  RecognitionSettings({
    required this.revision,

    required this.dailyStaffCap,

    required this.publicPointsAllowed,

    required this.principalApprovalRequired,
  });

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  // minimum: 1
  @JsonKey(name: r'daily_staff_cap', required: true, includeIfNull: false)
  final int dailyStaffCap;

  @JsonKey(name: r'public_points_allowed', required: true, includeIfNull: false)
  final bool publicPointsAllowed;

  @JsonKey(
    name: r'principal_approval_required',
    required: true,
    includeIfNull: false,
  )
  final bool principalApprovalRequired;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RecognitionSettings &&
          other.revision == revision &&
          other.dailyStaffCap == dailyStaffCap &&
          other.publicPointsAllowed == publicPointsAllowed &&
          other.principalApprovalRequired == principalApprovalRequired;

  @override
  int get hashCode =>
      revision.hashCode +
      dailyStaffCap.hashCode +
      publicPointsAllowed.hashCode +
      principalApprovalRequired.hashCode;

  factory RecognitionSettings.fromJson(Map<String, dynamic> json) =>
      _$RecognitionSettingsFromJson(json);

  Map<String, dynamic> toJson() => _$RecognitionSettingsToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
