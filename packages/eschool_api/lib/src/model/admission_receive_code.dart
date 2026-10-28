//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'admission_receive_code.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AdmissionReceiveCode {
  /// Returns a new [AdmissionReceiveCode] instance.
  AdmissionReceiveCode({
    required this.schoolId,

    required this.actorUserId,

    required this.receiveCode,

    required this.expiresAt,
  });

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'actor_user_id', required: true, includeIfNull: false)
  final int actorUserId;

  @JsonKey(name: r'receive_code', required: true, includeIfNull: false)
  final String receiveCode;

  @JsonKey(name: r'expires_at', required: true, includeIfNull: false)
  final DateTime expiresAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AdmissionReceiveCode &&
          other.schoolId == schoolId &&
          other.actorUserId == actorUserId &&
          other.receiveCode == receiveCode &&
          other.expiresAt == expiresAt;

  @override
  int get hashCode =>
      schoolId.hashCode +
      actorUserId.hashCode +
      receiveCode.hashCode +
      expiresAt.hashCode;

  factory AdmissionReceiveCode.fromJson(Map<String, dynamic> json) =>
      _$AdmissionReceiveCodeFromJson(json);

  Map<String, dynamic> toJson() => _$AdmissionReceiveCodeToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
