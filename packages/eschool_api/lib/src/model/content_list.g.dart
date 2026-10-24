// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'content_list.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ContentList _$ContentListFromJson(Map<String, dynamic> json) =>
    $checkedCreate('ContentList', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['page', 'last_page', 'items']);
      final val = ContentList(
        page: $checkedConvert('page', (v) => (v as num).toInt()),
        lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
        items: $checkedConvert(
          'items',
          (v) => (v as List<dynamic>)
              .map((e) => ContentListItem.fromJson(e as Map<String, dynamic>))
              .toList(),
        ),
      );
      return val;
    }, fieldKeyMap: const {'lastPage': 'last_page'});

Map<String, dynamic> _$ContentListToJson(ContentList instance) =>
    <String, dynamic>{
      'page': instance.page,
      'last_page': instance.lastPage,
      'items': instance.items.map((e) => e.toJson()).toList(),
    };
