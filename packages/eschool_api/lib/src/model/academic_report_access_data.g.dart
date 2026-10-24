// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_report_access_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReportAccessData _$AcademicReportAccessDataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicReportAccessData',
  json,
  ($checkedConvert) {
    $checkKeys(json, requiredKeys: const ['id', 'learner_id', 'download_url']);
    final val = AcademicReportAccessData(
      id: $checkedConvert('id', (v) => (v as num).toInt()),
      learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
      downloadUrl: $checkedConvert('download_url', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {'learnerId': 'learner_id', 'downloadUrl': 'download_url'},
);

Map<String, dynamic> _$AcademicReportAccessDataToJson(
  AcademicReportAccessData instance,
) => <String, dynamic>{
  'id': instance.id,
  'learner_id': instance.learnerId,
  'download_url': instance.downloadUrl,
};
