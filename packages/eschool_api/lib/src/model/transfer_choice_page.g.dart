// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'transfer_choice_page.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

TransferChoicePage _$TransferChoicePageFromJson(Map<String, dynamic> json) =>
    $checkedCreate('TransferChoicePage', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['items', 'page', 'has_more']);
      final val = TransferChoicePage(
        items: $checkedConvert(
          'items',
          (v) => (v as List<dynamic>)
              .map((e) => TransferChoice.fromJson(e as Map<String, dynamic>))
              .toList(),
        ),
        page: $checkedConvert('page', (v) => (v as num).toInt()),
        hasMore: $checkedConvert('has_more', (v) => v as bool),
      );
      return val;
    }, fieldKeyMap: const {'hasMore': 'has_more'});

Map<String, dynamic> _$TransferChoicePageToJson(TransferChoicePage instance) =>
    <String, dynamic>{
      'items': instance.items.map((e) => e.toJson()).toList(),
      'page': instance.page,
      'has_more': instance.hasMore,
    };
