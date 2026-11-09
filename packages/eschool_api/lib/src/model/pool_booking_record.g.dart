// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pool_booking_record.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PoolBookingRecord _$PoolBookingRecordFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'PoolBookingRecord',
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
        'pool_member_id',
        'pool_member_name',
        'cancel_reason',
        'vetted_for_session',
      ],
    );
    final val = PoolBookingRecord(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      kind: $checkedConvert(
        'kind',
        (v) => $enumDecode(
          _$PoolBookingRecordKindEnumEnumMap,
          v,
          unknownValue: PoolBookingRecordKindEnum.unknownDefaultOpenApi,
        ),
      ),
      status: $checkedConvert(
        'status',
        (v) => $enumDecode(
          _$PoolBookingRecordStatusEnumEnumMap,
          v,
          unknownValue: PoolBookingRecordStatusEnum.unknownDefaultOpenApi,
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
      poolMemberId: $checkedConvert(
        'pool_member_id',
        (v) => (v as num).toInt(),
      ),
      poolMemberName: $checkedConvert('pool_member_name', (v) => v as String),
      cancelReason: $checkedConvert('cancel_reason', (v) => v as String?),
      vettedForSession: $checkedConvert('vetted_for_session', (v) => v as bool),
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
    'poolMemberId': 'pool_member_id',
    'poolMemberName': 'pool_member_name',
    'cancelReason': 'cancel_reason',
    'vettedForSession': 'vetted_for_session',
  },
);

Map<String, dynamic> _$PoolBookingRecordToJson(PoolBookingRecord instance) =>
    <String, dynamic>{
      'id': instance.id,
      'kind': _$PoolBookingRecordKindEnumEnumMap[instance.kind]!,
      'status': _$PoolBookingRecordStatusEnumEnumMap[instance.status]!,
      'revision': instance.revision,
      'activity_session_id': instance.activitySessionId,
      'activity_group_id': instance.activityGroupId,
      'group_name': instance.groupName,
      'session_name': instance.sessionName,
      'venue': instance.venue,
      'on_date': instance.onDate,
      'starts_at': instance.startsAt,
      'ends_at': instance.endsAt,
      'pool_member_id': instance.poolMemberId,
      'pool_member_name': instance.poolMemberName,
      'cancel_reason': instance.cancelReason,
      'vetted_for_session': instance.vettedForSession,
    };

const _$PoolBookingRecordKindEnumEnumMap = {
  PoolBookingRecordKindEnum.careerTalk: 'career_talk',
  PoolBookingRecordKindEnum.jobShadow: 'job_shadow',
  PoolBookingRecordKindEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};

const _$PoolBookingRecordStatusEnumEnumMap = {
  PoolBookingRecordStatusEnum.booked: 'booked',
  PoolBookingRecordStatusEnum.cancelled: 'cancelled',
  PoolBookingRecordStatusEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
