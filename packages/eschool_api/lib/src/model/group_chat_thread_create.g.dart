// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'group_chat_thread_create.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GroupChatThreadCreate _$GroupChatThreadCreateFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('GroupChatThreadCreate', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const [
      'operation_id',
      'persona',
      'body',
      'language',
      'kind',
      'title',
    ],
  );
  final val = GroupChatThreadCreate(
    operationId: $checkedConvert('operation_id', (v) => v as String),
    persona: $checkedConvert(
      'persona',
      (v) => $enumDecode(
        _$GroupChatPersonaEnumMap,
        v,
        unknownValue: GroupChatPersona.unknownDefaultOpenApi,
      ),
    ),
    body: $checkedConvert('body', (v) => v as String),
    language: $checkedConvert('language', (v) => v as String),
    kind: $checkedConvert(
      'kind',
      (v) => $enumDecode(
        _$GroupChatThreadKindEnumMap,
        v,
        unknownValue: GroupChatThreadKind.unknownDefaultOpenApi,
      ),
    ),
    audience: $checkedConvert(
      'audience',
      (v) => $enumDecodeNullable(
        _$GroupChatAudienceEnumMap,
        v,
        unknownValue: GroupChatAudience.unknownDefaultOpenApi,
      ),
    ),
    title: $checkedConvert('title', (v) => v as String),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$GroupChatThreadCreateToJson(
  GroupChatThreadCreate instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'persona': _$GroupChatPersonaEnumMap[instance.persona]!,
  'body': instance.body,
  'language': instance.language,
  'kind': _$GroupChatThreadKindEnumMap[instance.kind]!,
  'audience': ?_$GroupChatAudienceEnumMap[instance.audience],
  'title': instance.title,
};

const _$GroupChatPersonaEnumMap = {
  GroupChatPersona.staff: 'staff',
  GroupChatPersona.parent: 'parent',
  GroupChatPersona.student: 'student',
  GroupChatPersona.unknownDefaultOpenApi: 'unknown_default_open_api',
};

const _$GroupChatThreadKindEnumMap = {
  GroupChatThreadKind.announcement: 'announcement',
  GroupChatThreadKind.question: 'question',
  GroupChatThreadKind.unknownDefaultOpenApi: 'unknown_default_open_api',
};

const _$GroupChatAudienceEnumMap = {
  GroupChatAudience.learners: 'learners',
  GroupChatAudience.guardians: 'guardians',
  GroupChatAudience.staff: 'staff',
  GroupChatAudience.unknownDefaultOpenApi: 'unknown_default_open_api',
};
