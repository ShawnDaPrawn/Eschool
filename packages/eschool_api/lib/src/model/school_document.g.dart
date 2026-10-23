// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'school_document.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SchoolDocument _$SchoolDocumentFromJson(Map<String, dynamic> json) =>
    $checkedCreate('SchoolDocument', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['title', 'kind', 'url']);
      final val = SchoolDocument(
        id: $checkedConvert('id', (v) => v as String?),
        title: $checkedConvert('title', (v) => v as String),
        kind: $checkedConvert('kind', (v) => v as String?),
        url: $checkedConvert('url', (v) => v as String),
      );
      return val;
    });

Map<String, dynamic> _$SchoolDocumentToJson(SchoolDocument instance) =>
    <String, dynamic>{
      'id': ?instance.id,
      'title': instance.title,
      'kind': instance.kind,
      'url': instance.url,
    };
