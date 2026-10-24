// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'content_library.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ContentLibrary _$ContentLibraryFromJson(Map<String, dynamic> json) =>
    $checkedCreate('ContentLibrary', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['learner_id', 'page', 'last_page', 'items'],
      );
      final val = ContentLibrary(
        learnerId: $checkedConvert('learner_id', (v) => (v as num).toInt()),
        page: $checkedConvert('page', (v) => (v as num).toInt()),
        lastPage: $checkedConvert('last_page', (v) => (v as num).toInt()),
        items: $checkedConvert(
          'items',
          (v) => (v as List<dynamic>)
              .map(
                (e) => ContentLibraryItem.fromJson(e as Map<String, dynamic>),
              )
              .toList(),
        ),
      );
      return val;
    }, fieldKeyMap: const {'learnerId': 'learner_id', 'lastPage': 'last_page'});

Map<String, dynamic> _$ContentLibraryToJson(ContentLibrary instance) =>
    <String, dynamic>{
      'learner_id': instance.learnerId,
      'page': instance.page,
      'last_page': instance.lastPage,
      'items': instance.items.map((e) => e.toJson()).toList(),
    };
