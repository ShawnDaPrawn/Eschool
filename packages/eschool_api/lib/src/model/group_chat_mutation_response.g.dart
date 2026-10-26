// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'group_chat_mutation_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GroupChatMutationResponse _$GroupChatMutationResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('GroupChatMutationResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = GroupChatMutationResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => GroupChatMutation.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$GroupChatMutationResponseCodeEnumEnumMap,
        v,
        unknownValue: GroupChatMutationResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$GroupChatMutationResponseToJson(
  GroupChatMutationResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$GroupChatMutationResponseCodeEnumEnumMap[instance.code]!,
};

const _$GroupChatMutationResponseCodeEnumEnumMap = {
  GroupChatMutationResponseCodeEnum.number200: 200,
  GroupChatMutationResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
