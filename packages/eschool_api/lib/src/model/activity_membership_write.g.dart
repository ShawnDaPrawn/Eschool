// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'activity_membership_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ActivityMembershipWrite _$ActivityMembershipWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ActivityMembershipWrite',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'operation_id',
        'revision',
        'learner_id',
        'role',
        'starts_on',
        'ends_on',
        'reason',
      ],
    );
    final val = ActivityMembershipWrite(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      role: $checkedConvert(
        'role',
        (v) => $enumDecode(
          _$ActivityMembershipWriteRoleEnumEnumMap,
          v,
          unknownValue: ActivityMembershipWriteRoleEnum.unknownDefaultOpenApi,
        ),
      ),
      active: $checkedConvert('active', (v) => v as bool?),
      startsOn: $checkedConvert('starts_on', (v) => v as String),
      endsOn: $checkedConvert('ends_on', (v) => v as String),
      reason: $checkedConvert('reason', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'learnerId': 'learner_id',
    'startsOn': 'starts_on',
    'endsOn': 'ends_on',
  },
);

Map<String, dynamic> _$ActivityMembershipWriteToJson(
  ActivityMembershipWrite instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'revision': instance.revision,
  'learner_id': instance.learnerId,
  'role': _$ActivityMembershipWriteRoleEnumEnumMap[instance.role]!,
  'active': ?instance.active,
  'starts_on': instance.startsOn,
  'ends_on': instance.endsOn,
  'reason': instance.reason,
};

const _$ActivityMembershipWriteRoleEnumEnumMap = {
  ActivityMembershipWriteRoleEnum.member: 'member',
  ActivityMembershipWriteRoleEnum.captain: 'captain',
  ActivityMembershipWriteRoleEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
