//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'emergency_release_create.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class EmergencyReleaseCreate {
  /// Returns a new [EmergencyReleaseCreate] instance.
  EmergencyReleaseCreate({
    required this.operationId,

    required this.learnerId,

    required this.releasedTo,

    this.guardianUserId,

    this.gatePassId,

    this.collectorName,

    required this.verification,

    this.scanId,

    required this.identityChecked,

    this.notes,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  @JsonKey(
    name: r'released_to',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        EmergencyReleaseCreateReleasedToEnum.unknownDefaultOpenApi,
  )
  final EmergencyReleaseCreateReleasedToEnum releasedTo;

  /// Required when released_to is guardian.
  // minimum: 1
  @JsonKey(name: r'guardian_user_id', required: false, includeIfNull: false)
  final int? guardianUserId;

  /// An early collection request approved for today; required when released_to is collector.
  @JsonKey(name: r'gate_pass_id', required: false, includeIfNull: false)
  final String? gatePassId;

  /// As on the collector's identity document; must match the request.
  @JsonKey(name: r'collector_name', required: false, includeIfNull: false)
  final String? collectorName;

  @JsonKey(
    name: r'verification',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        EmergencyReleaseCreateVerificationEnum.unknownDefaultOpenApi,
  )
  final EmergencyReleaseCreateVerificationEnum verification;

  /// A guardian card scan at a gate reader in the last two minutes; required for card_scan.
  @JsonKey(name: r'scan_id', required: false, includeIfNull: false)
  final String? scanId;

  @JsonKey(
    name: r'identity_checked',
    required: true,
    includeIfNull: false,
    fromJson: EmergencyReleaseCreateIdentityCheckedEnum.fromJson,
    toJson: EmergencyReleaseCreateIdentityCheckedEnum.toJsonValue,
  )
  final EmergencyReleaseCreateIdentityCheckedEnum identityChecked;

  @JsonKey(name: r'notes', required: false, includeIfNull: false)
  final String? notes;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is EmergencyReleaseCreate &&
          other.operationId == operationId &&
          other.learnerId == learnerId &&
          other.releasedTo == releasedTo &&
          other.guardianUserId == guardianUserId &&
          other.gatePassId == gatePassId &&
          other.collectorName == collectorName &&
          other.verification == verification &&
          other.scanId == scanId &&
          other.identityChecked == identityChecked &&
          other.notes == notes;

  @override
  int get hashCode =>
      operationId.hashCode +
      learnerId.hashCode +
      releasedTo.hashCode +
      (guardianUserId == null ? 0 : guardianUserId.hashCode) +
      (gatePassId == null ? 0 : gatePassId.hashCode) +
      (collectorName == null ? 0 : collectorName.hashCode) +
      verification.hashCode +
      (scanId == null ? 0 : scanId.hashCode) +
      identityChecked.hashCode +
      (notes == null ? 0 : notes.hashCode);

  factory EmergencyReleaseCreate.fromJson(Map<String, dynamic> json) =>
      _$EmergencyReleaseCreateFromJson(json);

  Map<String, dynamic> toJson() => _$EmergencyReleaseCreateToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum EmergencyReleaseCreateReleasedToEnum {
  @JsonValue(r'guardian')
  guardian(r'guardian'),
  @JsonValue(r'collector')
  collector(r'collector'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const EmergencyReleaseCreateReleasedToEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum EmergencyReleaseCreateVerificationEnum {
  @JsonValue(r'card_scan')
  cardScan(r'card_scan'),
  @JsonValue(r'id_document')
  idDocument(r'id_document'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const EmergencyReleaseCreateVerificationEnum(this.value);

  final String value;

  @override
  String toString() => value;
}

enum EmergencyReleaseCreateIdentityCheckedEnum {
  true_(true),
  unknownDefaultOpenApi(null);

  const EmergencyReleaseCreateIdentityCheckedEnum(this.value);
  final bool? value;

  static EmergencyReleaseCreateIdentityCheckedEnum fromJson(Object? value) =>
      values.firstWhere(
        (entry) => entry.value != null && entry.value == value,
        orElse: () =>
            EmergencyReleaseCreateIdentityCheckedEnum.unknownDefaultOpenApi,
      );
  static bool? toJsonValue(EmergencyReleaseCreateIdentityCheckedEnum value) =>
      value.value;
}
