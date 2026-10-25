// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'activity_attendance_entry_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ActivityAttendanceEntryWrite _$ActivityAttendanceEntryWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ActivityAttendanceEntryWrite', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['learner_id', 'status', 'revision']);
  final val = ActivityAttendanceEntryWrite(
    learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
    status: $checkedConvert(
      'status',
      (v) => $enumDecode(
        _$ActivityAttendanceEntryWriteStatusEnumEnumMap,
        v,
        unknownValue:
            ActivityAttendanceEntryWriteStatusEnum.unknownDefaultOpenApi,
      ),
    ),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
  );
  return val;
}, fieldKeyMap: const {'learnerId': 'learner_id'});

Map<String, dynamic> _$ActivityAttendanceEntryWriteToJson(
  ActivityAttendanceEntryWrite instance,
) => <String, dynamic>{
  'learner_id': instance.learnerId,
  'status': _$ActivityAttendanceEntryWriteStatusEnumEnumMap[instance.status]!,
  'revision': instance.revision,
};

const _$ActivityAttendanceEntryWriteStatusEnumEnumMap = {
  ActivityAttendanceEntryWriteStatusEnum.present: 'present',
  ActivityAttendanceEntryWriteStatusEnum.absent: 'absent',
  ActivityAttendanceEntryWriteStatusEnum.late_: 'late',
  ActivityAttendanceEntryWriteStatusEnum.excused: 'excused',
  ActivityAttendanceEntryWriteStatusEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
