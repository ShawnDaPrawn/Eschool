// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'group_chat_message_create.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GroupChatMessageCreate _$GroupChatMessageCreateFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('GroupChatMessageCreate', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const [
      'operation_id',
      'persona',
      'body',
      'language',
      'visibility',
    ],
  );
  final val = GroupChatMessageCreate(
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
    visibility: $checkedConvert(
      'visibility',
      (v) => $enumDecode(
        _$GroupChatVisibilityEnumMap,
        v,
        unknownValue: GroupChatVisibility.unknownDefaultOpenApi,
      ),
    ),
    replyTo: $checkedConvert('reply_to', (v) => (v as num?)?.toInt()),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id', 'replyTo': 'reply_to'});

Map<String, dynamic> _$GroupChatMessageCreateToJson(
  GroupChatMessageCreate instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'persona': _$GroupChatPersonaEnumMap[instance.persona]!,
  'body': instance.body,
  'language': instance.language,
  'visibility': _$GroupChatVisibilityEnumMap[instance.visibility]!,
  'reply_to': ?instance.replyTo,
};

const _$GroupChatPersonaEnumMap = {
  GroupChatPersona.staff: 'staff',
  GroupChatPersona.parent: 'parent',
  GroupChatPersona.student: 'student',
  GroupChatPersona.unknownDefaultOpenApi: 'unknown_default_open_api',
};

const _$GroupChatVisibilityEnumMap = {
  GroupChatVisibility.thread: 'thread',
  GroupChatVisibility.staff: 'staff',
  GroupChatVisibility.unknownDefaultOpenApi: 'unknown_default_open_api',
};
