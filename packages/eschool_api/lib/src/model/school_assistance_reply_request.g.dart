// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'school_assistance_reply_request.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SchoolAssistanceReplyRequest _$SchoolAssistanceReplyRequestFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SchoolAssistanceReplyRequest', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['operation_id', 'text', 'language', 'persona'],
  );
  final val = SchoolAssistanceReplyRequest(
    operationId: $checkedConvert('operation_id', (v) => v as String),
    text: $checkedConvert('text', (v) => v as String),
    language: $checkedConvert('language', (v) => v as String),
    persona: $checkedConvert(
      'persona',
      (v) => $enumDecode(
        _$SchoolAssistanceReplyRequestPersonaEnumEnumMap,
        v,
        unknownValue:
            SchoolAssistanceReplyRequestPersonaEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$SchoolAssistanceReplyRequestToJson(
  SchoolAssistanceReplyRequest instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'text': instance.text,
  'language': instance.language,
  'persona':
      _$SchoolAssistanceReplyRequestPersonaEnumEnumMap[instance.persona]!,
};

const _$SchoolAssistanceReplyRequestPersonaEnumEnumMap = {
  SchoolAssistanceReplyRequestPersonaEnum.parent: 'parent',
  SchoolAssistanceReplyRequestPersonaEnum.office: 'office',
  SchoolAssistanceReplyRequestPersonaEnum.unknownDefaultOpenApi:
      'unknown_default_open_api',
};
