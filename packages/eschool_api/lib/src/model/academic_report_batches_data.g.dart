// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_report_batches_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReportBatchesData _$AcademicReportBatchesDataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicReportBatchesData',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['session_year_id', 'page', 'last_page', 'batches'],
    );
    final val = AcademicReportBatchesData(
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      page: $checkedConvert('page', (v) => (v as num).toInt()),
      lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
      batches: $checkedConvert(
        'batches',
        (v) => (v as List<dynamic>)
            .map(
              (e) => AcademicReportBatchSummary.fromJson(
                e as Map<String, dynamic>,
              ),
            )
            .toList(),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'sessionYearId': 'session_year_id',
    'lastPage': 'last_page',
  },
);

Map<String, dynamic> _$AcademicReportBatchesDataToJson(
  AcademicReportBatchesData instance,
) => <String, dynamic>{
  'session_year_id': instance.sessionYearId,
  'page': instance.page,
  'last_page': instance.lastPage,
  'batches': instance.batches.map((e) => e.toJson()).toList(),
};
