// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'school_term.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SchoolTerm _$SchoolTermFromJson(Map<String, dynamic> json) => $checkedCreate(
  'SchoolTerm',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'session_year_id',
        'name',
        'starts_on',
        'ends_on',
        'grade_from',
        'grade_to',
        'marks_lock_on',
        'reports_on',
        'current',
        'needs_review',
      ],
    );
    final val = SchoolTerm(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      name: $checkedConvert('name', (v) => v as String),
      startsOn: $checkedConvert(
        'starts_on',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      endsOn: $checkedConvert(
        'ends_on',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      gradeFrom: $checkedConvert('grade_from', (v) => (v as num).toInt()),
      gradeTo: $checkedConvert('grade_to', (v) => (v as num).toInt()),
      marksLockOn: $checkedConvert(
        'marks_lock_on',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      reportsOn: $checkedConvert(
        'reports_on',
        (v) => v == null ? null : DateTime.parse(v as String),
      ),
      current: $checkedConvert('current', (v) => v as bool),
      needsReview: $checkedConvert('needs_review', (v) => v as bool),
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
    'needsReview': 'needs_review',
  },
);

Map<String, dynamic> _$SchoolTermToJson(SchoolTerm instance) =>
    <String, dynamic>{
      'id': instance.id,
      'session_year_id': instance.sessionYearId,
      'name': instance.name,
      'starts_on': instance.startsOn?.toIso8601String(),
      'ends_on': instance.endsOn?.toIso8601String(),
      'grade_from': instance.gradeFrom,
      'grade_to': instance.gradeTo,
      'marks_lock_on': instance.marksLockOn?.toIso8601String(),
      'reports_on': instance.reportsOn?.toIso8601String(),
      'current': instance.current,
      'needs_review': instance.needsReview,
    };
