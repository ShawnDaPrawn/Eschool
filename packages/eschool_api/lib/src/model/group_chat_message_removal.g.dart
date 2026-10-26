// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'group_chat_message_removal.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GroupChatMessageRemoval _$GroupChatMessageRemovalFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('GroupChatMessageRemoval', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['operation_id', 'persona', 'revision', 'reason'],
  );
  final val = GroupChatMessageRemoval(
    operationId: $checkedConvert('operation_id', (v) => v as String),
    persona: $checkedConvert(
      'persona',
      (v) => $enumDecode(
        _$GroupChatPersonaEnumMap,
        v,
        unknownValue: GroupChatPersona.unknownDefaultOpenApi,
      ),
    ),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
    reason: $checkedConvert('reason', (v) => v as String),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$GroupChatMessageRemovalToJson(
  GroupChatMessageRemoval instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'persona': _$GroupChatPersonaEnumMap[instance.persona]!,
  'revision': instance.revision,
  'reason': instance.reason,
};

const _$GroupChatPersonaEnumMap = {
  GroupChatPersona.staff: 'staff',
  GroupChatPersona.parent: 'parent',
  GroupChatPersona.student: 'student',
  GroupChatPersona.unknownDefaultOpenApi: 'unknown_default_open_api',
};
