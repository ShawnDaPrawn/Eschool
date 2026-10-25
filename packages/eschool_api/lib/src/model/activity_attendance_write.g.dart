// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'activity_attendance_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ActivityAttendanceWrite _$ActivityAttendanceWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ActivityAttendanceWrite', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['operation_id', 'revision', 'reason', 'entries'],
  );
  final val = ActivityAttendanceWrite(
    operationId: $checkedConvert('operation_id', (v) => v as String),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    reason: $checkedConvert('reason', (v) => v as String),
    entries: $checkedConvert(
      'entries',
      (v) => (v as List<dynamic>)
          .map(
            (e) => ActivityAttendanceEntryWrite.fromJson(
              e as Map<String, dynamic>,
            ),
          )
          .toList(),
    ),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$ActivityAttendanceWriteToJson(
  ActivityAttendanceWrite instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'revision': instance.revision,
  'reason': instance.reason,
  'entries': instance.entries.map((e) => e.toJson()).toList(),
};
