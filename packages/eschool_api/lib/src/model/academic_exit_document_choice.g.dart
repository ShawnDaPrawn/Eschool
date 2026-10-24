// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'academic_exit_document_choice.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

AcademicExitDocumentChoice _$AcademicExitDocumentChoiceFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'AcademicExitDocumentChoice',
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
      ],
    );
    final val = AcademicExitDocumentChoice(
      id: $checkedConvert('id', (v) => v as String),
      definitionId: $checkedConvert('definition_id', (v) => (v as num).toInt()),
      name: $checkedConvert('name', (v) => v as String),
      mime: $checkedConvert('mime', (v) => v as String),
      extension_: $checkedConvert('extension', (v) => v as String),
      bytes: $checkedConvert('bytes', (v) => (v as num).toInt()),
    );
    return val;
  },
  fieldKeyMap: const {
    'definitionId': 'definition_id',
    'extension_': 'extension',
  },
);

Map<String, dynamic> _$AcademicExitDocumentChoiceToJson(
  AcademicExitDocumentChoice instance,
) => <String, dynamic>{
  'id': instance.id,
  'definition_id': instance.definitionId,
  'name': instance.name,
  'mime': instance.mime,
  'extension': instance.extension_,
  'bytes': instance.bytes,
};
