// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'pool_document.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

PoolDocument _$PoolDocumentFromJson(Map<String, dynamic> json) =>
    $checkedCreate('PoolDocument', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['document', 'version', 'title', 'text'],
      );
      final val = PoolDocument(
        document: $checkedConvert(
          'document',
          (v) => $enumDecode(
            _$PoolDocumentDocumentEnumEnumMap,
            v,
            unknownValue: PoolDocumentDocumentEnum.unknownDefaultOpenApi,
          ),
        ),
        version: $checkedConvert('version', (v) => v as String),
        title: $checkedConvert('title', (v) => v as String),
        text: $checkedConvert('text', (v) => v as String),
      );
      return val;
    });

Map<String, dynamic> _$PoolDocumentToJson(PoolDocument instance) =>
    <String, dynamic>{
      'document': _$PoolDocumentDocumentEnumEnumMap[instance.document]!,
      'version': instance.version,
      'title': instance.title,
      'text': instance.text,
    };

const _$PoolDocumentDocumentEnumEnumMap = {
  PoolDocumentDocumentEnum.navigateed: 'navigateed',
  PoolDocumentDocumentEnum.school: 'school',
  PoolDocumentDocumentEnum.poolConsent: 'pool_consent',
  PoolDocumentDocumentEnum.unknownDefaultOpenApi: 'unknown_default_open_api',
};
