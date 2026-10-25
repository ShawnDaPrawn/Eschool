// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'consultation_history_page.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ConsultationHistoryPage _$ConsultationHistoryPageFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('ConsultationHistoryPage', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['items', 'page', 'last_page']);
  final val = ConsultationHistoryPage(
    items: $checkedConvert(
      'items',
      (v) => (v as List<dynamic>)
          .map(
            (e) => ConsultationHistoryEntry.fromJson(e as Map<String, dynamic>),
          )
          .toList(),
    ),
    page: $checkedConvert('page', (v) => (v as num).toInt()),
    lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
  );
  return val;
}, fieldKeyMap: const {'lastPage': 'last_page'});

Map<String, dynamic> _$ConsultationHistoryPageToJson(
  ConsultationHistoryPage instance,
) => <String, dynamic>{
  'items': instance.items.map((e) => e.toJson()).toList(),
  'page': instance.page,
  'last_page': instance.lastPage,
};
