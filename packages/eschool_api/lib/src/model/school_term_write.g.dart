// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'school_term_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SchoolTermWrite _$SchoolTermWriteFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'SchoolTermWrite',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'session_year_id',
            'name',
            'starts_on',
            'ends_on',
            'marks_lock_on',
            'reports_on',
          ],
        );
        final val = SchoolTermWrite(
          sessionYearId: $checkedConvert(
            'session_year_id',
            (v) => (v as num).toInt(),
          ),
          name: $checkedConvert('name', (v) => v as String),
          startsOn: $checkedConvert('starts_on', (v) => v as String),
          endsOn: $checkedConvert('ends_on', (v) => v as String),
          gradeFrom: $checkedConvert('grade_from', (v) => (v as num?)?.toInt()),
          gradeTo: $checkedConvert('grade_to', (v) => (v as num?)?.toInt()),
          marksLockOn: $checkedConvert('marks_lock_on', (v) => v as String?),
          reportsOn: $checkedConvert('reports_on', (v) => v as String?),
        );
        return val;
      },
      fieldKeyMap: const {
        'sessionYearId': 'session_year_id',
        'startsOn': 'starts_on',
        'endsOn': 'ends_on',
        'gradeFrom': 'grade_from',
        'gradeTo': 'grade_to',
        'marksLockOn': 'marks_lock_on',
        'reportsOn': 'reports_on',
      },
    );

Map<String, dynamic> _$SchoolTermWriteToJson(SchoolTermWrite instance) =>
    <String, dynamic>{
      'session_year_id': instance.sessionYearId,
      'name': instance.name,
      'starts_on': instance.startsOn,
      'ends_on': instance.endsOn,
      'grade_from': ?instance.gradeFrom,
      'grade_to': ?instance.gradeTo,
      'marks_lock_on': instance.marksLockOn,
      'reports_on': instance.reportsOn,
    };
