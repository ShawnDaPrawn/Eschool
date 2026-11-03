// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'activity_session_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ActivitySessionWrite _$ActivitySessionWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ActivitySessionWrite', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const [
      'operation_id',
      'revision',
      'on_date',
      'name',
      'venue',
      'status',
    ],
  );
  final val = ActivitySessionWrite(
    operationId: $checkedConvert('operation_id', (v) => v as String),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    onDate: $checkedConvert('on_date', (v) => v as String),
    name: $checkedConvert('name', (v) => v as String),
    venue: $checkedConvert('venue', (v) => v as String),
    status: $checkedConvert(
      'status',
      (v) => $enumDecode(
        _$ActivitySessionWriteStatusEnumEnumMap,
        v,
        unknownValue: ActivitySessionWriteStatusEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id', 'onDate': 'on_date'});

Map<String, dynamic> _$ActivitySessionWriteToJson(
  ActivitySessionWrite instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'revision': instance.revision,
  'on_date': instance.onDate,
  'name': instance.name,
  'venue': instance.venue,
  'status': _$ActivitySessionWriteStatusEnumEnumMap[instance.status]!,
};

const _$ActivitySessionWriteStatusEnumEnumMap = {
  ActivitySessionWriteStatusEnum.scheduled: 'scheduled',
  ActivitySessionWriteStatusEnum.completed: 'completed',
  ActivitySessionWriteStatusEnum.cancelled: 'cancelled',
  ActivitySessionWriteStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
