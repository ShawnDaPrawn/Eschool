// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'school_assistance_open_thread_request.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SchoolAssistanceOpenThreadRequest _$SchoolAssistanceOpenThreadRequestFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SchoolAssistanceOpenThreadRequest', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['operation_id', 'text', 'language']);
  final val = SchoolAssistanceOpenThreadRequest(
    operationId: $checkedConvert('operation_id', (v) => v as String),
    text: $checkedConvert('text', (v) => v as String),
    language: $checkedConvert('language', (v) => v as String),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$SchoolAssistanceOpenThreadRequestToJson(
  SchoolAssistanceOpenThreadRequest instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'text': instance.text,
  'language': instance.language,
};
