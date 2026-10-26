// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'gallery_child.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GalleryChild _$GalleryChildFromJson(Map<String, dynamic> json) =>
    $checkedCreate('GalleryChild', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['id', 'name', 'handle', 'can_approve', 'archived'],
      );
      final val = GalleryChild(
        id: $checkedConvert('id', (v) => (v as num).toInt()),
        name: $checkedConvert('name', (v) => v as String),
        handle: $checkedConvert('handle', (v) => v as String?),
        canApprove: $checkedConvert('can_approve', (v) => v as bool),
        archived: $checkedConvert('archived', (v) => v as bool),
      );
      return val;
    }, fieldKeyMap: const {'canApprove': 'can_approve'});

Map<String, dynamic> _$GalleryChildToJson(GalleryChild instance) =>
    <String, dynamic>{
      'id': instance.id,
      'name': instance.name,
      'handle': instance.handle,
      'can_approve': instance.canApprove,
      'archived': instance.archived,
    };
