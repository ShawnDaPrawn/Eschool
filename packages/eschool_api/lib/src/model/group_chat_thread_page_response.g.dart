// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'group_chat_thread_page_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GroupChatThreadPageResponse _$GroupChatThreadPageResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('GroupChatThreadPageResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = GroupChatThreadPageResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => GroupChatThreadPage.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$GroupChatThreadPageResponseCodeEnumEnumMap,
        v,
        unknownValue: GroupChatThreadPageResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$GroupChatThreadPageResponseToJson(
  GroupChatThreadPageResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$GroupChatThreadPageResponseCodeEnumEnumMap[instance.code]!,
};

const _$GroupChatThreadPageResponseCodeEnumEnumMap = {
  GroupChatThreadPageResponseCodeEnum.number200: 200,
  GroupChatThreadPageResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
