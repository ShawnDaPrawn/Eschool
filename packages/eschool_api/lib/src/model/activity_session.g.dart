// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'activity_session.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ActivitySession _$ActivitySessionFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'ActivitySession',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'activity_group_id',
            'on_date',
            'name',
            'venue',
            'status',
            'revision',
          ],
        );
        final val = ActivitySession(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          activityGroupId: $checkedConvert(
            'activity_group_id',
            (v) => (v as num).toInt(),
          ),
          onDate: $checkedConvert('on_date', (v) => v as String),
          name: $checkedConvert('name', (v) => v as String),
          venue: $checkedConvert('venue', (v) => v as String),
          status: $checkedConvert(
            'status',
            (v) => $enumDecode(
              _$ActivitySessionStatusEnumEnumMap,
              v,
              unknownValue: ActivitySessionStatusEnum.unknownDefaultOpenApi,
            ),
          ),
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
        );
        return val;
      },
      fieldKeyMap: const {
        'activityGroupId': 'activity_group_id',
        'onDate': 'on_date',
      },
    );

Map<String, dynamic> _$ActivitySessionToJson(ActivitySession instance) =>
    <String, dynamic>{
      'id': instance.id,
      'activity_group_id': instance.activityGroupId,
      'on_date': instance.onDate,
      'name': instance.name,
      'venue': instance.venue,
      'status': _$ActivitySessionStatusEnumEnumMap[instance.status]!,
      'revision': instance.revision,
    };

const _$ActivitySessionStatusEnumEnumMap = {
  ActivitySessionStatusEnum.scheduled: 'scheduled',
  ActivitySessionStatusEnum.completed: 'completed',
  ActivitySessionStatusEnum.cancelled: 'cancelled',
  ActivitySessionStatusEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
