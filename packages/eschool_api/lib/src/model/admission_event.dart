//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'admission_event.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class AdmissionEvent {
  /// Returns a new [AdmissionEvent] instance.
  AdmissionEvent({
    required this.id,

    required this.name,

    required this.description,

    required this.status,

    required this.startsAt,

    required this.endsAt,
  });

  // minimum: 1
  // maximum: 9000000000000000
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'description', required: true, includeIfNull: true)
  final String? description;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: AdmissionEventStatusEnum.unknownDefaultOpenApi,
  )
  final AdmissionEventStatusEnum status;

  @JsonKey(name: r'starts_at', required: true, includeIfNull: false)
  final DateTime startsAt;

  @JsonKey(name: r'ends_at', required: true, includeIfNull: false)
  final DateTime endsAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is AdmissionEvent &&
          other.id == id &&
          other.name == name &&
          other.description == description &&
          other.status == status &&
          other.startsAt == startsAt &&
          other.endsAt == endsAt;

  @override
  int get hashCode =>
      id.hashCode +
      name.hashCode +
      (description == null ? 0 : description.hashCode) +
      status.hashCode +
      startsAt.hashCode +
      endsAt.hashCode;

  factory AdmissionEvent.fromJson(Map<String, dynamic> json) =>
      _$AdmissionEventFromJson(json);

  Map<String, dynamic> toJson() => _$AdmissionEventToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum AdmissionEventStatusEnum {
  @JsonValue(r'draft')
  draft(r'draft'),
  @JsonValue(r'active')
  active(r'active'),
  @JsonValue(r'paused')
  paused(r'paused'),
  @JsonValue(r'cancelled')
  cancelled(r'cancelled'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const AdmissionEventStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
