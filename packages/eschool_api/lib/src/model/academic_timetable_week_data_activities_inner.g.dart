// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_timetable_week_data_activities_inner.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicTimetableWeekDataActivitiesInner
_$AcademicTimetableWeekDataActivitiesInnerFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'AcademicTimetableWeekDataActivitiesInner',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'date',
            'starts_at',
            'ends_at',
            'name',
            'group_name',
            'venue',
            'status',
          ],
        );
        final val = AcademicTimetableWeekDataActivitiesInner(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          date: $checkedConvert('date', (v) => v as String),
          startsAt: $checkedConvert('starts_at', (v) => v as String?),
          endsAt: $checkedConvert('ends_at', (v) => v as String?),
          name: $checkedConvert('name', (v) => v as String),
          groupName: $checkedConvert('group_name', (v) => v as String),
          venue: $checkedConvert('venue', (v) => v as String),
          status: $checkedConvert(
            'status',
            (v) => $enumDecode(
              _$AcademicTimetableWeekDataActivitiesInnerStatusEnumEnumMap,
              v,
              unknownValue: AcademicTimetableWeekDataActivitiesInnerStatusEnum
                  .unknownDefaultOpenApi,
            ),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'startsAt': 'starts_at',
        'endsAt': 'ends_at',
        'groupName': 'group_name',
      },
    );

Map<String, dynamic> _$AcademicTimetableWeekDataActivitiesInnerToJson(
  AcademicTimetableWeekDataActivitiesInner instance,
) => <String, dynamic>{
  'id': instance.id,
  'date': instance.date,
  'starts_at': instance.startsAt,
  'ends_at': instance.endsAt,
  'name': instance.name,
  'group_name': instance.groupName,
  'venue': instance.venue,
  'status':
      _$AcademicTimetableWeekDataActivitiesInnerStatusEnumEnumMap[instance
          .status]!,
};

const _$AcademicTimetableWeekDataActivitiesInnerStatusEnumEnumMap = {
  AcademicTimetableWeekDataActivitiesInnerStatusEnum.scheduled: 'scheduled',
  AcademicTimetableWeekDataActivitiesInnerStatusEnum.completed: 'completed',
  AcademicTimetableWeekDataActivitiesInnerStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
