// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'strengths_activity.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

StrengthsActivity _$StrengthsActivityFromJson(Map<String, dynamic> json) =>
    $checkedCreate('StrengthsActivity', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const [
          'activity_group_id',
          'name',
          'discipline',
          'season',
          'role',
        ],
      );
      final val = StrengthsActivity(
        activityGroupId: $checkedConvert(
          'activity_group_id',
          (v) => (v as num).toInt(),
        ),
        name: $checkedConvert('name', (v) => v as String),
        discipline: $checkedConvert('discipline', (v) => v as String),
        season: $checkedConvert('season', (v) => v as String),
        role: $checkedConvert(
          'role',
          (v) => $enumDecode(
            _$StrengthsActivityRoleEnumEnumMap,
            v,
            unknownValue: StrengthsActivityRoleEnum.unknownDefaultOpenApi,
          ),
        ),
      );
      return val;
    }, fieldKeyMap: const {'activityGroupId': 'activity_group_id'});

Map<String, dynamic> _$StrengthsActivityToJson(StrengthsActivity instance) =>
    <String, dynamic>{
      'activity_group_id': instance.activityGroupId,
      'name': instance.name,
      'discipline': instance.discipline,
      'season': instance.season,
      'role': _$StrengthsActivityRoleEnumEnumMap[instance.role]!,
    };

const _$StrengthsActivityRoleEnumEnumMap = {
  StrengthsActivityRoleEnum.member: 'member',
  StrengthsActivityRoleEnum.captain: 'captain',
  StrengthsActivityRoleEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
