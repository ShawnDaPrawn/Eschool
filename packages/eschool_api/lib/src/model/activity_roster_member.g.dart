// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'activity_roster_member.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ActivityRosterMember _$ActivityRosterMemberFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'ActivityRosterMember',
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
        'learner_name',
      ],
    );
    final val = ActivityRosterMember(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      activityGroupId: $checkedConvert(
        'activity_group_id',
        (v) => (v as num).toInt(),
      ),
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      role: $checkedConvert(
        'role',
        (v) => $enumDecode(
          _$ActivityRosterMemberRoleEnumEnumMap,
          v,
          unknownValue: ActivityRosterMemberRoleEnum.unknownDefaultOpenApi,
        ),
      ),
      active: $checkedConvert('active', (v) => v as bool),
      startsOn: $checkedConvert('starts_on', (v) => v as String),
      endsOn: $checkedConvert('ends_on', (v) => v as String),
      reason: $checkedConvert('reason', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      learnerName: $checkedConvert('learner_name', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {
    'activityGroupId': 'activity_group_id',
    'learnerId': 'learner_id',
    'startsOn': 'starts_on',
    'endsOn': 'ends_on',
    'learnerName': 'learner_name',
  },
);

Map<String, dynamic> _$ActivityRosterMemberToJson(
  ActivityRosterMember instance,
) => <String, dynamic>{
  'id': instance.id,
  'activity_group_id': instance.activityGroupId,
  'learner_id': instance.learnerId,
  'role': _$ActivityRosterMemberRoleEnumEnumMap[instance.role]!,
  'active': instance.active,
  'starts_on': instance.startsOn,
  'ends_on': instance.endsOn,
  'reason': instance.reason,
  'revision': instance.revision,
  'learner_name': instance.learnerName,
};

const _$ActivityRosterMemberRoleEnumEnumMap = {
  ActivityRosterMemberRoleEnum.member: 'member',
  ActivityRosterMemberRoleEnum.captain: 'captain',
  ActivityRosterMemberRoleEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
