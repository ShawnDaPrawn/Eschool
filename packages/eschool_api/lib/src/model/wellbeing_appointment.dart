//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'wellbeing_appointment.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class WellbeingAppointment {
  /// Returns a new [WellbeingAppointment] instance.
  WellbeingAppointment({
    required this.id,

    required this.supportCaseId,

    required this.professionalUserId,

    required this.startsAt,

    required this.endsAt,

    required this.location,

    required this.notes,

    required this.status,

    required this.revision,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'support_case_id', required: true, includeIfNull: false)
  final int supportCaseId;

  // minimum: 1
  @JsonKey(name: r'professional_user_id', required: true, includeIfNull: false)
  final int professionalUserId;

  @JsonKey(name: r'starts_at', required: true, includeIfNull: false)
  final DateTime startsAt;

  @JsonKey(name: r'ends_at', required: true, includeIfNull: false)
  final DateTime endsAt;

  @JsonKey(name: r'location', required: true, includeIfNull: false)
  final String location;

  @JsonKey(name: r'notes', required: true, includeIfNull: false)
  final String notes;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: WellbeingAppointmentStatusEnum.unknownDefaultOpenApi,
  )
  final WellbeingAppointmentStatusEnum status;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is WellbeingAppointment &&
          other.id == id &&
          other.supportCaseId == supportCaseId &&
          other.professionalUserId == professionalUserId &&
          other.startsAt == startsAt &&
          other.endsAt == endsAt &&
          other.location == location &&
          other.notes == notes &&
          other.status == status &&
          other.revision == revision;

  @override
  int get hashCode =>
      id.hashCode +
      supportCaseId.hashCode +
      professionalUserId.hashCode +
      startsAt.hashCode +
      endsAt.hashCode +
      location.hashCode +
      notes.hashCode +
      status.hashCode +
      revision.hashCode;

  factory WellbeingAppointment.fromJson(Map<String, dynamic> json) =>
      _$WellbeingAppointmentFromJson(json);

  Map<String, dynamic> toJson() => _$WellbeingAppointmentToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum WellbeingAppointmentStatusEnum {
  @JsonValue(r'scheduled')
  scheduled(r'scheduled'),
  @JsonValue(r'completed')
  completed(r'completed'),
  @JsonValue(r'cancelled')
  cancelled(r'cancelled'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const WellbeingAppointmentStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
