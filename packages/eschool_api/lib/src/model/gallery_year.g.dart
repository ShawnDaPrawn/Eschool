// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'gallery_year.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GalleryYear _$GalleryYearFromJson(Map<String, dynamic> json) =>
    $checkedCreate('GalleryYear', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['id', 'name', 'starts_on', 'ends_on'],
      );
      final val = GalleryYear(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        name: $checkedConvert('name', (v) => v as String),
        startsOn: $checkedConvert('starts_on', (v) => v as String),
        endsOn: $checkedConvert('ends_on', (v) => v as String),
      );
      return val;
    }, fieldKeyMap: const {'startsOn': 'starts_on', 'endsOn': 'ends_on'});

Map<String, dynamic> _$GalleryYearToJson(GalleryYear instance) =>
    <String, dynamic>{
      'id': instance.id,
      'name': instance.name,
      'starts_on': instance.startsOn,
      'ends_on': instance.endsOn,
    };
