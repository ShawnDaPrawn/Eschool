// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'gallery_group_page.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GalleryGroupPage _$GalleryGroupPageFromJson(Map<String, dynamic> json) =>
    $checkedCreate('GalleryGroupPage', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['items', 'next_before']);
      final val = GalleryGroupPage(
        items: $checkedConvert(
          'items',
          (v) => (v as List<dynamic>)
              .map((e) => GalleryPhoto.fromJson(e as Map<String, dynamic>))
              .toList(),
        ),
        nextBefore: $checkedConvert('next_before', (v) => (v as num?)?.toInt()),
      );
      return val;
    }, fieldKeyMap: const {'nextBefore': 'next_before'});

Map<String, dynamic> _$GalleryGroupPageToJson(GalleryGroupPage instance) =>
    <String, dynamic>{
      'items': instance.items.map((e) => e.toJson()).toList(),
      'next_before': instance.nextBefore,
    };
