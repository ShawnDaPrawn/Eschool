//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'intervention_update.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class InterventionUpdate {
  /// Returns a new [InterventionUpdate] instance.
  InterventionUpdate({
    required this.operationId,

    required this.expectedRevision,

    required this.action,

    required this.reviewOn,

    required this.state,

    this.outcome,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'expected_revision', required: true, includeIfNull: false)
  final int expectedRevision;

  @JsonKey(name: r'action', required: true, includeIfNull: false)
  final String action;

  @JsonKey(name: r'review_on', required: true, includeIfNull: false)
  final String reviewOn;

  @JsonKey(
    name: r'state',
    required: true,
    includeIfNull: false,
    unknownEnumValue: InterventionUpdateStateEnum.unknownDefaultOpenApi,
  )
  final InterventionUpdateStateEnum state;

  @JsonKey(name: r'outcome', required: false, includeIfNull: false)
  final String? outcome;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is InterventionUpdate &&
          other.operationId == operationId &&
          other.expectedRevision == expectedRevision &&
          other.action == action &&
          other.reviewOn == reviewOn &&
          other.state == state &&
          other.outcome == outcome;

  @override
  int get hashCode =>
      operationId.hashCode +
      expectedRevision.hashCode +
      action.hashCode +
      reviewOn.hashCode +
      state.hashCode +
      (outcome == null ? 0 : outcome.hashCode);

  factory InterventionUpdate.fromJson(Map<String, dynamic> json) =>
      _$InterventionUpdateFromJson(json);

  Map<String, dynamic> toJson() => _$InterventionUpdateToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum InterventionUpdateStateEnum {
  @JsonValue(r'active')
  active(r'active'),
  @JsonValue(r'completed')
  completed(r'completed'),
  @JsonValue(r'cancelled')
  cancelled(r'cancelled'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const InterventionUpdateStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
