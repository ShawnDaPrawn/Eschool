// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'discipline_detention_attendance_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DisciplineDetentionAttendanceWrite _$DisciplineDetentionAttendanceWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'DisciplineDetentionAttendanceWrite',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'operation_id',
        'expected_revision',
        'expected_session_revision',
        'attendance',
        'reason',
      ],
    );
    final val = DisciplineDetentionAttendanceWrite(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      expectedRevision: $checkedConvert(
        'expected_revision',
        (v) => (v as num).toInt(),
      ),
      expectedSessionRevision: $checkedConvert(
        'expected_session_revision',
        (v) => (v as num).toInt(),
      ),
      attendance: $checkedConvert(
        'attendance',
        (v) => $enumDecode(
          _$DisciplineDetentionAttendanceWriteAttendanceEnumEnumMap,
          v,
          unknownValue: DisciplineDetentionAttendanceWriteAttendanceEnum
              .unknownDefaultOpenApi,
        ),
      ),
      reason: $checkedConvert('reason', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'expectedRevision': 'expected_revision',
    'expectedSessionRevision': 'expected_session_revision',
  },
);

Map<String, dynamic> _$DisciplineDetentionAttendanceWriteToJson(
  DisciplineDetentionAttendanceWrite instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'expected_revision': instance.expectedRevision,
  'expected_session_revision': instance.expectedSessionRevision,
  'attendance':
      _$DisciplineDetentionAttendanceWriteAttendanceEnumEnumMap[instance
          .attendance]!,
  'reason': instance.reason,
};

const _$DisciplineDetentionAttendanceWriteAttendanceEnumEnumMap = {
  DisciplineDetentionAttendanceWriteAttendanceEnum.present: 'present',
  DisciplineDetentionAttendanceWriteAttendanceEnum.absent: 'absent',
  DisciplineDetentionAttendanceWriteAttendanceEnum.excused: 'excused',
  DisciplineDetentionAttendanceWriteAttendanceEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
