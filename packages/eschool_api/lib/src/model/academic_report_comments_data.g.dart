// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_report_comments_data.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicReportCommentsData _$AcademicReportCommentsDataFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicReportCommentsData',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['session_year_id', 'page', 'last_page', 'rows'],
    );
    final val = AcademicReportCommentsData(
      sessionYearId: $checkedConvert(
        'session_year_id',
        (v) => (v as num).toInt(),
      ),
      page: $checkedConvert('page', (v) => (v as num).toInt()),
      lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
      rows: $checkedConvert(
        'rows',
        (v) => (v as List<dynamic>)
            .map(
              (e) =>
                  AcademicReportCommentRow.fromJson(e as Map<String, dynamic>),
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

Map<String, dynamic> _$AcademicReportCommentsDataToJson(
  AcademicReportCommentsData instance,
) => <String, dynamic>{
  'session_year_id': instance.sessionYearId,
  'page': instance.page,
  'last_page': instance.lastPage,
  'rows': instance.rows.map((e) => e.toJson()).toList(),
};
