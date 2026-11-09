// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'emergency_alert_prepare.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

EmergencyAlertPrepare _$EmergencyAlertPrepareFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('EmergencyAlertPrepare', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['operation_id', 'kind', 'audience', 'drill'],
  );
  final val = EmergencyAlertPrepare(
    operationId: $checkedConvert('operation_id', (v) => v as String),
    kind: $checkedConvert(
      'kind',
      (v) => $enumDecode(
        _$EmergencyAlertPrepareKindEnumEnumMap,
        v,
        unknownValue: EmergencyAlertPrepareKindEnum.unknownDefaultOpenApi,
      ),
    ),
    audience: $checkedConvert(
      'audience',
      (v) => $enumDecode(
        _$EmergencyAlertPrepareAudienceEnumEnumMap,
        v,
        unknownValue: EmergencyAlertPrepareAudienceEnum.unknownDefaultOpenApi,
      ),
    ),
    drill: $checkedConvert('drill', (v) => v as bool),
    drillId: $checkedConvert('drill_id', (v) => v as String?),
    title: $checkedConvert('title', (v) => v as String?),
    message: $checkedConvert('message', (v) => v as String?),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id', 'drillId': 'drill_id'});

Map<String, dynamic> _$EmergencyAlertPrepareToJson(
  EmergencyAlertPrepare instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'kind': _$EmergencyAlertPrepareKindEnumEnumMap[instance.kind]!,
  'audience': _$EmergencyAlertPrepareAudienceEnumEnumMap[instance.audience]!,
  'drill': instance.drill,
  'drill_id': ?instance.drillId,
  'title': ?instance.title,
  'message': ?instance.message,
};

const _$EmergencyAlertPrepareKindEnumEnumMap = {
  EmergencyAlertPrepareKindEnum.lockdown: 'lockdown',
  EmergencyAlertPrepareKindEnum.evacuation: 'evacuation',
  EmergencyAlertPrepareKindEnum.shelter: 'shelter',
  EmergencyAlertPrepareKindEnum.critical: 'critical',
  EmergencyAlertPrepareKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$EmergencyAlertPrepareAudienceEnumEnumMap = {
  EmergencyAlertPrepareAudienceEnum.everyone: 'everyone',
  EmergencyAlertPrepareAudienceEnum.staff: 'staff',
  EmergencyAlertPrepareAudienceEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
