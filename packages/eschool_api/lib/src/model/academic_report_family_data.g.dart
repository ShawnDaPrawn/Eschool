// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_report_family_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReportFamilyData _$AcademicReportFamilyDataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('AcademicReportFamilyData', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['learner_id', 'page', 'last_page', 'reports'],
  );
  final val = AcademicReportFamilyData(
    learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
    page: $checkedConvert('page', (v) => (v as num).toInt()),
    lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
    reports: $checkedConvert(
      'reports',
      (v) => (v as List<dynamic>)
          .map(
            (e) => AcademicReportFamilyCard.fromJson(e as Map<String, dynamic>),
          )
          .toList(),
    ),
  );
  return val;
}, fieldKeyMap: const {'learnerId': 'learner_id', 'lastPage': 'last_page'});

Map<String, dynamic> _$AcademicReportFamilyDataToJson(
  AcademicReportFamilyData instance,
) => <String, dynamic>{
  'learner_id': instance.learnerId,
  'page': instance.page,
  'last_page': instance.lastPage,
  'reports': instance.reports.map((e) => e.toJson()).toList(),
};
