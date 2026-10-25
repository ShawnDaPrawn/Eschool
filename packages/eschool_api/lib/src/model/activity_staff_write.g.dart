// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'activity_staff_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ActivityStaffWrite _$ActivityStaffWriteFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'ActivityStaffWrite',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'operation_id',
            'revision',
            'user_id',
            'role',
            'cascade',
            'starts_on',
            'ends_on',
            'reason',
          ],
        );
        final val = ActivityStaffWrite(
          operationId: $checkedConvert('operation_id', (v) => v as String),
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
          userId: $checkedConvert('user_id', (v) => (v as num).toInt()),
          role: $checkedConvert(
            'role',
            (v) => $enumDecode(
              _$ActivityStaffWriteRoleEnumEnumMap,
              v,
              unknownValue: ActivityStaffWriteRoleEnum.unknownDefaultOpenApi,
            ),
          ),
          cascade: $checkedConvert('cascade', (v) => v as bool),
          active: $checkedConvert('active', (v) => v as bool?),
          startsOn: $checkedConvert('starts_on', (v) => v as String),
          endsOn: $checkedConvert('ends_on', (v) => v as String),
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

Map<String, dynamic> _$ActivityStaffWriteToJson(ActivityStaffWrite instance) =>
    <String, dynamic>{
      'operation_id': instance.operationId,
      'revision': instance.revision,
      'user_id': instance.userId,
      'role': _$ActivityStaffWriteRoleEnumEnumMap[instance.role]!,
      'cascade': instance.cascade,
      'active': ?instance.active,
      'starts_on': instance.startsOn,
      'ends_on': instance.endsOn,
      'reason': instance.reason,
    };

const _$ActivityStaffWriteRoleEnumEnumMap = {
  ActivityStaffWriteRoleEnum.admin: 'admin',
  ActivityStaffWriteRoleEnum.facilitator: 'facilitator',
  ActivityStaffWriteRoleEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
