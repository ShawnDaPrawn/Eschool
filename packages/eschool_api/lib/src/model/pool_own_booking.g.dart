// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pool_own_booking.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PoolOwnBooking _$PoolOwnBookingFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'PoolOwnBooking',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'kind',
            'status',
            'revision',
            'activity_session_id',
            'activity_group_id',
            'group_name',
            'session_name',
            'venue',
            'on_date',
            'starts_at',
            'ends_at',
            'attendance_open',
          ],
        );
        final val = PoolOwnBooking(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          kind: $checkedConvert(
            'kind',
            (v) => $enumDecode(
              _$PoolOwnBookingKindEnumEnumMap,
              v,
              unknownValue: PoolOwnBookingKindEnum.unknownDefaultOpenApi,
            ),
          ),
          status: $checkedConvert(
            'status',
            (v) => $enumDecode(
              _$PoolOwnBookingStatusEnumEnumMap,
              v,
              unknownValue: PoolOwnBookingStatusEnum.unknownDefaultOpenApi,
            ),
          ),
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
          activitySessionId: $checkedConvert(
            'activity_session_id',
            (v) => (v as num).toInt(),
          ),
          activityGroupId: $checkedConvert(
            'activity_group_id',
            (v) => (v as num).toInt(),
          ),
          groupName: $checkedConvert('group_name', (v) => v as String),
          sessionName: $checkedConvert('session_name', (v) => v as String),
          venue: $checkedConvert('venue', (v) => v as String),
          onDate: $checkedConvert('on_date', (v) => v as String?),
          startsAt: $checkedConvert('starts_at', (v) => v as String?),
          endsAt: $checkedConvert('ends_at', (v) => v as String?),
          attendanceOpen: $checkedConvert('attendance_open', (v) => v as bool),
        );
        return val;
      },
      fieldKeyMap: const {
        'activitySessionId': 'activity_session_id',
        'activityGroupId': 'activity_group_id',
        'groupName': 'group_name',
        'sessionName': 'session_name',
        'onDate': 'on_date',
        'startsAt': 'starts_at',
        'endsAt': 'ends_at',
        'attendanceOpen': 'attendance_open',
      },
    );

Map<String, dynamic> _$PoolOwnBookingToJson(PoolOwnBooking instance) =>
    <String, dynamic>{
      'id': instance.id,
      'kind': _$PoolOwnBookingKindEnumEnumMap[instance.kind]!,
      'status': _$PoolOwnBookingStatusEnumEnumMap[instance.status]!,
      'revision': instance.revision,
      'activity_session_id': instance.activitySessionId,
      'activity_group_id': instance.activityGroupId,
      'group_name': instance.groupName,
      'session_name': instance.sessionName,
      'venue': instance.venue,
      'on_date': instance.onDate,
      'starts_at': instance.startsAt,
      'ends_at': instance.endsAt,
      'attendance_open': instance.attendanceOpen,
    };

const _$PoolOwnBookingKindEnumEnumMap = {
  PoolOwnBookingKindEnum.careerTalk: 'career_talk',
  PoolOwnBookingKindEnum.jobShadow: 'job_shadow',
  PoolOwnBookingKindEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};

const _$PoolOwnBookingStatusEnumEnumMap = {
  PoolOwnBookingStatusEnum.booked: 'booked',
  PoolOwnBookingStatusEnum.cancelled: 'cancelled',
  PoolOwnBookingStatusEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
