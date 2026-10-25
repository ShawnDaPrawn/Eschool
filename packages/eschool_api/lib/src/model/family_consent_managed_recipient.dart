//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'family_consent_managed_recipient.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FamilyConsentManagedRecipient {
  /// Returns a new [FamilyConsentManagedRecipient] instance.
  FamilyConsentManagedRecipient({
    required this.learnerId,

    required this.name,

    required this.current,

    required this.revision,

    required this.decision,

    required this.signedAt,

    required this.effective,
  });

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'current', required: true, includeIfNull: false)
  final bool current;

  // minimum: 0
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'decision', required: true, includeIfNull: true)
  final String? decision;

  @JsonKey(name: r'signed_at', required: true, includeIfNull: true)
  final DateTime? signedAt;

  @JsonKey(name: r'effective', required: true, includeIfNull: false)
  final bool effective;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FamilyConsentManagedRecipient &&
          other.learnerId == learnerId &&
          other.name == name &&
          other.current == current &&
          other.revision == revision &&
          other.decision == decision &&
          other.signedAt == signedAt &&
          other.effective == effective;

  @override
  int get hashCode =>
      learnerId.hashCode +
      name.hashCode +
      current.hashCode +
      revision.hashCode +
      (decision == null ? 0 : decision.hashCode) +
      (signedAt == null ? 0 : signedAt.hashCode) +
      effective.hashCode;

  factory FamilyConsentManagedRecipient.fromJson(Map<String, dynamic> json) =>
      _$FamilyConsentManagedRecipientFromJson(json);

  Map<String, dynamic> toJson() => _$FamilyConsentManagedRecipientToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
