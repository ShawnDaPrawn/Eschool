// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'group_chat_mutation.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GroupChatMutation _$GroupChatMutationFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'GroupChatMutation',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'operation_type',
            'persona',
            'thread_id',
            'activity_group_id',
            'revision',
          ],
        );
        final val = GroupChatMutation(
          operationType: $checkedConvert(
            'operation_type',
            (v) => $enumDecode(
              _$GroupChatOperationTypeEnumMap,
              v,
              unknownValue: GroupChatOperationType.unknownDefaultOpenApi,
            ),
          ),
          persona: $checkedConvert(
            'persona',
            (v) => $enumDecode(
              _$GroupChatPersonaEnumMap,
              v,
              unknownValue: GroupChatPersona.unknownDefaultOpenApi,
            ),
          ),
          threadId: $checkedConvert('thread_id', (v) => v as String),
          activityGroupId: $checkedConvert(
            'activity_group_id',
            (v) => (v as num).toInt(),
          ),
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
          messageId: $checkedConvert('message_id', (v) => (v as num?)?.toInt()),
          closed: $checkedConvert('closed', (v) => v as bool?),
          removed: $checkedConvert('removed', (v) => v as bool?),
        );
        return val;
      },
      fieldKeyMap: const {
        'operationType': 'operation_type',
        'threadId': 'thread_id',
        'activityGroupId': 'activity_group_id',
        'messageId': 'message_id',
      },
    );

Map<String, dynamic> _$GroupChatMutationToJson(
  GroupChatMutation instance,
) => <String, dynamic>{
  'operation_type': _$GroupChatOperationTypeEnumMap[instance.operationType]!,
  'persona': _$GroupChatPersonaEnumMap[instance.persona]!,
  'thread_id': instance.threadId,
  'activity_group_id': instance.activityGroupId,
  'revision': instance.revision,
  'message_id': ?instance.messageId,
  'closed': ?instance.closed,
  'removed': ?instance.removed,
};

const _$GroupChatOperationTypeEnumMap = {
  GroupChatOperationType.threadCreated: 'thread_created',
  GroupChatOperationType.threadState: 'thread_state',
  GroupChatOperationType.messageSent: 'message_sent',
  GroupChatOperationType.messageRemoved: 'message_removed',
  GroupChatOperationType.unknownDefaultOpenApi: 'unknown_default_open_api',
};

const _$GroupChatPersonaEnumMap = {
  GroupChatPersona.staff: 'staff',
  GroupChatPersona.parent: 'parent',
  GroupChatPersona.student: 'student',
  GroupChatPersona.unknownDefaultOpenApi: 'unknown_default_open_api',
};
