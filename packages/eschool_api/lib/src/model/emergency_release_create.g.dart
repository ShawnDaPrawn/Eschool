// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'emergency_release_create.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

EmergencyReleaseCreate _$EmergencyReleaseCreateFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'EmergencyReleaseCreate',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'operation_id',
        'learner_id',
        'released_to',
        'verification',
        'identity_checked',
      ],
    );
    final val = EmergencyReleaseCreate(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      releasedTo: $checkedConvert(
        'released_to',
        (v) => $enumDecode(
          _$EmergencyReleaseCreateReleasedToEnumEnumMap,
          v,
          unknownValue:
              EmergencyReleaseCreateReleasedToEnum.unknownDefaultOpenApi,
        ),
      ),
      guardianUserId: $checkedConvert(
        'guardian_user_id',
        (v) => (v as num?)?.toInt(),
      ),
      gatePassId: $checkedConvert('gate_pass_id', (v) => v as String?),
      collectorName: $checkedConvert('collector_name', (v) => v as String?),
      verification: $checkedConvert(
        'verification',
        (v) => $enumDecode(
          _$EmergencyReleaseCreateVerificationEnumEnumMap,
          v,
          unknownValue:
              EmergencyReleaseCreateVerificationEnum.unknownDefaultOpenApi,
        ),
      ),
      scanId: $checkedConvert('scan_id', (v) => v as String?),
      identityChecked: $checkedConvert(
        'identity_checked',
        (v) => EmergencyReleaseCreateIdentityCheckedEnum.fromJson(v),
      ),
      notes: $checkedConvert('notes', (v) => v as String?),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'learnerId': 'learner_id',
    'releasedTo': 'released_to',
    'guardianUserId': 'guardian_user_id',
    'gatePassId': 'gate_pass_id',
    'collectorName': 'collector_name',
    'scanId': 'scan_id',
    'identityChecked': 'identity_checked',
  },
);

Map<String, dynamic> _$EmergencyReleaseCreateToJson(
  EmergencyReleaseCreate instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'learner_id': instance.learnerId,
  'released_to':
      _$EmergencyReleaseCreateReleasedToEnumEnumMap[instance.releasedTo]!,
  'guardian_user_id': ?instance.guardianUserId,
  'gate_pass_id': ?instance.gatePassId,
  'collector_name': ?instance.collectorName,
  'verification':
      _$EmergencyReleaseCreateVerificationEnumEnumMap[instance.verification]!,
  'scan_id': ?instance.scanId,
  'identity_checked': ?EmergencyReleaseCreateIdentityCheckedEnum.toJsonValue(
    instance.identityChecked,
  ),
  'notes': ?instance.notes,
};

const _$EmergencyReleaseCreateReleasedToEnumEnumMap = {
  EmergencyReleaseCreateReleasedToEnum.guardian: 'guardian',
  EmergencyReleaseCreateReleasedToEnum.collector: 'collector',
  EmergencyReleaseCreateReleasedToEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$EmergencyReleaseCreateVerificationEnumEnumMap = {
  EmergencyReleaseCreateVerificationEnum.cardScan: 'card_scan',
  EmergencyReleaseCreateVerificationEnum.idDocument: 'id_document',
  EmergencyReleaseCreateVerificationEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
