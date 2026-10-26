// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'group_chat_read_ack_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GroupChatReadAckResponse _$GroupChatReadAckResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('GroupChatReadAckResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = GroupChatReadAckResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => GroupChatReadAck.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$GroupChatReadAckResponseCodeEnumEnumMap,
        v,
        unknownValue: GroupChatReadAckResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$GroupChatReadAckResponseToJson(
  GroupChatReadAckResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$GroupChatReadAckResponseCodeEnumEnumMap[instance.code]!,
};

const _$GroupChatReadAckResponseCodeEnumEnumMap = {
  GroupChatReadAckResponseCodeEnum.number200: 200,
  GroupChatReadAckResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
