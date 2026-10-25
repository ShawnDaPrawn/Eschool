// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'consultation_page.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ConsultationPage _$ConsultationPageFromJson(Map<String, dynamic> json) =>
    $checkedCreate('ConsultationPage', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['items', 'page', 'last_page']);
      final val = ConsultationPage(
        items: $checkedConvert(
          'items',
          (v) => (v as List<dynamic>)
              .map(
                (e) => ConsultationSummary.fromJson(e as Map<String, dynamic>),
              )
              .toList(),
        ),
        page: $checkedConvert('page', (v) => (v as num).toInt()),
        lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
      );
      return val;
    }, fieldKeyMap: const {'lastPage': 'last_page'});

Map<String, dynamic> _$ConsultationPageToJson(ConsultationPage instance) =>
    <String, dynamic>{
      'items': instance.items.map((e) => e.toJson()).toList(),
      'page': instance.page,
      'last_page': instance.lastPage,
    };
