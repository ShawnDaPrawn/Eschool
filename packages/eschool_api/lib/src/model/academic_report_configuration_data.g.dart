// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_report_configuration_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReportConfigurationData _$AcademicReportConfigurationDataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicReportConfigurationData',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'session_year_id',
        'school_years',
        'homerooms',
        'terms',
        'templates',
        'can_design',
        'comment_banks',
      ],
    );
    final val = AcademicReportConfigurationData(
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      schoolYears: $checkedConvert(
        'school_years',
        (v) => (v as List<dynamic>)
            .map(
              (e) =>
                  AcademicTimetableChoice.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
      homerooms: $checkedConvert(
        'homerooms',
        (v) => (v as List<dynamic>)
            .map(
              (e) => AcademicReportHomeroom.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
      terms: $checkedConvert(
        'terms',
        (v) => (v as List<dynamic>)
            .map((e) => AcademicReportTerm.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      templates: $checkedConvert(
        'templates',
        (v) => (v as List<dynamic>)
            .map(
              (e) => AcademicReportTemplate.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
      canDesign: $checkedConvert('can_design', (v) => v as bool),
      commentBanks: $checkedConvert(
        'comment_banks',
        (v) => (v as List<dynamic>)
            .map(
              (e) =>
                  AcademicReportCommentBank.fromJson(e as Map<String, dynamic>),
            )
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'sessionYearId': 'session_year_id',
    'schoolYears': 'school_years',
    'canDesign': 'can_design',
    'commentBanks': 'comment_banks',
  },
);

Map<String, dynamic> _$AcademicReportConfigurationDataToJson(
  AcademicReportConfigurationData instance,
) => <String, dynamic>{
  'session_year_id': instance.sessionYearId,
  'school_years': instance.schoolYears.map((e) => e.toJson()).toList(),
  'homerooms': instance.homerooms.map((e) => e.toJson()).toList(),
  'terms': instance.terms.map((e) => e.toJson()).toList(),
  'templates': instance.templates.map((e) => e.toJson()).toList(),
  'can_design': instance.canDesign,
  'comment_banks': instance.commentBanks.map((e) => e.toJson()).toList(),
};
