// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'activity_group_staff.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ActivityGroupStaff _$ActivityGroupStaffFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'ActivityGroupStaff',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'activity_group_id',
            'user_id',
            'role',
            'cascade',
            'active',
            'starts_on',
            'ends_on',
            'reason',
            'revision',
          ],
        );
        final val = ActivityGroupStaff(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          activityGroupId: $checkedConvert(
            'activity_group_id',
            (v) => (v as num).toInt(),
          ),
          userId: $checkedConvert('user_id', (v) => (v as num).toInt()),
          role: $checkedConvert(
            'role',
            (v) => $enumDecode(
              _$ActivityGroupStaffRoleEnumEnumMap,
              v,
              unknownValue: ActivityGroupStaffRoleEnum.unknownDefaultOpenApi,
            ),
          ),
          cascade: $checkedConvert('cascade', (v) => v as bool),
          active: $checkedConvert('active', (v) => v as bool),
          startsOn: $checkedConvert('starts_on', (v) => v as String),
          endsOn: $checkedConvert('ends_on', (v) => v as String),
          reason: $checkedConvert('reason', (v) => v as String),
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
        );
        return val;
      },
      fieldKeyMap: const {
        'activityGroupId': 'activity_group_id',
        'userId': 'user_id',
        'startsOn': 'starts_on',
        'endsOn': 'ends_on',
      },
    );

Map<String, dynamic> _$ActivityGroupStaffToJson(ActivityGroupStaff instance) =>
    <String, dynamic>{
      'id': instance.id,
      'activity_group_id': instance.activityGroupId,
      'user_id': instance.userId,
      'role': _$ActivityGroupStaffRoleEnumEnumMap[instance.role]!,
      'cascade': instance.cascade,
      'active': instance.active,
      'starts_on': instance.startsOn,
      'ends_on': instance.endsOn,
      'reason': instance.reason,
      'revision': instance.revision,
    };

const _$ActivityGroupStaffRoleEnumEnumMap = {
  ActivityGroupStaffRoleEnum.admin: 'admin',
  ActivityGroupStaffRoleEnum.facilitator: 'facilitator',
  ActivityGroupStaffRoleEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
