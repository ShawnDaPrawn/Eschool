// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'fixture_schedule.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

FixtureSchedule _$FixtureScheduleFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'FixtureSchedule',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'operation_id',
            'revision',
            'on_date',
            'venue',
            'host_is_home',
            'status',
          ],
        );
        final val = FixtureSchedule(
          operationId: $checkedConvert('operation_id', (v) => v as String),
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
          onDate: $checkedConvert('on_date', (v) => v as String),
          startsAt: $checkedConvert('starts_at', (v) => v as String?),
          venue: $checkedConvert('venue', (v) => v as String),
          field: $checkedConvert('field', (v) => v as String?),
          hostIsHome: $checkedConvert('host_is_home', (v) => v as bool),
          status: $checkedConvert(
            'status',
            (v) => $enumDecode(
              _$FixtureScheduleStatusEnumEnumMap,
              v,
              unknownValue: FixtureScheduleStatusEnum.unknownDefaultOpenApi,
            ),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'operationId': 'operation_id',
        'onDate': 'on_date',
        'startsAt': 'starts_at',
        'hostIsHome': 'host_is_home',
      },
    );

Map<String, dynamic> _$FixtureScheduleToJson(FixtureSchedule instance) =>
    <String, dynamic>{
      'operation_id': instance.operationId,
      'revision': instance.revision,
      'on_date': instance.onDate,
      'starts_at': ?instance.startsAt,
      'venue': instance.venue,
      'field': ?instance.field,
      'host_is_home': instance.hostIsHome,
      'status': _$FixtureScheduleStatusEnumEnumMap[instance.status]!,
    };

const _$FixtureScheduleStatusEnumEnumMap = {
  FixtureScheduleStatusEnum.scheduled: 'scheduled',
  FixtureScheduleStatusEnum.cancelled: 'cancelled',
  FixtureScheduleStatusEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
