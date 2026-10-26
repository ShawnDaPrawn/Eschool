// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'group_chat_policy_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GroupChatPolicyResponse _$GroupChatPolicyResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('GroupChatPolicyResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = GroupChatPolicyResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => GroupChatPolicy.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$GroupChatPolicyResponseCodeEnumEnumMap,
        v,
        unknownValue: GroupChatPolicyResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$GroupChatPolicyResponseToJson(
  GroupChatPolicyResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$GroupChatPolicyResponseCodeEnumEnumMap[instance.code]!,
};

const _$GroupChatPolicyResponseCodeEnumEnumMap = {
  GroupChatPolicyResponseCodeEnum.number200: 200,
  GroupChatPolicyResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
