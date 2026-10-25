//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'intervention_version_record.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class InterventionVersionRecord {
  /// Returns a new [InterventionVersionRecord] instance.
  InterventionVersionRecord({
    required this.kind,

    this.action,

    this.outcome,

    this.reviewOn,

    this.state,

    this.previousOwnerId,

    this.ownerId,

    this.reason,
  });

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: InterventionVersionRecordKindEnum.unknownDefaultOpenApi,
  )
  final InterventionVersionRecordKindEnum kind;

  @JsonKey(name: r'action', required: false, includeIfNull: false)
  final String? action;

  @JsonKey(name: r'outcome', required: false, includeIfNull: false)
  final String? outcome;

  @JsonKey(name: r'review_on', required: false, includeIfNull: false)
  final String? reviewOn;

  @JsonKey(
    name: r'state',
    required: false,
    includeIfNull: false,
    unknownEnumValue: InterventionVersionRecordStateEnum.unknownDefaultOpenApi,
  )
  final InterventionVersionRecordStateEnum? state;

  // minimum: 1
  @JsonKey(name: r'previous_owner_id', required: false, includeIfNull: false)
  final int? previousOwnerId;

  // minimum: 1
  @JsonKey(name: r'owner_id', required: false, includeIfNull: false)
  final int? ownerId;

  @JsonKey(name: r'reason', required: false, includeIfNull: false)
  final String? reason;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is InterventionVersionRecord &&
          other.kind == kind &&
          other.action == action &&
          other.outcome == outcome &&
          other.reviewOn == reviewOn &&
          other.state == state &&
          other.previousOwnerId == previousOwnerId &&
          other.ownerId == ownerId &&
          other.reason == reason;

  @override
  int get hashCode =>
      kind.hashCode +
      action.hashCode +
      (outcome == null ? 0 : outcome.hashCode) +
      reviewOn.hashCode +
      state.hashCode +
      previousOwnerId.hashCode +
      ownerId.hashCode +
      reason.hashCode;

  factory InterventionVersionRecord.fromJson(Map<String, dynamic> json) =>
      _$InterventionVersionRecordFromJson(json);

  Map<String, dynamic> toJson() => _$InterventionVersionRecordToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum InterventionVersionRecordKindEnum {
  @JsonValue(r'record')
  record(r'record'),
  @JsonValue(r'owner_change')
  ownerChange(r'owner_change'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const InterventionVersionRecordKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum InterventionVersionRecordStateEnum {
  @JsonValue(r'active')
  active(r'active'),
  @JsonValue(r'completed')
  completed(r'completed'),
  @JsonValue(r'cancelled')
  cancelled(r'cancelled'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const InterventionVersionRecordStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
