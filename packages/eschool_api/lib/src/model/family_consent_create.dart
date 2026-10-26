//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'family_consent_create.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class FamilyConsentCreate {
  /// Returns a new [FamilyConsentCreate] instance.
  FamilyConsentCreate({
    required this.operationId,

    required this.activityGroupId,

    required this.title,

    required this.wording,

    required this.eventStartsAt,

    required this.eventEndsAt,

    required this.respondBy,

    required this.learnerIds,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'activity_group_id', required: true, includeIfNull: false)
  final int activityGroupId;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(name: r'wording', required: true, includeIfNull: false)
  final String wording;

  @JsonKey(name: r'event_starts_at', required: true, includeIfNull: false)
  final DateTime eventStartsAt;

  @JsonKey(name: r'event_ends_at', required: true, includeIfNull: false)
  final DateTime eventEndsAt;

  @JsonKey(name: r'respond_by', required: true, includeIfNull: false)
  final DateTime respondBy;

  @JsonKey(name: r'learner_ids', required: true, includeIfNull: false)
  final Set<int> learnerIds;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is FamilyConsentCreate &&
          other.operationId == operationId &&
          other.activityGroupId == activityGroupId &&
          other.title == title &&
          other.wording == wording &&
          other.eventStartsAt == eventStartsAt &&
          other.eventEndsAt == eventEndsAt &&
          other.respondBy == respondBy &&
          other.learnerIds == learnerIds;

  @override
  int get hashCode =>
      operationId.hashCode +
      activityGroupId.hashCode +
      title.hashCode +
      wording.hashCode +
      eventStartsAt.hashCode +
      eventEndsAt.hashCode +
      respondBy.hashCode +
      learnerIds.hashCode;

  factory FamilyConsentCreate.fromJson(Map<String, dynamic> json) =>
      _$FamilyConsentCreateFromJson(json);

  Map<String, dynamic> toJson() => _$FamilyConsentCreateToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
