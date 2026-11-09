//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/open_badge_credential_proof.dart';
import 'package:eschool_api/src/model/open_badge_credential_issuer.dart';
import 'package:json_annotation/json_annotation.dart';

part 'open_badge_credential.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class OpenBadgeCredential {
  /// Returns a new [OpenBadgeCredential] instance.
  OpenBadgeCredential({
    required this.atContext,

    required this.id,

    required this.type,

    required this.issuer,

    required this.validFrom,

    required this.name,

    this.awardedDate,

    required this.credentialSubject,

    required this.credentialStatus,

    required this.proof,
  });

  @JsonKey(name: r'@context', required: true, includeIfNull: false)
  final List<String> atContext;

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  @JsonKey(name: r'type', required: true, includeIfNull: false)
  final List<String> type;

  @JsonKey(name: r'issuer', required: true, includeIfNull: false)
  final OpenBadgeCredentialIssuer issuer;

  @JsonKey(name: r'validFrom', required: true, includeIfNull: false)
  final String validFrom;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'awardedDate', required: false, includeIfNull: false)
  final String? awardedDate;

  @JsonKey(name: r'credentialSubject', required: true, includeIfNull: false)
  final Map<String, Object> credentialSubject;

  @JsonKey(name: r'credentialStatus', required: true, includeIfNull: false)
  final Map<String, Object> credentialStatus;

  @JsonKey(name: r'proof', required: true, includeIfNull: false)
  final OpenBadgeCredentialProof proof;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is OpenBadgeCredential &&
          other.atContext == atContext &&
          other.id == id &&
          other.type == type &&
          other.issuer == issuer &&
          other.validFrom == validFrom &&
          other.name == name &&
          other.awardedDate == awardedDate &&
          other.credentialSubject == credentialSubject &&
          other.credentialStatus == credentialStatus &&
          other.proof == proof;

  @override
  int get hashCode =>
      atContext.hashCode +
      id.hashCode +
      type.hashCode +
      issuer.hashCode +
      validFrom.hashCode +
      name.hashCode +
      awardedDate.hashCode +
      credentialSubject.hashCode +
      credentialStatus.hashCode +
      proof.hashCode;

  factory OpenBadgeCredential.fromJson(Map<String, dynamic> json) =>
      _$OpenBadgeCredentialFromJson(json);

  Map<String, dynamic> toJson() => _$OpenBadgeCredentialToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
