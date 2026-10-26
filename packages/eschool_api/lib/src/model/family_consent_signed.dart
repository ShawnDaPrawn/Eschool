//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'family_consent_signed.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FamilyConsentSigned {
  /// Returns a new [FamilyConsentSigned] instance.
  FamilyConsentSigned({
    required this.id,

    required this.requestId,

    required this.learnerId,

    required this.decision,

    required this.revision,

    required this.signedAt,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  @JsonKey(name: r'request_id', required: true, includeIfNull: false)
  final String requestId;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'decision', required: true, includeIfNull: false)
  final String decision;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'signed_at', required: true, includeIfNull: false)
  final DateTime signedAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FamilyConsentSigned &&
          other.id == id &&
          other.requestId == requestId &&
          other.learnerId == learnerId &&
          other.decision == decision &&
          other.revision == revision &&
          other.signedAt == signedAt;

  @override
  int get hashCode =>
      id.hashCode +
      requestId.hashCode +
      learnerId.hashCode +
      decision.hashCode +
      revision.hashCode +
      signedAt.hashCode;

  factory FamilyConsentSigned.fromJson(Map<String, dynamic> json) =>
      _$FamilyConsentSignedFromJson(json);

  Map<String, dynamic> toJson() => _$FamilyConsentSignedToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
