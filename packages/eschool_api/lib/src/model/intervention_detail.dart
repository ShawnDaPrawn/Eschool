//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'intervention_detail.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class InterventionDetail {
  /// Returns a new [InterventionDetail] instance.
  InterventionDetail({
    required this.id,

    required this.learnerId,

    required this.sessionYearId,

    required this.gradeLevelId,

    required this.ownerAuthority,

    required this.ownerId,

    required this.startedOn,

    required this.reviewOn,

    required this.state,

    required this.revision,

    required this.action,

    required this.outcome,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  // minimum: 1
  @JsonKey(name: r'grade_level_id', required: true, includeIfNull: false)
  final int gradeLevelId;

  @JsonKey(
    name: r'owner_authority',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        InterventionDetailOwnerAuthorityEnum.unknownDefaultOpenApi,
  )
  final InterventionDetailOwnerAuthorityEnum ownerAuthority;

  // minimum: 1
  @JsonKey(name: r'owner_id', required: true, includeIfNull: false)
  final int ownerId;

  @JsonKey(name: r'started_on', required: true, includeIfNull: false)
  final String startedOn;

  @JsonKey(name: r'review_on', required: true, includeIfNull: false)
  final String reviewOn;

  @JsonKey(
    name: r'state',
    required: true,
    includeIfNull: false,
    unknownEnumValue: InterventionDetailStateEnum.unknownDefaultOpenApi,
  )
  final InterventionDetailStateEnum state;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'action', required: true, includeIfNull: false)
  final String action;

  @JsonKey(name: r'outcome', required: true, includeIfNull: true)
  final String? outcome;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is InterventionDetail &&
          other.id == id &&
          other.learnerId == learnerId &&
          other.sessionYearId == sessionYearId &&
          other.gradeLevelId == gradeLevelId &&
          other.ownerAuthority == ownerAuthority &&
          other.ownerId == ownerId &&
          other.startedOn == startedOn &&
          other.reviewOn == reviewOn &&
          other.state == state &&
          other.revision == revision &&
          other.action == action &&
          other.outcome == outcome;

  @override
  int get hashCode =>
      id.hashCode +
      learnerId.hashCode +
      sessionYearId.hashCode +
      gradeLevelId.hashCode +
      ownerAuthority.hashCode +
      ownerId.hashCode +
      startedOn.hashCode +
      reviewOn.hashCode +
      state.hashCode +
      revision.hashCode +
      action.hashCode +
      (outcome == null ? 0 : outcome.hashCode);

  factory InterventionDetail.fromJson(Map<String, dynamic> json) =>
      _$InterventionDetailFromJson(json);

  Map<String, dynamic> toJson() => _$InterventionDetailToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum InterventionDetailOwnerAuthorityEnum {
  @JsonValue(r'professional')
  professional(r'professional'),
  @JsonValue(r'academic')
  academic(r'academic'),
  @JsonValue(r'leadership')
  leadership(r'leadership'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const InterventionDetailOwnerAuthorityEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum InterventionDetailStateEnum {
  @JsonValue(r'active')
  active(r'active'),
  @JsonValue(r'completed')
  completed(r'completed'),
  @JsonValue(r'cancelled')
  cancelled(r'cancelled'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const InterventionDetailStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
