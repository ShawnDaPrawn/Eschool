// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'group_chat_message.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GroupChatMessage _$GroupChatMessageFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'GroupChatMessage',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'sender_id',
            'sender_name',
            'sender_persona',
            'body',
            'language',
            'revision',
            'reply_to',
            'private',
            'removed',
            'can_remove',
            'sent_at',
          ],
        );
        final val = GroupChatMessage(
          id: $checkedConvert('id', (v) => (v as num).toInt()),
          senderId: $checkedConvert('sender_id', (v) => (v as num).toInt()),
          senderName: $checkedConvert('sender_name', (v) => v as String),
          senderPersona: $checkedConvert(
            'sender_persona',
            (v) => $enumDecode(
              _$GroupChatPersonaEnumMap,
              v,
              unknownValue: GroupChatPersona.unknownDefaultOpenApi,
            ),
          ),
          body: $checkedConvert('body', (v) => v as String?),
          language: $checkedConvert('language', (v) => v as String),
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
          replyTo: $checkedConvert('reply_to', (v) => (v as num?)?.toInt()),
          private: $checkedConvert('private', (v) => v as bool),
          removed: $checkedConvert('removed', (v) => v as bool),
          canRemove: $checkedConvert('can_remove', (v) => v as bool),
          sentAt: $checkedConvert(
            'sent_at',
            (v) => DateTime.parse(v as String),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'senderId': 'sender_id',
        'senderName': 'sender_name',
        'senderPersona': 'sender_persona',
        'replyTo': 'reply_to',
        'canRemove': 'can_remove',
        'sentAt': 'sent_at',
      },
    );

Map<String, dynamic> _$GroupChatMessageToJson(GroupChatMessage instance) =>
    <String, dynamic>{
      'id': instance.id,
      'sender_id': instance.senderId,
      'sender_name': instance.senderName,
      'sender_persona': _$GroupChatPersonaEnumMap[instance.senderPersona]!,
      'body': instance.body,
      'language': instance.language,
      'revision': instance.revision,
      'reply_to': instance.replyTo,
      'private': instance.private,
      'removed': instance.removed,
      'can_remove': instance.canRemove,
      'sent_at': instance.sentAt.toIso8601String(),
    };

const _$GroupChatPersonaEnumMap = {
  GroupChatPersona.staff: 'staff',
  GroupChatPersona.parent: 'parent',
  GroupChatPersona.student: 'student',
  GroupChatPersona.unknownDefaultOpenApi: 'unknown_default_open_api',
};
