//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/secure_offline_context.dart';
import 'package:json_annotation/json_annotation.dart';

part 'secure_offline_envelope.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SecureOfflineEnvelope {
  /// Returns a new [SecureOfflineEnvelope] instance.
  SecureOfflineEnvelope({
    required this.suite,

    required this.context,

    required this.ephemeralPublicKey,

    required this.salt,

    required this.nonce,

    required this.tag,

    required this.ciphertext,
  });

  @JsonKey(name: r'suite', required: true, includeIfNull: false)
  final String suite;

  @JsonKey(name: r'context', required: true, includeIfNull: false)
  final SecureOfflineContext context;

  @JsonKey(name: r'ephemeral_public_key', required: true, includeIfNull: false)
  final String ephemeralPublicKey;

  @JsonKey(name: r'salt', required: true, includeIfNull: false)
  final String salt;

  @JsonKey(name: r'nonce', required: true, includeIfNull: false)
  final String nonce;

  @JsonKey(name: r'tag', required: true, includeIfNull: false)
  final String tag;

  @JsonKey(name: r'ciphertext', required: true, includeIfNull: false)
  final String ciphertext;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SecureOfflineEnvelope &&
          other.suite == suite &&
          other.context == context &&
          other.ephemeralPublicKey == ephemeralPublicKey &&
          other.salt == salt &&
          other.nonce == nonce &&
          other.tag == tag &&
          other.ciphertext == ciphertext;

  @override
  int get hashCode =>
      suite.hashCode +
      context.hashCode +
      ephemeralPublicKey.hashCode +
      salt.hashCode +
      nonce.hashCode +
      tag.hashCode +
      ciphertext.hashCode;

  factory SecureOfflineEnvelope.fromJson(Map<String, dynamic> json) =>
      _$SecureOfflineEnvelopeFromJson(json);

  Map<String, dynamic> toJson() => _$SecureOfflineEnvelopeToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
