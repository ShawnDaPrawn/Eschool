//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'open_badge_credential_proof.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class OpenBadgeCredentialProof {
  /// Returns a new [OpenBadgeCredentialProof] instance.
  OpenBadgeCredentialProof({
    required this.type,

    required this.cryptosuite,

    required this.created,

    required this.verificationMethod,

    required this.proofPurpose,

    required this.proofValue,
  });

  @JsonKey(name: r'type', required: true, includeIfNull: false)
  final String type;

  @JsonKey(name: r'cryptosuite', required: true, includeIfNull: false)
  final String cryptosuite;

  @JsonKey(name: r'created', required: true, includeIfNull: false)
  final String created;

  @JsonKey(name: r'verificationMethod', required: true, includeIfNull: false)
  final String verificationMethod;

  @JsonKey(name: r'proofPurpose', required: true, includeIfNull: false)
  final String proofPurpose;

  @JsonKey(name: r'proofValue', required: true, includeIfNull: false)
  final String proofValue;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is OpenBadgeCredentialProof &&
          other.type == type &&
          other.cryptosuite == cryptosuite &&
          other.created == created &&
          other.verificationMethod == verificationMethod &&
          other.proofPurpose == proofPurpose &&
          other.proofValue == proofValue;

  @override
  int get hashCode =>
      type.hashCode +
      cryptosuite.hashCode +
      created.hashCode +
      verificationMethod.hashCode +
      proofPurpose.hashCode +
      proofValue.hashCode;

  factory OpenBadgeCredentialProof.fromJson(Map<String, dynamic> json) =>
      _$OpenBadgeCredentialProofFromJson(json);

  Map<String, dynamic> toJson() => _$OpenBadgeCredentialProofToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
