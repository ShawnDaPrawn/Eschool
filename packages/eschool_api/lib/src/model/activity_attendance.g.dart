// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'activity_attendance.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ActivityAttendance _$ActivityAttendanceFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'ActivityAttendance',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'activity_session_id',
            'learner_id',
            'status',
            'recorded_by',
            'reason',
            'revision',
          ],
        );
        final val = ActivityAttendance(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          activitySessionId: $checkedConvert(
            'activity_session_id',
            (v) => (v as num).toInt(),
          ),
          learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
          status: $checkedConvert(
            'status',
            (v) => $enumDecode(
              _$ActivityAttendanceStatusEnumEnumMap,
              v,
              unknownValue: ActivityAttendanceStatusEnum.unknownDefaultOpenApi,
            ),
          ),
          recordedBy: $checkedConvert('recorded_by', (v) => (v as num).toInt()),
          reason: $checkedConvert('reason', (v) => v as String),
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
        );
        return val;
      },
      fieldKeyMap: const {
        'activitySessionId': 'activity_session_id',
        'learnerId': 'learner_id',
        'recordedBy': 'recorded_by',
      },
    );

Map<String, dynamic> _$ActivityAttendanceToJson(ActivityAttendance instance) =>
    <String, dynamic>{
      'id': instance.id,
      'activity_session_id': instance.activitySessionId,
      'learner_id': instance.learnerId,
      'status': _$ActivityAttendanceStatusEnumEnumMap[instance.status]!,
      'recorded_by': instance.recordedBy,
      'reason': instance.reason,
      'revision': instance.revision,
    };

const _$ActivityAttendanceStatusEnumEnumMap = {
  ActivityAttendanceStatusEnum.present: 'present',
  ActivityAttendanceStatusEnum.absent: 'absent',
  ActivityAttendanceStatusEnum.late_: 'late',
  ActivityAttendanceStatusEnum.excused: 'excused',
  ActivityAttendanceStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
