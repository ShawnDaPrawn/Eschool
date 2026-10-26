// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'group_chat_read_ack.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GroupChatReadAck _$GroupChatReadAckFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'GroupChatReadAck',
      json,
      ($checkedConvert) {
        $checkKeys(json, requiredKeys: const ['thread_id', 'last_message_id']);
        final val = GroupChatReadAck(
          threadId: $checkedConvert('thread_id', (v) => v as String),
          lastMessageId: $checkedConvert(
            'last_message_id',
            (v) => (v as num).toInt(),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'threadId': 'thread_id',
        'lastMessageId': 'last_message_id',
      },
    );

Map<String, dynamic> _$GroupChatReadAckToJson(GroupChatReadAck instance) =>
    <String, dynamic>{
      'thread_id': instance.threadId,
      'last_message_id': instance.lastMessageId,
    };
