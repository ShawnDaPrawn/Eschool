// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'school_assistance_translation_request.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SchoolAssistanceTranslationRequest _$SchoolAssistanceTranslationRequestFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'SchoolAssistanceTranslationRequest',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const ['operation_id', 'message_id', 'language'],
    );
    final val = SchoolAssistanceTranslationRequest(
      operationId: $checkedConvert('operation_id', (v) => v as String),
      messageId: $checkedConvert('message_id', (v) => v as String),
      language: $checkedConvert('language', (v) => v as String),
    );
    return val;
  },
  fieldKeyMap: const {'operationId': 'operation_id', 'messageId': 'message_id'},
);

Map<String, dynamic> _$SchoolAssistanceTranslationRequestToJson(
  SchoolAssistanceTranslationRequest instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'message_id': instance.messageId,
  'language': instance.language,
};
