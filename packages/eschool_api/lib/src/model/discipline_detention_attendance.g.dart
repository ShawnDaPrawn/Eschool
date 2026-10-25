// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'discipline_detention_attendance.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

DisciplineDetentionAttendance _$DisciplineDetentionAttendanceFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'DisciplineDetentionAttendance',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'session_id',
        'entry_id',
        'session_revision',
        'entry_revision',
        'attendance',
      ],
    );
    final val = DisciplineDetentionAttendance(
      sessionId: $checkedConvert('session_id', (v) => (v as num).toInt()),
      entryId: $checkedConvert('entry_id', (v) => (v as num).toInt()),
      sessionRevision: $checkedConvert(
        'session_revision',
        (v) => (v as num).toInt(),
      ),
      entryRevision: $checkedConvert(
        'entry_revision',
        (v) => (v as num).toInt(),
      ),
      attendance: $checkedConvert(
        'attendance',
        (v) => $enumDecode(
          _$DisciplineDetentionAttendanceAttendanceEnumEnumMap,
          v,
          unknownValue:
              DisciplineDetentionAttendanceAttendanceEnum.unknownDefaultOpenApi,
        ),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'sessionId': 'session_id',
    'entryId': 'entry_id',
    'sessionRevision': 'session_revision',
    'entryRevision': 'entry_revision',
  },
);

Map<String, dynamic> _$DisciplineDetentionAttendanceToJson(
  DisciplineDetentionAttendance instance,
) => <String, dynamic>{
  'session_id': instance.sessionId,
  'entry_id': instance.entryId,
  'session_revision': instance.sessionRevision,
  'entry_revision': instance.entryRevision,
  'attendance':
      _$DisciplineDetentionAttendanceAttendanceEnumEnumMap[instance
          .attendance]!,
};

const _$DisciplineDetentionAttendanceAttendanceEnumEnumMap = {
  DisciplineDetentionAttendanceAttendanceEnum.present: 'present',
  DisciplineDetentionAttendanceAttendanceEnum.absent: 'absent',
  DisciplineDetentionAttendanceAttendanceEnum.excused: 'excused',
  DisciplineDetentionAttendanceAttendanceEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
