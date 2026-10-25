//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/secure_card.dart';
import 'package:json_annotation/json_annotation.dart';

part 'secure_card_credential.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SecureCardCredential {
  /// Returns a new [SecureCardCredential] instance.
  SecureCardCredential({
    required this.card,

    required this.credential,

    required this.expiresAt,

    required this.keyId,

    required this.publicKey,
  });

  @JsonKey(name: r'card', required: true, includeIfNull: false)
  final SecureCard card;

  @JsonKey(name: r'credential', required: true, includeIfNull: false)
  final String credential;

  @JsonKey(name: r'expires_at', required: true, includeIfNull: false)
  final DateTime expiresAt;

  @JsonKey(name: r'key_id', required: true, includeIfNull: false)
  final String keyId;

  @JsonKey(name: r'public_key', required: true, includeIfNull: false)
  final String publicKey;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SecureCardCredential &&
          other.card == card &&
          other.credential == credential &&
          other.expiresAt == expiresAt &&
          other.keyId == keyId &&
          other.publicKey == publicKey;

  @override
  int get hashCode =>
      card.hashCode +
      credential.hashCode +
      expiresAt.hashCode +
      keyId.hashCode +
      publicKey.hashCode;

  factory SecureCardCredential.fromJson(Map<String, dynamic> json) =>
      _$SecureCardCredentialFromJson(json);

  Map<String, dynamic> toJson() => _$SecureCardCredentialToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
