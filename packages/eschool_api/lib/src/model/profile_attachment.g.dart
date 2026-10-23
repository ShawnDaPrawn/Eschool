// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'profile_attachment.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

ProfileAttachment _$ProfileAttachmentFromJson(Map<String, dynamic> json) =>
    $checkedCreate('ProfileAttachment', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['id', 'name', 'mime', 'size', 'url'],
      );
      final val = ProfileAttachment(
        id: $checkedConvert('id', (v) => v as String),
        name: $checkedConvert('name', (v) => v as String),
        mime: $checkedConvert('mime', (v) => v as String),
        size: $checkedConvert('size', (v) => (v as num).toInt()),
        url: $checkedConvert('url', (v) => v as String),
      );
      return val;
    });

Map<String, dynamic> _$ProfileAttachmentToJson(ProfileAttachment instance) =>
    <String, dynamic>{
      'id': instance.id,
      'name': instance.name,
      'mime': instance.mime,
      'size': instance.size,
      'url': instance.url,
    };
