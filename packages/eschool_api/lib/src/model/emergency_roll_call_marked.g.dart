// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'emergency_roll_call_marked.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

EmergencyRollCallMarked _$EmergencyRollCallMarkedFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'EmergencyRollCallMarked',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'alert_id',
        'status',
        'learner_ids',
        'already_released',
      ],
    );
    final val = EmergencyRollCallMarked(
      alertId: $checkedConvert('alert_id', (v) => v as String),
      status: $checkedConvert(
        'status',
        (v) => $enumDecode(
          _$EmergencyRollCallMarkedStatusEnumEnumMap,
          v,
          unknownValue: EmergencyRollCallMarkedStatusEnum.unknownDefaultOpenApi,
        ),
      ),
      learnerIds: $checkedConvert(
        'learner_ids',
        (v) => (v as List<dynamic>).map((e) => (e as num).toInt()).toList(),
      ),
      alreadyReleased: $checkedConvert(
        'already_released',
        (v) => (v as List<dynamic>).map((e) => (e as num).toInt()).toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'alertId': 'alert_id',
    'learnerIds': 'learner_ids',
    'alreadyReleased': 'already_released',
  },
);

Map<String, dynamic> _$EmergencyRollCallMarkedToJson(
  EmergencyRollCallMarked instance,
) => <String, dynamic>{
  'alert_id': instance.alertId,
  'status': _$EmergencyRollCallMarkedStatusEnumEnumMap[instance.status]!,
  'learner_ids': instance.learnerIds,
  'already_released': instance.alreadyReleased,
};

const _$EmergencyRollCallMarkedStatusEnumEnumMap = {
  EmergencyRollCallMarkedStatusEnum.accounted: 'accounted',
  EmergencyRollCallMarkedStatusEnum.unaccounted: 'unaccounted',
  EmergencyRollCallMarkedStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
