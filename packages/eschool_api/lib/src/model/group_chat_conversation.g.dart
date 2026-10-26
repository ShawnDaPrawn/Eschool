// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'group_chat_conversation.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GroupChatConversation _$GroupChatConversationFromJson(
  Map<String, dynamic> json,
) => $checkedCreate(
  'GroupChatConversation',
  json,
  ($checkedConvert) {
    $checkKeys(
      json,
      requiredKeys: const [
        'thread',
        'items',
        'next_before',
        'can_close',
        'can_post',
        'can_reopen',
        'max_characters',
        'policy',
      ],
    );
    final val = GroupChatConversation(
      thread: $checkedConvert(
        'thread',
        (v) => GroupChatThread.fromJson(v as Map<String, dynamic>),
      ),
      items: $checkedConvert(
        'items',
        (v) => (v as List<dynamic>)
            .map((e) => GroupChatMessage.fromJson(e as Map<String, dynamic>))
            .toList(),
      ),
      nextBefore: $checkedConvert('next_before', (v) => (v as num?)?.toInt()),
      canClose: $checkedConvert('can_close', (v) => v as bool),
      canPost: $checkedConvert('can_post', (v) => v as bool),
      canReopen: $checkedConvert('can_reopen', (v) => v as bool),
      maxCharacters: $checkedConvert(
        'max_characters',
        (v) => (v as num).toInt(),
      ),
      policy: $checkedConvert(
        'policy',
        (v) => GroupChatPolicy.fromJson(v as Map<String, dynamic>),
      ),
    );
    return val;
  },
  fieldKeyMap: const {
    'nextBefore': 'next_before',
    'canClose': 'can_close',
    'canPost': 'can_post',
    'canReopen': 'can_reopen',
    'maxCharacters': 'max_characters',
  },
);

Map<String, dynamic> _$GroupChatConversationToJson(
  GroupChatConversation instance,
) => <String, dynamic>{
  'thread': instance.thread.toJson(),
  'items': instance.items.map((e) => e.toJson()).toList(),
  'next_before': instance.nextBefore,
  'can_close': instance.canClose,
  'can_post': instance.canPost,
  'can_reopen': instance.canReopen,
  'max_characters': instance.maxCharacters,
  'policy': instance.policy.toJson(),
};
