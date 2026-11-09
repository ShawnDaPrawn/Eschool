//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'emergency_release_record.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class EmergencyReleaseRecord {
  /// Returns a new [EmergencyReleaseRecord] instance.
  EmergencyReleaseRecord({
    required this.id,

    required this.alertId,

    required this.learnerId,

    required this.releasedTo,

    required this.releasedToName,

    required this.verification,

    required this.releasedAt,

    required this.releasedBy,
  });

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  @JsonKey(name: r'alert_id', required: true, includeIfNull: false)
  final String alertId;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(
    name: r'released_to',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        EmergencyReleaseRecordReleasedToEnum.unknownDefaultOpenApi,
  )
  final EmergencyReleaseRecordReleasedToEnum releasedTo;

  @JsonKey(name: r'released_to_name', required: true, includeIfNull: false)
  final String releasedToName;

  @JsonKey(
    name: r'verification',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        EmergencyReleaseRecordVerificationEnum.unknownDefaultOpenApi,
  )
  final EmergencyReleaseRecordVerificationEnum verification;

  @JsonKey(name: r'released_at', required: true, includeIfNull: false)
  final DateTime releasedAt;

  // minimum: 1
  @JsonKey(name: r'released_by', required: true, includeIfNull: false)
  final int releasedBy;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is EmergencyReleaseRecord &&
          other.id == id &&
          other.alertId == alertId &&
          other.learnerId == learnerId &&
          other.releasedTo == releasedTo &&
          other.releasedToName == releasedToName &&
          other.verification == verification &&
          other.releasedAt == releasedAt &&
          other.releasedBy == releasedBy;

  @override
  int get hashCode =>
      id.hashCode +
      alertId.hashCode +
      learnerId.hashCode +
      releasedTo.hashCode +
      releasedToName.hashCode +
      verification.hashCode +
      releasedAt.hashCode +
      releasedBy.hashCode;

  factory EmergencyReleaseRecord.fromJson(Map<String, dynamic> json) =>
      _$EmergencyReleaseRecordFromJson(json);

  Map<String, dynamic> toJson() => _$EmergencyReleaseRecordToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum EmergencyReleaseRecordReleasedToEnum {
  @JsonValue(r'guardian')
  guardian(r'guardian'),
  @JsonValue(r'collector')
  collector(r'collector'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const EmergencyReleaseRecordReleasedToEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum EmergencyReleaseRecordVerificationEnum {
  @JsonValue(r'card_scan')
  cardScan(r'card_scan'),
  @JsonValue(r'id_document')
  idDocument(r'id_document'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const EmergencyReleaseRecordVerificationEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
