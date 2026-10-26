// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'school_assistance_document.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SchoolAssistanceDocument _$SchoolAssistanceDocumentFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'SchoolAssistanceDocument',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'document_key',
        'revision',
        'title',
        'source_reference',
        'expires_on',
        'state',
      ],
    );
    final val = SchoolAssistanceDocument(
      id: $checkedConvert('id', (v) => v as String),
      documentKey: $checkedConvert('document_key', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      title: $checkedConvert('title', (v) => v as String),
      sourceReference: $checkedConvert('source_reference', (v) => v as String),
      expiresOn: $checkedConvert('expires_on', (v) => v as String?),
      state: $checkedConvert(
        'state',
        (v) => $enumDecode(
          _$SchoolAssistanceDocumentStateEnumEnumMap,
          v,
          unknownValue: SchoolAssistanceDocumentStateEnum.unknownDefaultOpenApi,
        ),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'documentKey': 'document_key',
    'sourceReference': 'source_reference',
    'expiresOn': 'expires_on',
  },
);

Map<String, dynamic> _$SchoolAssistanceDocumentToJson(
  SchoolAssistanceDocument instance,
) => <String, dynamic>{
  'id': instance.id,
  'document_key': instance.documentKey,
  'revision': instance.revision,
  'title': instance.title,
  'source_reference': instance.sourceReference,
  'expires_on': instance.expiresOn,
  'state': _$SchoolAssistanceDocumentStateEnumEnumMap[instance.state]!,
};

const _$SchoolAssistanceDocumentStateEnumEnumMap = {
  SchoolAssistanceDocumentStateEnum.published: 'published',
  SchoolAssistanceDocumentStateEnum.withdrawn: 'withdrawn',
  SchoolAssistanceDocumentStateEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
