// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'foundation_grant_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FoundationGrantInput _$FoundationGrantInputFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'FoundationGrantInput',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'operation_id',
        'device_id',
        'teaching_group_id',
        'minutes',
      ],
    );
    final val = FoundationGrantInput(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      deviceId: $checkedConvert('device_id', (v) => v as String),
      teachingGroupId: $checkedConvert(
        'teaching_group_id',
        (v) => (v as num).toInt(),
      ),
      minutes: $checkedConvert('minutes', (v) => (v as num).toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'deviceId': 'device_id',
    'teachingGroupId': 'teaching_group_id',
  },
);

Map<String, dynamic> _$FoundationGrantInputToJson(
  FoundationGrantInput instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'device_id': instance.deviceId,
  'teaching_group_id': instance.teachingGroupId,
  'minutes': instance.minutes,
};
