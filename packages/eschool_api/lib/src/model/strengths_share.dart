//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/strengths_section_key.dart';
import 'package:json_annotation/json_annotation.dart';

part 'strengths_share.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class StrengthsShare {
  /// Returns a new [StrengthsShare] instance.
  StrengthsShare({
    required this.id,

    required this.recipientUserId,

    required this.recipientName,

    required this.kind,

    required this.sections,

    required this.sharedOn,

    required this.expiresAt,

    required this.revokedAt,

    required this.lastViewedAt,

    required this.status,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'recipient_user_id', required: true, includeIfNull: false)
  final int recipientUserId;

  @JsonKey(name: r'recipient_name', required: true, includeIfNull: false)
  final String recipientName;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: StrengthsShareKindEnum.unknownDefaultOpenApi,
  )
  final StrengthsShareKindEnum kind;

  @JsonKey(name: r'sections', required: true, includeIfNull: false)
  final List<StrengthsSectionKey> sections;

  @JsonKey(name: r'shared_on', required: true, includeIfNull: false)
  final DateTime sharedOn;

  @JsonKey(name: r'expires_at', required: true, includeIfNull: false)
  final DateTime expiresAt;

  @JsonKey(name: r'revoked_at', required: true, includeIfNull: true)
  final DateTime? revokedAt;

  @JsonKey(name: r'last_viewed_at', required: true, includeIfNull: true)
  final DateTime? lastViewedAt;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: StrengthsShareStatusEnum.unknownDefaultOpenApi,
  )
  final StrengthsShareStatusEnum status;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is StrengthsShare &&
          other.id == id &&
          other.recipientUserId == recipientUserId &&
          other.recipientName == recipientName &&
          other.kind == kind &&
          other.sections == sections &&
          other.sharedOn == sharedOn &&
          other.expiresAt == expiresAt &&
          other.revokedAt == revokedAt &&
          other.lastViewedAt == lastViewedAt &&
          other.status == status;

  @override
  int get hashCode =>
      id.hashCode +
      recipientUserId.hashCode +
      recipientName.hashCode +
      kind.hashCode +
      sections.hashCode +
      sharedOn.hashCode +
      expiresAt.hashCode +
      (revokedAt == null ? 0 : revokedAt.hashCode) +
      (lastViewedAt == null ? 0 : lastViewedAt.hashCode) +
      status.hashCode;

  factory StrengthsShare.fromJson(Map<String, dynamic> json) =>
      _$StrengthsShareFromJson(json);

  Map<String, dynamic> toJson() => _$StrengthsShareToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum StrengthsShareKindEnum {
  @JsonValue(r'guardian')
  guardian(r'guardian'),
  @JsonValue(r'teacher')
  teacher(r'teacher'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const StrengthsShareKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum StrengthsShareStatusEnum {
  @JsonValue(r'open')
  open(r'open'),
  @JsonValue(r'revoked')
  revoked(r'revoked'),
  @JsonValue(r'expired')
  expired(r'expired'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const StrengthsShareStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
