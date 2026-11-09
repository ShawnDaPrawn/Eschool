// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'emergency_public_alert.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

EmergencyPublicAlert _$EmergencyPublicAlertFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'EmergencyPublicAlert',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'kind',
        'drill',
        'status',
        'title',
        'message',
        'activated_at',
        'all_clear_at',
        'all_clear_message',
      ],
    );
    final val = EmergencyPublicAlert(
      id: $checkedConvert('id', (v) => v as String),
      kind: $checkedConvert(
        'kind',
        (v) => $enumDecode(
          _$EmergencyPublicAlertKindEnumEnumMap,
          v,
          unknownValue: EmergencyPublicAlertKindEnum.unknownDefaultOpenApi,
        ),
      ),
      drill: $checkedConvert('drill', (v) => v as bool),
      status: $checkedConvert(
        'status',
        (v) => $enumDecode(
          _$EmergencyPublicAlertStatusEnumEnumMap,
          v,
          unknownValue: EmergencyPublicAlertStatusEnum.unknownDefaultOpenApi,
        ),
      ),
      title: $checkedConvert('title', (v) => v as String),
      message: $checkedConvert('message', (v) => v as String),
      activatedAt: $checkedConvert(
        'activated_at',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      allClearAt: $checkedConvert(
        'all_clear_at',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      allClearMessage: $checkedConvert(
        'all_clear_message',
        (v) => v as String?,
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'activatedAt': 'activated_at',
    'allClearAt': 'all_clear_at',
    'allClearMessage': 'all_clear_message',
  },
);

Map<String, dynamic> _$EmergencyPublicAlertToJson(
  EmergencyPublicAlert instance,
) => <String, dynamic>{
  'id': instance.id,
  'kind': _$EmergencyPublicAlertKindEnumEnumMap[instance.kind]!,
  'drill': instance.drill,
  'status': _$EmergencyPublicAlertStatusEnumEnumMap[instance.status]!,
  'title': instance.title,
  'message': instance.message,
  'activated_at': instance.activatedAt?.toIso8601String(),
  'all_clear_at': instance.allClearAt?.toIso8601String(),
  'all_clear_message': instance.allClearMessage,
};

const _$EmergencyPublicAlertKindEnumEnumMap = {
  EmergencyPublicAlertKindEnum.lockdown: 'lockdown',
  EmergencyPublicAlertKindEnum.evacuation: 'evacuation',
  EmergencyPublicAlertKindEnum.shelter: 'shelter',
  EmergencyPublicAlertKindEnum.critical: 'critical',
  EmergencyPublicAlertKindEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};

const _$EmergencyPublicAlertStatusEnumEnumMap = {
  EmergencyPublicAlertStatusEnum.active: 'active',
  EmergencyPublicAlertStatusEnum.allClear: 'all_clear',
  EmergencyPublicAlertStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
