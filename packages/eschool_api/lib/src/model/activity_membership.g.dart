// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'activity_membership.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ActivityMembership _$ActivityMembershipFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'ActivityMembership',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'activity_group_id',
            'learner_id',
            'role',
            'active',
            'starts_on',
            'ends_on',
            'reason',
            'revision',
          ],
        );
        final val = ActivityMembership(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          activityGroupId: $checkedConvert(
            'activity_group_id',
            (v) => (v as num).toInt(),
          ),
          learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
          role: $checkedConvert(
            'role',
            (v) => $enumDecode(
              _$ActivityMembershipRoleEnumEnumMap,
              v,
              unknownValue: ActivityMembershipRoleEnum.unknownDefaultOpenApi,
            ),
          ),
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
        'learnerId': 'learner_id',
        'startsOn': 'starts_on',
        'endsOn': 'ends_on',
      },
    );

Map<String, dynamic> _$ActivityMembershipToJson(ActivityMembership instance) =>
    <String, dynamic>{
      'id': instance.id,
      'activity_group_id': instance.activityGroupId,
      'learner_id': instance.learnerId,
      'role': _$ActivityMembershipRoleEnumEnumMap[instance.role]!,
      'active': instance.active,
      'starts_on': instance.startsOn,
      'ends_on': instance.endsOn,
      'reason': instance.reason,
      'revision': instance.revision,
    };

const _$ActivityMembershipRoleEnumEnumMap = {
  ActivityMembershipRoleEnum.member: 'member',
  ActivityMembershipRoleEnum.captain: 'captain',
  ActivityMembershipRoleEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
