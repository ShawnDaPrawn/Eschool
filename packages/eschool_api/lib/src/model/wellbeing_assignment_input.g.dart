// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wellbeing_assignment_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WellbeingAssignmentInput _$WellbeingAssignmentInputFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'WellbeingAssignmentInput',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'operation_id',
        'revision',
        'user_id',
        'starts_on',
        'ends_on',
        'active',
        'reason',
      ],
    );
    final val = WellbeingAssignmentInput(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      userId: $checkedConvert('user_id', (v) => (v as num).toInt()),
      startsOn: $checkedConvert('starts_on', (v) => v as String),
      endsOn: $checkedConvert('ends_on', (v) => v as String),
      active: $checkedConvert('active', (v) => v as bool),
      reason: $checkedConvert('reason', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'userId': 'user_id',
    'startsOn': 'starts_on',
    'endsOn': 'ends_on',
  },
);

Map<String, dynamic> _$WellbeingAssignmentInputToJson(
  WellbeingAssignmentInput instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'revision': instance.revision,
  'user_id': instance.userId,
  'starts_on': instance.startsOn,
  'ends_on': instance.endsOn,
  'active': instance.active,
  'reason': instance.reason,
};
