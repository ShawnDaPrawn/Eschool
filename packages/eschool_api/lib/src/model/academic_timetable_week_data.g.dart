// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_timetable_week_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicTimetableWeekData _$AcademicTimetableWeekDataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicTimetableWeekData',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'session_year_id',
        'starts_on',
        'ends_on',
        'can_manage',
        'lessons',
      ],
    );
    final val = AcademicTimetableWeekData(
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      startsOn: $checkedConvert('starts_on', (v) => v as String),
      endsOn: $checkedConvert('ends_on', (v) => v as String),
      canManage: $checkedConvert('can_manage', (v) => v as bool),
      lessons: $checkedConvert(
        'lessons',
        (v) => (v as List<dynamic>)
            .map(
              (e) =>
                  AcademicTimetableLesson.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
      examDays: $checkedConvert(
        'exam_days',
        (v) => (v as List<dynamic>?)?.map((e) => e as String).toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'sessionYearId': 'session_year_id',
    'startsOn': 'starts_on',
    'endsOn': 'ends_on',
    'canManage': 'can_manage',
    'examDays': 'exam_days',
  },
);

Map<String, dynamic> _$AcademicTimetableWeekDataToJson(
  AcademicTimetableWeekData instance,
) => <String, dynamic>{
  'session_year_id': instance.sessionYearId,
  'starts_on': instance.startsOn,
  'ends_on': instance.endsOn,
  'can_manage': instance.canManage,
  'lessons': instance.lessons.map((e) => e.toJson()).toList(),
  'exam_days': ?instance.examDays,
};
