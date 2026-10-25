// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'support_cases.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SupportCases _$SupportCasesFromJson(Map<String, dynamic> json) =>
    $checkedCreate('SupportCases', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['page', 'last_page', 'items']);
      final val = SupportCases(
        page: $checkedConvert('page', (v) => (v as num).toInt()),
        lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
        items: $checkedConvert(
          'items',
          (v) => (v as List<dynamic>)
              .map(
                (e) => SupportCaseSummary.fromJson(e as Map<String, dynamic>),
              )
              .toList(),
        ),
      );
      return val;
    }, fieldKeyMap: const {'lastPage': 'last_page'});

Map<String, dynamic> _$SupportCasesToJson(SupportCases instance) =>
    <String, dynamic>{
      'page': instance.page,
      'last_page': instance.lastPage,
      'items': instance.items.map((e) => e.toJson()).toList(),
    };
