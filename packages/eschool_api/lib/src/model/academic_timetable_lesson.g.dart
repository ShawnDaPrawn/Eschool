// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_timetable_lesson.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicTimetableLesson _$AcademicTimetableLessonFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicTimetableLesson',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'revision',
        'session_year_id',
        'term_id',
        'teaching_group_id',
        'class_section_id',
        'teacher_id',
        'room_id',
        'bell_period_id',
        'starts_on',
        'ends_on',
        'occurs_on',
        'weekday',
        'week_pattern',
        'active',
        'teacher_name',
        'room_name',
        'building',
        'period_name',
        'starts_at',
        'ends_at',
        'title',
        'subject_name',
        'date',
      ],
    );
    final val = AcademicTimetableLesson(
      coverId: $checkedConvert('cover_id', (v) => (v as num?)?.toInt()),
      originalTeacherId: $checkedConvert(
        'original_teacher_id',
        (v) => (v as num?)?.toInt(),
      ),
      originalTeacherName: $checkedConvert(
        'original_teacher_name',
        (v) => v as String?,
      ),
      dayPattern: $checkedConvert('day_pattern', (v) => v as String?),
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      termId: $checkedConvert('term_id', (v) => (v as num?)?.toInt()),
      teachingGroupId: $checkedConvert(
        'teaching_group_id',
        (v) => (v as num?)?.toInt(),
      ),
      classSectionId: $checkedConvert(
        'class_section_id',
        (v) => (v as num?)?.toInt(),
      ),
      teacherId: $checkedConvert('teacher_id', (v) => (v as num).toInt()),
      roomId: $checkedConvert('room_id', (v) => (v as num).toInt()),
      bellPeriodId: $checkedConvert(
        'bell_period_id',
        (v) => (v as num).toInt(),
      ),
      startsOn: $checkedConvert('starts_on', (v) => v as String),
      endsOn: $checkedConvert('ends_on', (v) => v as String),
      occursOn: $checkedConvert('occurs_on', (v) => v as String?),
      weekday: $checkedConvert('weekday', (v) => (v as num).toInt()),
      weekPattern: $checkedConvert(
        'week_pattern',
        (v) => $enumDecode(
          _$AcademicTimetableLessonWeekPatternEnumEnumMap,
          v,
          unknownValue:
              AcademicTimetableLessonWeekPatternEnum.unknownDefaultOpenApi,
        ),
      ),
      active: $checkedConvert('active', (v) => v as bool),
      teacherName: $checkedConvert('teacher_name', (v) => v as String),
      roomName: $checkedConvert('room_name', (v) => v as String),
      building: $checkedConvert('building', (v) => v as String?),
      periodName: $checkedConvert('period_name', (v) => v as String),
      startsAt: $checkedConvert('starts_at', (v) => v as String),
      endsAt: $checkedConvert('ends_at', (v) => v as String),
      title: $checkedConvert('title', (v) => v as String),
      subjectName: $checkedConvert('subject_name', (v) => v as String?),
      date: $checkedConvert('date', (v) => v as String?),
    );
    return val;
  },
  fieldKeyMap: const {
    'coverId': 'cover_id',
    'originalTeacherId': 'original_teacher_id',
    'originalTeacherName': 'original_teacher_name',
    'dayPattern': 'day_pattern',
    'sessionYearId': 'session_year_id',
    'termId': 'term_id',
    'teachingGroupId': 'teaching_group_id',
    'classSectionId': 'class_section_id',
    'teacherId': 'teacher_id',
    'roomId': 'room_id',
    'bellPeriodId': 'bell_period_id',
    'startsOn': 'starts_on',
    'endsOn': 'ends_on',
    'occursOn': 'occurs_on',
    'weekPattern': 'week_pattern',
    'teacherName': 'teacher_name',
    'roomName': 'room_name',
    'periodName': 'period_name',
    'startsAt': 'starts_at',
    'endsAt': 'ends_at',
    'subjectName': 'subject_name',
  },
);

Map<String, dynamic> _$AcademicTimetableLessonToJson(
  AcademicTimetableLesson instance,
) => <String, dynamic>{
  'cover_id': ?instance.coverId,
  'original_teacher_id': ?instance.originalTeacherId,
  'original_teacher_name': ?instance.originalTeacherName,
  'day_pattern': ?instance.dayPattern,
  'id': instance.id,
  'revision': instance.revision,
  'session_year_id': instance.sessionYearId,
  'term_id': instance.termId,
  'teaching_group_id': instance.teachingGroupId,
  'class_section_id': instance.classSectionId,
  'teacher_id': instance.teacherId,
  'room_id': instance.roomId,
  'bell_period_id': instance.bellPeriodId,
  'starts_on': instance.startsOn,
  'ends_on': instance.endsOn,
  'occurs_on': instance.occursOn,
  'weekday': instance.weekday,
  'week_pattern':
      _$AcademicTimetableLessonWeekPatternEnumEnumMap[instance.weekPattern]!,
  'active': instance.active,
  'teacher_name': instance.teacherName,
  'room_name': instance.roomName,
  'building': instance.building,
  'period_name': instance.periodName,
  'starts_at': instance.startsAt,
  'ends_at': instance.endsAt,
  'title': instance.title,
  'subject_name': instance.subjectName,
  'date': instance.date,
};

const _$AcademicTimetableLessonWeekPatternEnumEnumMap = {
  AcademicTimetableLessonWeekPatternEnum.all: 'all',
  AcademicTimetableLessonWeekPatternEnum.odd: 'odd',
  AcademicTimetableLessonWeekPatternEnum.even: 'even',
  AcademicTimetableLessonWeekPatternEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
