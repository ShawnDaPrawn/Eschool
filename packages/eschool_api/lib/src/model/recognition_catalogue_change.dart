//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'recognition_catalogue_change.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RecognitionCatalogueChange {
  /// Returns a new [RecognitionCatalogueChange] instance.
  RecognitionCatalogueChange({
    required this.operationId,

    this.revision,

    this.name,

    this.active,

    this.emblem,

    this.primaryColour,

    this.secondaryColour,

    this.defaultPoints,

    this.minimumPoints,

    this.maximumPoints,

    this.learnerId,

    this.houseId,

    this.sessionYearId,

    this.startsOn,

    this.endsOn,

    this.reason,

    this.parentId,

    this.scopeKind,

    this.gradeLevelId,

    this.subjectId,

    this.activityGroupId,

    this.awardCategoryId,

    this.criteria,

    this.frequency,

    this.opensOn,

    this.closesOn,

    this.status,

    this.userId,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 0
  @JsonKey(name: r'revision', required: false, includeIfNull: false)
  final int? revision;

  @JsonKey(name: r'name', required: false, includeIfNull: false)
  final String? name;

  @JsonKey(name: r'active', required: false, includeIfNull: false)
  final bool? active;

  @JsonKey(name: r'emblem', required: false, includeIfNull: false)
  final String? emblem;

  @JsonKey(name: r'primary_colour', required: false, includeIfNull: false)
  final String? primaryColour;

  @JsonKey(name: r'secondary_colour', required: false, includeIfNull: false)
  final String? secondaryColour;

  @JsonKey(name: r'default_points', required: false, includeIfNull: false)
  final int? defaultPoints;

  @JsonKey(name: r'minimum_points', required: false, includeIfNull: false)
  final int? minimumPoints;

  @JsonKey(name: r'maximum_points', required: false, includeIfNull: false)
  final int? maximumPoints;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: false, includeIfNull: false)
  final int? learnerId;

  // minimum: 1
  @JsonKey(name: r'house_id', required: false, includeIfNull: false)
  final int? houseId;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: false, includeIfNull: false)
  final int? sessionYearId;

  @JsonKey(name: r'starts_on', required: false, includeIfNull: false)
  final String? startsOn;

  @JsonKey(name: r'ends_on', required: false, includeIfNull: false)
  final String? endsOn;

  @JsonKey(name: r'reason', required: false, includeIfNull: false)
  final String? reason;

  // minimum: 1
  @JsonKey(name: r'parent_id', required: false, includeIfNull: false)
  final int? parentId;

  @JsonKey(name: r'scope_kind', required: false, includeIfNull: false)
  final String? scopeKind;

  // minimum: 1
  @JsonKey(name: r'grade_level_id', required: false, includeIfNull: false)
  final int? gradeLevelId;

  // minimum: 1
  @JsonKey(name: r'subject_id', required: false, includeIfNull: false)
  final int? subjectId;

  // minimum: 1
  @JsonKey(name: r'activity_group_id', required: false, includeIfNull: false)
  final int? activityGroupId;

  // minimum: 1
  @JsonKey(name: r'award_category_id', required: false, includeIfNull: false)
  final int? awardCategoryId;

  @JsonKey(name: r'criteria', required: false, includeIfNull: false)
  final String? criteria;

  @JsonKey(name: r'frequency', required: false, includeIfNull: false)
  final String? frequency;

  @JsonKey(name: r'opens_on', required: false, includeIfNull: false)
  final String? opensOn;

  @JsonKey(name: r'closes_on', required: false, includeIfNull: false)
  final String? closesOn;

  @JsonKey(name: r'status', required: false, includeIfNull: false)
  final String? status;

  // minimum: 1
  @JsonKey(name: r'user_id', required: false, includeIfNull: false)
  final int? userId;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RecognitionCatalogueChange &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.name == name &&
          other.active == active &&
          other.emblem == emblem &&
          other.primaryColour == primaryColour &&
          other.secondaryColour == secondaryColour &&
          other.defaultPoints == defaultPoints &&
          other.minimumPoints == minimumPoints &&
          other.maximumPoints == maximumPoints &&
          other.learnerId == learnerId &&
          other.houseId == houseId &&
          other.sessionYearId == sessionYearId &&
          other.startsOn == startsOn &&
          other.endsOn == endsOn &&
          other.reason == reason &&
          other.parentId == parentId &&
          other.scopeKind == scopeKind &&
          other.gradeLevelId == gradeLevelId &&
          other.subjectId == subjectId &&
          other.activityGroupId == activityGroupId &&
          other.awardCategoryId == awardCategoryId &&
          other.criteria == criteria &&
          other.frequency == frequency &&
          other.opensOn == opensOn &&
          other.closesOn == closesOn &&
          other.status == status &&
          other.userId == userId;

  @override
  int get hashCode =>
      operationId.hashCode +
      revision.hashCode +
      name.hashCode +
      active.hashCode +
      emblem.hashCode +
      primaryColour.hashCode +
      secondaryColour.hashCode +
      defaultPoints.hashCode +
      minimumPoints.hashCode +
      maximumPoints.hashCode +
      learnerId.hashCode +
      houseId.hashCode +
      sessionYearId.hashCode +
      startsOn.hashCode +
      (endsOn == null ? 0 : endsOn.hashCode) +
      reason.hashCode +
      (parentId == null ? 0 : parentId.hashCode) +
      scopeKind.hashCode +
      (gradeLevelId == null ? 0 : gradeLevelId.hashCode) +
      (subjectId == null ? 0 : subjectId.hashCode) +
      (activityGroupId == null ? 0 : activityGroupId.hashCode) +
      awardCategoryId.hashCode +
      criteria.hashCode +
      frequency.hashCode +
      opensOn.hashCode +
      closesOn.hashCode +
      status.hashCode +
      userId.hashCode;

  factory RecognitionCatalogueChange.fromJson(Map<String, dynamic> json) =>
      _$RecognitionCatalogueChangeFromJson(json);

  Map<String, dynamic> toJson() => _$RecognitionCatalogueChangeToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
