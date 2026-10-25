//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'wellbeing_appointment_input.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WellbeingAppointmentInput {
  /// Returns a new [WellbeingAppointmentInput] instance.
  WellbeingAppointmentInput({
    required this.operationId,

    required this.revision,

    required this.professionalUserId,

    required this.startsAt,

    required this.endsAt,

    required this.location,

    required this.notes,

    required this.status,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 0
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  // minimum: 1
  @JsonKey(name: r'professional_user_id', required: true, includeIfNull: false)
  final int professionalUserId;

  @JsonKey(name: r'starts_at', required: true, includeIfNull: false)
  final DateTime startsAt;

  @JsonKey(name: r'ends_at', required: true, includeIfNull: false)
  final DateTime endsAt;

  @JsonKey(name: r'location', required: true, includeIfNull: false)
  final String location;

  @JsonKey(name: r'notes', required: true, includeIfNull: true)
  final String? notes;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: WellbeingAppointmentInputStatusEnum.unknownDefaultOpenApi,
  )
  final WellbeingAppointmentInputStatusEnum status;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WellbeingAppointmentInput &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.professionalUserId == professionalUserId &&
          other.startsAt == startsAt &&
          other.endsAt == endsAt &&
          other.location == location &&
          other.notes == notes &&
          other.status == status;

  @override
  int get hashCode =>
      operationId.hashCode +
      revision.hashCode +
      professionalUserId.hashCode +
      startsAt.hashCode +
      endsAt.hashCode +
      location.hashCode +
      (notes == null ? 0 : notes.hashCode) +
      status.hashCode;

  factory WellbeingAppointmentInput.fromJson(Map<String, dynamic> json) =>
      _$WellbeingAppointmentInputFromJson(json);

  Map<String, dynamic> toJson() => _$WellbeingAppointmentInputToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum WellbeingAppointmentInputStatusEnum {
  @JsonValue(r'scheduled')
  scheduled(r'scheduled'),
  @JsonValue(r'completed')
  completed(r'completed'),
  @JsonValue(r'cancelled')
  cancelled(r'cancelled'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const WellbeingAppointmentInputStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
