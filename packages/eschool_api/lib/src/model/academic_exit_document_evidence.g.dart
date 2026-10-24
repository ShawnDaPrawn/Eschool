// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_exit_document_evidence.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicExitDocumentEvidence _$AcademicExitDocumentEvidenceFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicExitDocumentEvidence',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'id',
        'definition_id',
        'name',
        'mime',
        'extension',
        'bytes',
        'source_sha256',
      ],
    );
    final val = AcademicExitDocumentEvidence(
      id: $checkedConvert('id', (v) => v as String),
      definitionId: $checkedConvert('definition_id', (v) => (v as num).toInt()),
      name: $checkedConvert('name', (v) => v as String),
      mime: $checkedConvert('mime', (v) => v as String),
      extension_: $checkedConvert('extension', (v) => v as String),
      bytes: $checkedConvert('bytes', (v) => (v as num).toInt()),
      sourceSha256: $checkedConvert('source_sha256', (v) => v as String?),
    );
    return val;
  },
  fieldKeyMap: const {
    'definitionId': 'definition_id',
    'extension_': 'extension',
    'sourceSha256': 'source_sha256',
  },
);

Map<String, dynamic> _$AcademicExitDocumentEvidenceToJson(
  AcademicExitDocumentEvidence instance,
) => <String, dynamic>{
  'id': instance.id,
  'definition_id': instance.definitionId,
  'name': instance.name,
  'mime': instance.mime,
  'extension': instance.extension_,
  'bytes': instance.bytes,
  'source_sha256': instance.sourceSha256,
};
