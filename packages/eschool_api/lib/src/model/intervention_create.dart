//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'intervention_create.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class InterventionCreate {
  /// Returns a new [InterventionCreate] instance.
  InterventionCreate({
    required this.operationId,

    required this.sessionYearId,

    required this.ownerId,

    required this.action,

    required this.startedOn,

    required this.reviewOn,

    this.ownerAuthority,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  // minimum: 1
  @JsonKey(name: r'owner_id', required: true, includeIfNull: false)
  final int ownerId;

  @JsonKey(name: r'action', required: true, includeIfNull: false)
  final String action;

  @JsonKey(name: r'started_on', required: true, includeIfNull: false)
  final String startedOn;

  @JsonKey(name: r'review_on', required: true, includeIfNull: false)
  final String reviewOn;

  @JsonKey(
    name: r'owner_authority',
    required: false,
    includeIfNull: false,
    unknownEnumValue:
        InterventionCreateOwnerAuthorityEnum.unknownDefaultOpenApi,
  )
  final InterventionCreateOwnerAuthorityEnum? ownerAuthority;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is InterventionCreate &&
          other.operationId == operationId &&
          other.sessionYearId == sessionYearId &&
          other.ownerId == ownerId &&
          other.action == action &&
          other.startedOn == startedOn &&
          other.reviewOn == reviewOn &&
          other.ownerAuthority == ownerAuthority;

  @override
  int get hashCode =>
      operationId.hashCode +
      sessionYearId.hashCode +
      ownerId.hashCode +
      action.hashCode +
      startedOn.hashCode +
      reviewOn.hashCode +
      ownerAuthority.hashCode;

  factory InterventionCreate.fromJson(Map<String, dynamic> json) =>
      _$InterventionCreateFromJson(json);

  Map<String, dynamic> toJson() => _$InterventionCreateToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum InterventionCreateOwnerAuthorityEnum {
  @JsonValue(r'professional')
  professional(r'professional'),
  @JsonValue(r'academic')
  academic(r'academic'),
  @JsonValue(r'leadership')
  leadership(r'leadership'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const InterventionCreateOwnerAuthorityEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
