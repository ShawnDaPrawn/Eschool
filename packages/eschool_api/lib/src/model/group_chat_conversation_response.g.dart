// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'group_chat_conversation_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GroupChatConversationResponse _$GroupChatConversationResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('GroupChatConversationResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = GroupChatConversationResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => GroupChatConversation.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$GroupChatConversationResponseCodeEnumEnumMap,
        v,
        unknownValue:
            GroupChatConversationResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$GroupChatConversationResponseToJson(
  GroupChatConversationResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$GroupChatConversationResponseCodeEnumEnumMap[instance.code]!,
};

const _$GroupChatConversationResponseCodeEnumEnumMap = {
  GroupChatConversationResponseCodeEnum.number200: 200,
  GroupChatConversationResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
