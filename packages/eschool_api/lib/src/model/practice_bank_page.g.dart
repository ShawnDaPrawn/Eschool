// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'practice_bank_page.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PracticeBankPage _$PracticeBankPageFromJson(Map<String, dynamic> json) =>
    $checkedCreate('PracticeBankPage', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['items', 'page', 'last_page']);
      final val = PracticeBankPage(
        items: $checkedConvert(
          'items',
          (v) => (v as List<dynamic>)
              .map((e) => PracticeBankItem.fromJson(e as Map<String, dynamic>))
              .toList(),
        ),
        page: $checkedConvert('page', (v) => (v as num).toInt()),
        lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
      );
      return val;
    }, fieldKeyMap: const {'lastPage': 'last_page'});

Map<String, dynamic> _$PracticeBankPageToJson(PracticeBankPage instance) =>
    <String, dynamic>{
      'items': instance.items.map((e) => e.toJson()).toList(),
      'page': instance.page,
      'last_page': instance.lastPage,
    };
