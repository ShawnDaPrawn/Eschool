// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'school_assistance_publish_request.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SchoolAssistancePublishRequest _$SchoolAssistancePublishRequestFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'SchoolAssistancePublishRequest',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'operation_id',
        'expected_revision',
        'title',
        'markdown',
        'source_reference',
        'identifiers_reviewed',
      ],
    );
    final val = SchoolAssistancePublishRequest(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      documentKey: $checkedConvert('document_key', (v) => v as String?),
      expectedRevision: $checkedConvert(
        'expected_revision',
        (v) => (v as num).toInt(),
      ),
      title: $checkedConvert('title', (v) => v as String),
      markdown: $checkedConvert('markdown', (v) => v as String),
      sourceReference: $checkedConvert('source_reference', (v) => v as String),
      identifiersReviewed: $checkedConvert(
        'identifiers_reviewed',
        (v) => v as bool,
      ),
      expiresOn: $checkedConvert('expires_on', (v) => v as String?),
    );
    return val;
  },
  fieldKeyMap: const {
    'operationId': 'operation_id',
    'documentKey': 'document_key',
    'expectedRevision': 'expected_revision',
    'sourceReference': 'source_reference',
    'identifiersReviewed': 'identifiers_reviewed',
    'expiresOn': 'expires_on',
  },
);

Map<String, dynamic> _$SchoolAssistancePublishRequestToJson(
  SchoolAssistancePublishRequest instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'document_key': ?instance.documentKey,
  'expected_revision': instance.expectedRevision,
  'title': instance.title,
  'markdown': instance.markdown,
  'source_reference': instance.sourceReference,
  'identifiers_reviewed': instance.identifiersReviewed,
  'expires_on': ?instance.expiresOn,
};
