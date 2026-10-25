// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'cover_source.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

CoverSource _$CoverSourceFromJson(Map<String, dynamic> json) => $checkedCreate(
  'CoverSource',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'lesson_id',
        'lesson_revision',
        'session_year_id',
        'teacher_id',
        'teacher_name',
        'group_id',
        'homeroom_id',
        'title',
        'subject_name',
        'room_id',
        'room_name',
        'period_id',
        'starts_at',
        'ends_at',
        'on_date',
      ],
    );
    final val = CoverSource(
      lessonId: $checkedConvert('lesson_id', (v) => (v as num).toInt()),
      lessonRevision: $checkedConvert(
        'lesson_revision',
        (v) => (v as num).toInt(),
      ),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      teacherId: $checkedConvert('teacher_id', (v) => (v as num).toInt()),
      teacherName: $checkedConvert('teacher_name', (v) => v as String),
      groupId: $checkedConvert('group_id', (v) => (v as num?)?.toInt()),
      homeroomId: $checkedConvert('homeroom_id', (v) => (v as num?)?.toInt()),
      title: $checkedConvert('title', (v) => v as String),
      subjectName: $checkedConvert('subject_name', (v) => v as String?),
      roomId: $checkedConvert('room_id', (v) => (v as num).toInt()),
      roomName: $checkedConvert('room_name', (v) => v as String),
      periodId: $checkedConvert('period_id', (v) => (v as num).toInt()),
      startsAt: $checkedConvert('starts_at', (v) => v as String),
      endsAt: $checkedConvert('ends_at', (v) => v as String),
      onDate: $checkedConvert('on_date', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {
    'lessonId': 'lesson_id',
    'lessonRevision': 'lesson_revision',
    'sessionYearId': 'session_year_id',
    'teacherId': 'teacher_id',
    'teacherName': 'teacher_name',
    'groupId': 'group_id',
    'homeroomId': 'homeroom_id',
    'subjectName': 'subject_name',
    'roomId': 'room_id',
    'roomName': 'room_name',
    'periodId': 'period_id',
    'startsAt': 'starts_at',
    'endsAt': 'ends_at',
    'onDate': 'on_date',
  },
);

Map<String, dynamic> _$CoverSourceToJson(CoverSource instance) =>
    <String, dynamic>{
      'lesson_id': instance.lessonId,
      'lesson_revision': instance.lessonRevision,
      'session_year_id': instance.sessionYearId,
      'teacher_id': instance.teacherId,
      'teacher_name': instance.teacherName,
      'group_id': instance.groupId,
      'homeroom_id': instance.homeroomId,
      'title': instance.title,
      'subject_name': instance.subjectName,
      'room_id': instance.roomId,
      'room_name': instance.roomName,
      'period_id': instance.periodId,
      'starts_at': instance.startsAt,
      'ends_at': instance.endsAt,
      'on_date': instance.onDate,
    };
