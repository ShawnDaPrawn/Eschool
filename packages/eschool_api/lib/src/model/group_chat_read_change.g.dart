// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'group_chat_read_change.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GroupChatReadChange _$GroupChatReadChangeFromJson(Map<String, dynamic> json) =>
    $checkedCreate('GroupChatReadChange', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['persona', 'last_message_id']);
      final val = GroupChatReadChange(
        persona: $checkedConvert(
          'persona',
          (v) => $enumDecode(
            _$GroupChatPersonaEnumMap,
            v,
            unknownValue: GroupChatPersona.unknownDefaultOpenApi,
          ),
        ),
        lastMessageId: $checkedConvert(
          'last_message_id',
          (v) => (v as num).toInt(),
        ),
      );
      return val;
    }, fieldKeyMap: const {'lastMessageId': 'last_message_id'});

Map<String, dynamic> _$GroupChatReadChangeToJson(
  GroupChatReadChange instance,
) => <String, dynamic>{
  'persona': _$GroupChatPersonaEnumMap[instance.persona]!,
  'last_message_id': instance.lastMessageId,
};

const _$GroupChatPersonaEnumMap = {
  GroupChatPersona.staff: 'staff',
  GroupChatPersona.parent: 'parent',
  GroupChatPersona.student: 'student',
  GroupChatPersona.unknownDefaultOpenApi: 'unknown_default_open_api',
};
