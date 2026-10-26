//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'foundation_authorization.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FoundationAuthorization {
  /// Returns a new [FoundationAuthorization] instance.
  FoundationAuthorization({
    required this.id,

    required this.deviceId,

    required this.teachingGroupId,

    required this.classroomAuthorization,

    required this.expiresAt,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  @JsonKey(name: r'device_id', required: true, includeIfNull: false)
  final String deviceId;

  // minimum: 1
  @JsonKey(name: r'teaching_group_id', required: true, includeIfNull: false)
  final int teachingGroupId;

  /// Short Teacher delegation for this exact classroom device and current eligible allocated group.
  @JsonKey(
    name: r'classroom_authorization',
    required: true,
    includeIfNull: false,
  )
  final String classroomAuthorization;

  @JsonKey(name: r'expires_at', required: true, includeIfNull: false)
  final DateTime expiresAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FoundationAuthorization &&
          other.id == id &&
          other.deviceId == deviceId &&
          other.teachingGroupId == teachingGroupId &&
          other.classroomAuthorization == classroomAuthorization &&
          other.expiresAt == expiresAt;

  @override
  int get hashCode =>
      id.hashCode +
      deviceId.hashCode +
      teachingGroupId.hashCode +
      classroomAuthorization.hashCode +
      expiresAt.hashCode;

  factory FoundationAuthorization.fromJson(Map<String, dynamic> json) =>
      _$FoundationAuthorizationFromJson(json);

  Map<String, dynamic> toJson() => _$FoundationAuthorizationToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
