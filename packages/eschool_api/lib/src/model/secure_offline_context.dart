//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'secure_offline_context.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SecureOfflineContext {
  /// Returns a new [SecureOfflineContext] instance.
  SecureOfflineContext({
    required this.schoolId,

    required this.deviceId,

    required this.snapshotId,

    required this.issuedAt,

    required this.expiresAt,
  });

  // minimum: 1
  // maximum: 9007199254740991
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  @JsonKey(name: r'device_id', required: true, includeIfNull: false)
  final String deviceId;

  @JsonKey(name: r'snapshot_id', required: true, includeIfNull: false)
  final String snapshotId;

  // minimum: 1
  @JsonKey(name: r'issued_at', required: true, includeIfNull: false)
  final int issuedAt;

  // minimum: 1
  @JsonKey(name: r'expires_at', required: true, includeIfNull: false)
  final int expiresAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SecureOfflineContext &&
          other.schoolId == schoolId &&
          other.deviceId == deviceId &&
          other.snapshotId == snapshotId &&
          other.issuedAt == issuedAt &&
          other.expiresAt == expiresAt;

  @override
  int get hashCode =>
      schoolId.hashCode +
      deviceId.hashCode +
      snapshotId.hashCode +
      issuedAt.hashCode +
      expiresAt.hashCode;

  factory SecureOfflineContext.fromJson(Map<String, dynamic> json) =>
      _$SecureOfflineContextFromJson(json);

  Map<String, dynamic> toJson() => _$SecureOfflineContextToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
