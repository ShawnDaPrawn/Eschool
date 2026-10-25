// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'gallery_family_page.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GalleryFamilyPage _$GalleryFamilyPageFromJson(Map<String, dynamic> json) =>
    $checkedCreate('GalleryFamilyPage', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['items', 'total', 'next_before']);
      final val = GalleryFamilyPage(
        items: $checkedConvert(
          'items',
          (v) => (v as List<dynamic>)
              .map(
                (e) => GalleryTaggedPhoto.fromJson(e as Map<String, dynamic>),
              )
              .toList(),
        ),
        total: $checkedConvert('total', (v) => (v as num).toInt()),
        nextBefore: $checkedConvert('next_before', (v) => (v as num?)?.toInt()),
      );
      return val;
    }, fieldKeyMap: const {'nextBefore': 'next_before'});

Map<String, dynamic> _$GalleryFamilyPageToJson(GalleryFamilyPage instance) =>
    <String, dynamic>{
      'items': instance.items.map((e) => e.toJson()).toList(),
      'total': instance.total,
      'next_before': instance.nextBefore,
    };
