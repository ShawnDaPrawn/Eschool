//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'secure_encoding_context.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SecureEncodingContext {
  /// Returns a new [SecureEncodingContext] instance.
  SecureEncodingContext({
    required this.schoolId,

    required this.deviceId,

    required this.planId,

    required this.cardId,

    required this.revision,

    required this.issuedAt,

    required this.expiresAt,
  });

  // minimum: 1
  // maximum: 9007199254740991
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  @JsonKey(name: r'device_id', required: true, includeIfNull: false)
  final String deviceId;

  @JsonKey(name: r'plan_id', required: true, includeIfNull: false)
  final String planId;

  @JsonKey(name: r'card_id', required: true, includeIfNull: false)
  final String cardId;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  // minimum: 1
  @JsonKey(name: r'issued_at', required: true, includeIfNull: false)
  final int issuedAt;

  // minimum: 1
  @JsonKey(name: r'expires_at', required: true, includeIfNull: false)
  final int expiresAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SecureEncodingContext &&
          other.schoolId == schoolId &&
          other.deviceId == deviceId &&
          other.planId == planId &&
          other.cardId == cardId &&
          other.revision == revision &&
          other.issuedAt == issuedAt &&
          other.expiresAt == expiresAt;

  @override
  int get hashCode =>
      schoolId.hashCode +
      deviceId.hashCode +
      planId.hashCode +
      cardId.hashCode +
      revision.hashCode +
      issuedAt.hashCode +
      expiresAt.hashCode;

  factory SecureEncodingContext.fromJson(Map<String, dynamic> json) =>
      _$SecureEncodingContextFromJson(json);

  Map<String, dynamic> toJson() => _$SecureEncodingContextToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
