// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'emergency_roll_call_mark.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

EmergencyRollCallMark _$EmergencyRollCallMarkFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'EmergencyRollCallMark',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['operation_id', 'learner_ids', 'status'],
    );
    final val = EmergencyRollCallMark(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      learnerIds: $checkedConvert(
        'learner_ids',
        (v) => (v as List<dynamic>).map((e) => (e as num).toInt()).toList(),
      ),
      status: $checkedConvert(
        'status',
        (v) => $enumDecode(
          _$EmergencyRollCallMarkStatusEnumEnumMap,
          v,
          unknownValue: EmergencyRollCallMarkStatusEnum.unknownDefaultOpenApi,
        ),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'learnerIds': 'learner_ids',
  },
);

Map<String, dynamic> _$EmergencyRollCallMarkToJson(
  EmergencyRollCallMark instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'learner_ids': instance.learnerIds,
  'status': _$EmergencyRollCallMarkStatusEnumEnumMap[instance.status]!,
};

const _$EmergencyRollCallMarkStatusEnumEnumMap = {
  EmergencyRollCallMarkStatusEnum.accounted: 'accounted',
  EmergencyRollCallMarkStatusEnum.unaccounted: 'unaccounted',
  EmergencyRollCallMarkStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
