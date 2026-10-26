//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'family_consent_summary.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FamilyConsentSummary {
  /// Returns a new [FamilyConsentSummary] instance.
  FamilyConsentSummary({
    required this.id,

    required this.title,

    required this.activityGroupId,

    required this.sessionYearId,

    required this.status,

    required this.eventStartsAt,

    required this.eventEndsAt,

    required this.respondBy,
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

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FamilyConsentSummary &&
          other.id == id &&
          other.title == title &&
          other.activityGroupId == activityGroupId &&
          other.sessionYearId == sessionYearId &&
          other.status == status &&
          other.eventStartsAt == eventStartsAt &&
          other.eventEndsAt == eventEndsAt &&
          other.respondBy == respondBy;

  @override
  int get hashCode =>
      id.hashCode +
      title.hashCode +
      activityGroupId.hashCode +
      sessionYearId.hashCode +
      status.hashCode +
      eventStartsAt.hashCode +
      eventEndsAt.hashCode +
      respondBy.hashCode;

  factory FamilyConsentSummary.fromJson(Map<String, dynamic> json) =>
      _$FamilyConsentSummaryFromJson(json);

  Map<String, dynamic> toJson() => _$FamilyConsentSummaryToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
