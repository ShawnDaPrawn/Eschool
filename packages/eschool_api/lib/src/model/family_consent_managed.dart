//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/family_consent_managed_recipient.dart';
import 'package:json_annotation/json_annotation.dart';

part 'family_consent_managed.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FamilyConsentManaged {
  /// Returns a new [FamilyConsentManaged] instance.
  FamilyConsentManaged({
    required this.id,

    required this.title,

    required this.activityGroupId,

    required this.sessionYearId,

    required this.status,

    required this.eventStartsAt,

    required this.eventEndsAt,

    required this.respondBy,

    required this.wording,

    required this.fingerprint,

    required this.revision,

    required this.recipients,

    required this.nextPage,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  // minimum: 1
  @JsonKey(name: r'activity_group_id', required: true, includeIfNull: false)
  final int activityGroupId;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(name: r'status', required: true, includeIfNull: false)
  final String status;

  @JsonKey(name: r'event_starts_at', required: true, includeIfNull: false)
  final DateTime eventStartsAt;

  @JsonKey(name: r'event_ends_at', required: true, includeIfNull: false)
  final DateTime eventEndsAt;

  @JsonKey(name: r'respond_by', required: true, includeIfNull: false)
  final DateTime respondBy;

  @JsonKey(name: r'wording', required: true, includeIfNull: false)
  final String wording;

  @JsonKey(name: r'fingerprint', required: true, includeIfNull: false)
  final String fingerprint;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'recipients', required: true, includeIfNull: false)
  final List<FamilyConsentManagedRecipient> recipients;

  // minimum: 1
  @JsonKey(name: r'next_page', required: true, includeIfNull: true)
  final int? nextPage;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FamilyConsentManaged &&
          other.id == id &&
          other.title == title &&
          other.activityGroupId == activityGroupId &&
          other.sessionYearId == sessionYearId &&
          other.status == status &&
          other.eventStartsAt == eventStartsAt &&
          other.eventEndsAt == eventEndsAt &&
          other.respondBy == respondBy &&
          other.wording == wording &&
          other.fingerprint == fingerprint &&
          other.revision == revision &&
          other.recipients == recipients &&
          other.nextPage == nextPage;

  @override
  int get hashCode =>
      id.hashCode +
      title.hashCode +
      activityGroupId.hashCode +
      sessionYearId.hashCode +
      status.hashCode +
      eventStartsAt.hashCode +
      eventEndsAt.hashCode +
      respondBy.hashCode +
      wording.hashCode +
      fingerprint.hashCode +
      revision.hashCode +
      recipients.hashCode +
      (nextPage == null ? 0 : nextPage.hashCode);

  factory FamilyConsentManaged.fromJson(Map<String, dynamic> json) =>
      _$FamilyConsentManagedFromJson(json);

  Map<String, dynamic> toJson() => _$FamilyConsentManagedToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
