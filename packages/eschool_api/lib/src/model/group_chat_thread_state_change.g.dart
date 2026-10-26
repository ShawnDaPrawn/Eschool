// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'group_chat_thread_state_change.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GroupChatThreadStateChange _$GroupChatThreadStateChangeFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('GroupChatThreadStateChange', json, ($checkedConvert) {
  $checkKeys(
    json,
    requiredKeys: const ['operation_id', 'persona', 'revision', 'closed'],
  );
  final val = GroupChatThreadStateChange(
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
    closed: $checkedConvert('closed', (v) => v as bool),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$GroupChatThreadStateChangeToJson(
  GroupChatThreadStateChange instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'persona': _$GroupChatPersonaEnumMap[instance.persona]!,
  'revision': instance.revision,
  'closed': instance.closed,
};

const _$GroupChatPersonaEnumMap = {
  GroupChatPersona.staff: 'staff',
  GroupChatPersona.parent: 'parent',
  GroupChatPersona.student: 'student',
  GroupChatPersona.unknownDefaultOpenApi: 'unknown_default_open_api',
};
