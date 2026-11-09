// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'bursary_document.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

BursaryDocument _$BursaryDocumentFromJson(Map<String, dynamic> json) =>
    $checkedCreate('BursaryDocument', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['id', 'category', 'mime', 'bytes']);
      final val = BursaryDocument(
        id: $checkedConvert('id', (v) => v as String),
        category: $checkedConvert('category', (v) => v as String),
        mime: $checkedConvert('mime', (v) => v as String),
        bytes: $checkedConvert('bytes', (v) => (v as num).toInt()),
        revision: $checkedConvert('revision', (v) => (v as num?)?.toInt()),
      );
      return val;
    });

Map<String, dynamic> _$BursaryDocumentToJson(BursaryDocument instance) =>
    <String, dynamic>{
      'id': instance.id,
      'category': instance.category,
      'mime': instance.mime,
      'bytes': instance.bytes,
      'revision': ?instance.revision,
    };
