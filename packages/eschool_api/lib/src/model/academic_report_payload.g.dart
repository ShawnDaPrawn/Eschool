// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_report_payload.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReportPayload _$AcademicReportPayloadFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicReportPayload',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'learner_id',
        'learner_name',
        'school_name',
        'year_name',
        'term_name',
        'homeroom_name',
        'grade_name',
        'session_year_id',
        'term_id',
        'class_section_id',
        'grade_level_id',
        'starts_on',
        'ends_on',
        'mode',
        'provisional',
        'ready',
        'subjects',
        'attendance',
        'enrolments',
      ],
    );
    final val = AcademicReportPayload(
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      learnerName: $checkedConvert('learner_name', (v) => v as String),
      schoolName: $checkedConvert('school_name', (v) => v as String),
      yearName: $checkedConvert('year_name', (v) => v as String),
      termName: $checkedConvert('term_name', (v) => v as String?),
      homeroomName: $checkedConvert('homeroom_name', (v) => v as String),
      gradeName: $checkedConvert('grade_name', (v) => v as String),
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      termId: $checkedConvert('term_id', (v) => (v as num?)?.toInt()),
      classSectionId: $checkedConvert(
        'class_section_id',
        (v) => (v as num).toInt(),
      ),
      gradeLevelId: $checkedConvert(
        'grade_level_id',
        (v) => (v as num).toInt(),
      ),
      startsOn: $checkedConvert('starts_on', (v) => v as String),
      endsOn: $checkedConvert('ends_on', (v) => v as String),
      mode: $checkedConvert('mode', (v) => v as String),
      provisional: $checkedConvert('provisional', (v) => v as bool),
      ready: $checkedConvert('ready', (v) => v as bool),
      subjects: $checkedConvert(
        'subjects',
        (v) => (v as List<dynamic>)
            .map(
              (e) => AcademicReportSubjectResult.fromJson(
                e as Map<String, dynamic>,
              ),
            )
            .toList(),
      ),
      attendance: $checkedConvert(
        'attendance',
        (v) => AcademicReportAttendance.fromJson(v as Map<String, dynamic>),
      ),
      enrolments: $checkedConvert(
        'enrolments',
        (v) => (v as List<dynamic>)
            .map(
              (e) =>
                  AcademicReportEnrolment.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'learnerId': 'learner_id',
    'learnerName': 'learner_name',
    'schoolName': 'school_name',
    'yearName': 'year_name',
    'termName': 'term_name',
    'homeroomName': 'homeroom_name',
    'gradeName': 'grade_name',
    'sessionYearId': 'session_year_id',
    'termId': 'term_id',
    'classSectionId': 'class_section_id',
    'gradeLevelId': 'grade_level_id',
    'startsOn': 'starts_on',
    'endsOn': 'ends_on',
  },
);

Map<String, dynamic> _$AcademicReportPayloadToJson(
  AcademicReportPayload instance,
) => <String, dynamic>{
  'learner_id': instance.learnerId,
  'learner_name': instance.learnerName,
  'school_name': instance.schoolName,
  'year_name': instance.yearName,
  'term_name': instance.termName,
  'homeroom_name': instance.homeroomName,
  'grade_name': instance.gradeName,
  'session_year_id': instance.sessionYearId,
  'term_id': instance.termId,
  'class_section_id': instance.classSectionId,
  'grade_level_id': instance.gradeLevelId,
  'starts_on': instance.startsOn,
  'ends_on': instance.endsOn,
  'mode': instance.mode,
  'provisional': instance.provisional,
  'ready': instance.ready,
  'subjects': instance.subjects.map((e) => e.toJson()).toList(),
  'attendance': instance.attendance.toJson(),
  'enrolments': instance.enrolments.map((e) => e.toJson()).toList(),
};
