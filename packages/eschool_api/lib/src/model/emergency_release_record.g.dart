// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'emergency_release_record.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

EmergencyReleaseRecord _$EmergencyReleaseRecordFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'EmergencyReleaseRecord',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'alert_id',
        'learner_id',
        'released_to',
        'released_to_name',
        'verification',
        'released_at',
        'released_by',
      ],
    );
    final val = EmergencyReleaseRecord(
      id: $checkedConvert('id', (v) => v as String),
      alertId: $checkedConvert('alert_id', (v) => v as String),
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      releasedTo: $checkedConvert(
        'released_to',
        (v) => $enumDecode(
          _$EmergencyReleaseRecordReleasedToEnumEnumMap,
          v,
          unknownValue:
              EmergencyReleaseRecordReleasedToEnum.unknownDefaultOpenApi,
        ),
      ),
      releasedToName: $checkedConvert('released_to_name', (v) => v as String),
      verification: $checkedConvert(
        'verification',
        (v) => $enumDecode(
          _$EmergencyReleaseRecordVerificationEnumEnumMap,
          v,
          unknownValue:
              EmergencyReleaseRecordVerificationEnum.unknownDefaultOpenApi,
        ),
      ),
      releasedAt: $checkedConvert(
        'released_at',
        (v) => DateTime.parse(v as String),
      ),
      releasedBy: $checkedConvert('released_by', (v) => (v as num).toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'alertId': 'alert_id',
    'learnerId': 'learner_id',
    'releasedTo': 'released_to',
    'releasedToName': 'released_to_name',
    'releasedAt': 'released_at',
    'releasedBy': 'released_by',
  },
);

Map<String, dynamic> _$EmergencyReleaseRecordToJson(
  EmergencyReleaseRecord instance,
) => <String, dynamic>{
  'id': instance.id,
  'alert_id': instance.alertId,
  'learner_id': instance.learnerId,
  'released_to':
      _$EmergencyReleaseRecordReleasedToEnumEnumMap[instance.releasedTo]!,
  'released_to_name': instance.releasedToName,
  'verification':
      _$EmergencyReleaseRecordVerificationEnumEnumMap[instance.verification]!,
  'released_at': instance.releasedAt.toIso8601String(),
  'released_by': instance.releasedBy,
};

const _$EmergencyReleaseRecordReleasedToEnumEnumMap = {
  EmergencyReleaseRecordReleasedToEnum.guardian: 'guardian',
  EmergencyReleaseRecordReleasedToEnum.collector: 'collector',
  EmergencyReleaseRecordReleasedToEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$EmergencyReleaseRecordVerificationEnumEnumMap = {
  EmergencyReleaseRecordVerificationEnum.cardScan: 'card_scan',
  EmergencyReleaseRecordVerificationEnum.idDocument: 'id_document',
  EmergencyReleaseRecordVerificationEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
