// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'group_chat_thread.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GroupChatThread _$GroupChatThreadFromJson(Map<String, dynamic> json) =>
    $checkedCreate(
      'GroupChatThread',
      json,
      ($checkedConvert) {
        $checkKeys(
          json,
          requiredKeys: const [
            'id',
            'activity_group_id',
            'title',
            'audience',
            'private',
            'closed',
            'revision',
            'last_message_at',
          ],
        );
        final val = GroupChatThread(
          id: $checkedConvert('id', (v) => v as String),
          activityGroupId: $checkedConvert(
            'activity_group_id',
            (v) => (v as num).toInt(),
          ),
          title: $checkedConvert('title', (v) => v as String?),
          audience: $checkedConvert(
            'audience',
            (v) => $enumDecode(
              _$GroupChatAudienceEnumMap,
              v,
              unknownValue: GroupChatAudience.unknownDefaultOpenApi,
            ),
          ),
          private: $checkedConvert('private', (v) => v as bool),
          closed: $checkedConvert('closed', (v) => v as bool),
          revision: $checkedConvert('revision', (v) => (v as num).toInt()),
          lastMessageAt: $checkedConvert(
            'last_message_at',
            (v) => DateTime.parse(v as String),
          ),
        );
        return val;
      },
      fieldKeyMap: const {
        'activityGroupId': 'activity_group_id',
        'lastMessageAt': 'last_message_at',
      },
    );

Map<String, dynamic> _$GroupChatThreadToJson(GroupChatThread instance) =>
    <String, dynamic>{
      'id': instance.id,
      'activity_group_id': instance.activityGroupId,
      'title': instance.title,
      'audience': _$GroupChatAudienceEnumMap[instance.audience]!,
      'private': instance.private,
      'closed': instance.closed,
      'revision': instance.revision,
      'last_message_at': instance.lastMessageAt.toIso8601String(),
    };

const _$GroupChatAudienceEnumMap = {
  GroupChatAudience.learners: 'learners',
  GroupChatAudience.guardians: 'guardians',
  GroupChatAudience.staff: 'staff',
  GroupChatAudience.unknownDefaultOpenApi: 'unknown_default_open_api',
};
