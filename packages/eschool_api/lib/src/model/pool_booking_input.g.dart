// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pool_booking_input.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PoolBookingInput _$PoolBookingInputFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'PoolBookingInput',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'operation_id',
            'pool_member_id',
            'activity_session_id',
            'kind',
          ],
        );
        final val = PoolBookingInput(
          operationId: $checkedConvert('operation_id', (v) => v as String),
          poolMemberId: $checkedConvert(
            'pool_member_id',
            (v) => (v as num).toInt(),
          ),
          activitySessionId: $checkedConvert(
            'activity_session_id',
            (v) => (v as num).toInt(),
          ),
          kind: $checkedConvert(
            'kind',
            (v) => $enumDecode(
              _$PoolBookingInputKindEnumEnumMap,
              v,
              unknownValue: PoolBookingInputKindEnum.unknownDefaultOpenApi,
            ),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'operationId': 'operation_id',
        'poolMemberId': 'pool_member_id',
        'activitySessionId': 'activity_session_id',
      },
    );

Map<String, dynamic> _$PoolBookingInputToJson(PoolBookingInput instance) =>
    <String, dynamic>{
      'operation_id': instance.operationId,
      'pool_member_id': instance.poolMemberId,
      'activity_session_id': instance.activitySessionId,
      'kind': _$PoolBookingInputKindEnumEnumMap[instance.kind]!,
    };

const _$PoolBookingInputKindEnumEnumMap = {
  PoolBookingInputKindEnum.careerTalk: 'career_talk',
  PoolBookingInputKindEnum.jobShadow: 'job_shadow',
  PoolBookingInputKindEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
