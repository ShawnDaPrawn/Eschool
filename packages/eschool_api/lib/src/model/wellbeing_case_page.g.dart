// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wellbeing_case_page.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WellbeingCasePage _$WellbeingCasePageFromJson(Map<String, dynamic> json) =>
    $checkedCreate('WellbeingCasePage', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['items', 'page', 'last_page']);
      final val = WellbeingCasePage(
        items: $checkedConvert(
          'items',
          (v) => (v as List<dynamic>)
              .map(
                (e) => WellbeingCaseSummary.fromJson(e as Map<String, dynamic>),
              )
              .toList(),
        ),
        page: $checkedConvert('page', (v) => (v as num).toInt()),
        lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
      );
      return val;
    }, fieldKeyMap: const {'lastPage': 'last_page'});

Map<String, dynamic> _$WellbeingCasePageToJson(WellbeingCasePage instance) =>
    <String, dynamic>{
      'items': instance.items.map((e) => e.toJson()).toList(),
      'page': instance.page,
      'last_page': instance.lastPage,
    };
