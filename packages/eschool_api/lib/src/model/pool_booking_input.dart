//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'pool_booking_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PoolBookingInput {
  /// Returns a new [PoolBookingInput] instance.
  PoolBookingInput({
    required this.operationId,

    required this.poolMemberId,

    required this.activitySessionId,

    required this.kind,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'pool_member_id', required: true, includeIfNull: false)
  final int poolMemberId;

  // minimum: 1
  @JsonKey(name: r'activity_session_id', required: true, includeIfNull: false)
  final int activitySessionId;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue: PoolBookingInputKindEnum.unknownDefaultOpenApi,
  )
  final PoolBookingInputKindEnum kind;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PoolBookingInput &&
          other.operationId == operationId &&
          other.poolMemberId == poolMemberId &&
          other.activitySessionId == activitySessionId &&
          other.kind == kind;

  @override
  int get hashCode =>
      operationId.hashCode +
      poolMemberId.hashCode +
      activitySessionId.hashCode +
      kind.hashCode;

  factory PoolBookingInput.fromJson(Map<String, dynamic> json) =>
      _$PoolBookingInputFromJson(json);

  Map<String, dynamic> toJson() => _$PoolBookingInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum PoolBookingInputKindEnum {
  @JsonValue(r'career_talk')
  careerTalk(r'career_talk'),
  @JsonValue(r'job_shadow')
  jobShadow(r'job_shadow'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const PoolBookingInputKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
