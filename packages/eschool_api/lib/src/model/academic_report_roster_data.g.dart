// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_report_roster_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReportRosterData _$AcademicReportRosterDataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicReportRosterData',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'session_year_id',
        'term_id',
        'class_section_id',
        'grade_level_id',
        'learners',
      ],
    );
    final val = AcademicReportRosterData(
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
      learners: $checkedConvert(
        'learners',
        (v) => (v as List<dynamic>)
            .map(
              (e) =>
                  AcademicTimetableChoice.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'sessionYearId': 'session_year_id',
    'termId': 'term_id',
    'classSectionId': 'class_section_id',
    'gradeLevelId': 'grade_level_id',
  },
);

Map<String, dynamic> _$AcademicReportRosterDataToJson(
  AcademicReportRosterData instance,
) => <String, dynamic>{
  'session_year_id': instance.sessionYearId,
  'term_id': instance.termId,
  'class_section_id': instance.classSectionId,
  'grade_level_id': instance.gradeLevelId,
  'learners': instance.learners.map((e) => e.toJson()).toList(),
};
