//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'foundation_lease_summary.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FoundationLeaseSummary {
  /// Returns a new [FoundationLeaseSummary] instance.
  FoundationLeaseSummary({
    required this.id,

    required this.deviceId,

    required this.teachingGroupId,

    required this.expiresAt,

    required this.active,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  @JsonKey(name: r'device_id', required: true, includeIfNull: false)
  final String deviceId;

  // minimum: 1
  @JsonKey(name: r'teaching_group_id', required: true, includeIfNull: false)
  final int teachingGroupId;

  @JsonKey(name: r'expires_at', required: true, includeIfNull: false)
  final DateTime expiresAt;

  @JsonKey(name: r'active', required: true, includeIfNull: false)
  final bool active;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FoundationLeaseSummary &&
          other.id == id &&
          other.deviceId == deviceId &&
          other.teachingGroupId == teachingGroupId &&
          other.expiresAt == expiresAt &&
          other.active == active;

  @override
  int get hashCode =>
      id.hashCode +
      deviceId.hashCode +
      teachingGroupId.hashCode +
      expiresAt.hashCode +
      active.hashCode;

  factory FoundationLeaseSummary.fromJson(Map<String, dynamic> json) =>
      _$FoundationLeaseSummaryFromJson(json);

  Map<String, dynamic> toJson() => _$FoundationLeaseSummaryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
