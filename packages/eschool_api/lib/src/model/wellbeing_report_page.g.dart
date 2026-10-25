// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wellbeing_report_page.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WellbeingReportPage _$WellbeingReportPageFromJson(Map<String, dynamic> json) =>
    $checkedCreate('WellbeingReportPage', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['items', 'page', 'last_page']);
      final val = WellbeingReportPage(
        items: $checkedConvert(
          'items',
          (v) => (v as List<dynamic>)
              .map(
                (e) =>
                    WellbeingReportSummary.fromJson(e as Map<String, dynamic>),
              )
              .toList(),
        ),
        page: $checkedConvert('page', (v) => (v as num).toInt()),
        lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
      );
      return val;
    }, fieldKeyMap: const {'lastPage': 'last_page'});

Map<String, dynamic> _$WellbeingReportPageToJson(
  WellbeingReportPage instance,
) => <String, dynamic>{
  'items': instance.items.map((e) => e.toJson()).toList(),
  'page': instance.page,
  'last_page': instance.lastPage,
};
