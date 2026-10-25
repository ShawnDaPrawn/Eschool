//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'family_consent_detail.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FamilyConsentDetail {
  /// Returns a new [FamilyConsentDetail] instance.
  FamilyConsentDetail({
    required this.id,

    required this.title,

    required this.status,

    required this.eventStartsAt,

    required this.eventEndsAt,

    required this.respondBy,

    required this.learnerId,

    required this.wording,

    required this.fingerprint,

    required this.revision,

    required this.decision,

    required this.signedAt,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(name: r'status', required: true, includeIfNull: false)
  final String status;

  @JsonKey(name: r'event_starts_at', required: true, includeIfNull: false)
  final DateTime eventStartsAt;

  @JsonKey(name: r'event_ends_at', required: true, includeIfNull: false)
  final DateTime eventEndsAt;

  @JsonKey(name: r'respond_by', required: true, includeIfNull: false)
  final DateTime respondBy;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'wording', required: true, includeIfNull: false)
  final String wording;

  @JsonKey(name: r'fingerprint', required: true, includeIfNull: false)
  final String fingerprint;

  // minimum: 0
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'decision', required: true, includeIfNull: true)
  final String? decision;

  @JsonKey(name: r'signed_at', required: true, includeIfNull: true)
  final DateTime? signedAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FamilyConsentDetail &&
          other.id == id &&
          other.title == title &&
          other.status == status &&
          other.eventStartsAt == eventStartsAt &&
          other.eventEndsAt == eventEndsAt &&
          other.respondBy == respondBy &&
          other.learnerId == learnerId &&
          other.wording == wording &&
          other.fingerprint == fingerprint &&
          other.revision == revision &&
          other.decision == decision &&
          other.signedAt == signedAt;

  @override
  int get hashCode =>
      id.hashCode +
      title.hashCode +
      status.hashCode +
      eventStartsAt.hashCode +
      eventEndsAt.hashCode +
      respondBy.hashCode +
      learnerId.hashCode +
      wording.hashCode +
      fingerprint.hashCode +
      revision.hashCode +
      (decision == null ? 0 : decision.hashCode) +
      (signedAt == null ? 0 : signedAt.hashCode);

  factory FamilyConsentDetail.fromJson(Map<String, dynamic> json) =>
      _$FamilyConsentDetailFromJson(json);

  Map<String, dynamic> toJson() => _$FamilyConsentDetailToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
