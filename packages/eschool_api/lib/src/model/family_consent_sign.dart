//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'family_consent_sign.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FamilyConsentSign {
  /// Returns a new [FamilyConsentSign] instance.
  FamilyConsentSign({
    required this.operationId,

    required this.revision,

    required this.fingerprint,

    required this.decision,

    required this.acknowledged,

    required this.signedName,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 0
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'fingerprint', required: true, includeIfNull: false)
  final String fingerprint;

  @JsonKey(name: r'decision', required: true, includeIfNull: false)
  final String decision;

  /// Must be true to attest that the exact consent wording was read.
  @JsonKey(name: r'acknowledged', required: true, includeIfNull: false)
  final bool acknowledged;

  @JsonKey(name: r'signed_name', required: true, includeIfNull: false)
  final String signedName;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FamilyConsentSign &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.fingerprint == fingerprint &&
          other.decision == decision &&
          other.acknowledged == acknowledged &&
          other.signedName == signedName;

  @override
  int get hashCode =>
      operationId.hashCode +
      revision.hashCode +
      fingerprint.hashCode +
      decision.hashCode +
      acknowledged.hashCode +
      signedName.hashCode;

  factory FamilyConsentSign.fromJson(Map<String, dynamic> json) =>
      _$FamilyConsentSignFromJson(json);

  Map<String, dynamic> toJson() => _$FamilyConsentSignToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
