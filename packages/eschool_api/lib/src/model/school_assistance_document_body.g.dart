// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'school_assistance_document_body.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SchoolAssistanceDocumentBody _$SchoolAssistanceDocumentBodyFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'SchoolAssistanceDocumentBody',
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
        'markdown',
        'school_id',
        'reader_id',
      ],
    );
    final val = SchoolAssistanceDocumentBody(
      id: $checkedConvert('id', (v) => v as String),
      documentKey: $checkedConvert('document_key', (v) => v as String),
      revision: $checkedConvert('revision', (v) => (v as num).toInt()),
      title: $checkedConvert('title', (v) => v as String),
      sourceReference: $checkedConvert('source_reference', (v) => v as String),
      expiresOn: $checkedConvert('expires_on', (v) => v as String?),
      state: $checkedConvert(
        'state',
        (v) => $enumDecode(
          _$SchoolAssistanceDocumentBodyStateEnumEnumMap,
          v,
          unknownValue:
              SchoolAssistanceDocumentBodyStateEnum.unknownDefaultOpenApi,
        ),
      ),
      markdown: $checkedConvert('markdown', (v) => v as String),
      schoolId: $checkedConvert('school_id', (v) => (v as num).toInt()),
      readerId: $checkedConvert('reader_id', (v) => (v as num).toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'documentKey': 'document_key',
    'sourceReference': 'source_reference',
    'expiresOn': 'expires_on',
    'schoolId': 'school_id',
    'readerId': 'reader_id',
  },
);

Map<String, dynamic> _$SchoolAssistanceDocumentBodyToJson(
  SchoolAssistanceDocumentBody instance,
) => <String, dynamic>{
  'id': instance.id,
  'document_key': instance.documentKey,
  'revision': instance.revision,
  'title': instance.title,
  'source_reference': instance.sourceReference,
  'expires_on': instance.expiresOn,
  'state': _$SchoolAssistanceDocumentBodyStateEnumEnumMap[instance.state]!,
  'markdown': instance.markdown,
  'school_id': instance.schoolId,
  'reader_id': instance.readerId,
};

const _$SchoolAssistanceDocumentBodyStateEnumEnumMap = {
  SchoolAssistanceDocumentBodyStateEnum.published: 'published',
  SchoolAssistanceDocumentBodyStateEnum.withdrawn: 'withdrawn',
  SchoolAssistanceDocumentBodyStateEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
