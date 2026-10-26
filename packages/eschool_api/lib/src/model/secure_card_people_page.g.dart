// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'secure_card_people_page.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SecureCardPeoplePage _$SecureCardPeoplePageFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SecureCardPeoplePage', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['items', 'next_page']);
  final val = SecureCardPeoplePage(
    items: $checkedConvert(
      'items',
      (v) => (v as List<dynamic>)
          .map((e) => SecureCardPerson.fromJson(e as Map<String, dynamic>))
          .toList(),
    ),
    nextPage: $checkedConvert('next_page', (v) => (v as num?)?.toInt()),
  );
  return val;
}, fieldKeyMap: const {'nextPage': 'next_page'});

Map<String, dynamic> _$SecureCardPeoplePageToJson(
  SecureCardPeoplePage instance,
) => <String, dynamic>{
  'items': instance.items.map((e) => e.toJson()).toList(),
  'next_page': instance.nextPage,
};
