// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'secure_card_page.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SecureCardPage _$SecureCardPageFromJson(Map<String, dynamic> json) =>
    $checkedCreate('SecureCardPage', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['items', 'next_page']);
      final val = SecureCardPage(
        items: $checkedConvert(
          'items',
          (v) => (v as List<dynamic>)
              .map((e) => SecureCard.fromJson(e as Map<String, dynamic>))
              .toList(),
        ),
        nextPage: $checkedConvert('next_page', (v) => (v as num?)?.toInt()),
      );
      return val;
    }, fieldKeyMap: const {'nextPage': 'next_page'});

Map<String, dynamic> _$SecureCardPageToJson(SecureCardPage instance) =>
    <String, dynamic>{
      'items': instance.items.map((e) => e.toJson()).toList(),
      'next_page': instance.nextPage,
    };
