//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'emergency_child_status.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class EmergencyChildStatus {
  /// Returns a new [EmergencyChildStatus] instance.
  EmergencyChildStatus({
    required this.learnerId,

    required this.name,

    required this.status,

    required this.releasedAt,

    required this.releasedTo,

    required this.releasedToName,
  });

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  /// null when there is no current alert.
  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: true,
    unknownEnumValue: EmergencyChildStatusStatusEnum.unknownDefaultOpenApi,
  )
  final EmergencyChildStatusStatusEnum? status;

  @JsonKey(name: r'released_at', required: true, includeIfNull: true)
  final DateTime? releasedAt;

  @JsonKey(
    name: r'released_to',
    required: true,
    includeIfNull: true,
    unknownEnumValue: EmergencyChildStatusReleasedToEnum.unknownDefaultOpenApi,
  )
  final EmergencyChildStatusReleasedToEnum? releasedTo;

  @JsonKey(name: r'released_to_name', required: true, includeIfNull: true)
  final String? releasedToName;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is EmergencyChildStatus &&
          other.learnerId == learnerId &&
          other.name == name &&
          other.status == status &&
          other.releasedAt == releasedAt &&
          other.releasedTo == releasedTo &&
          other.releasedToName == releasedToName;

  @override
  int get hashCode =>
      learnerId.hashCode +
      name.hashCode +
      (status == null ? 0 : status.hashCode) +
      (releasedAt == null ? 0 : releasedAt.hashCode) +
      (releasedTo == null ? 0 : releasedTo.hashCode) +
      (releasedToName == null ? 0 : releasedToName.hashCode);

  factory EmergencyChildStatus.fromJson(Map<String, dynamic> json) =>
      _$EmergencyChildStatusFromJson(json);

  Map<String, dynamic> toJson() => _$EmergencyChildStatusToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

/// null when there is no current alert.
enum EmergencyChildStatusStatusEnum {
  @JsonValue(r'accounted')
  accounted(r'accounted'),
  @JsonValue(r'unaccounted')
  unaccounted(r'unaccounted'),
  @JsonValue(r'released')
  released(r'released'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const EmergencyChildStatusStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum EmergencyChildStatusReleasedToEnum {
  @JsonValue(r'guardian')
  guardian(r'guardian'),
  @JsonValue(r'collector')
  collector(r'collector'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const EmergencyChildStatusReleasedToEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
