// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'group_chat_receipt_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GroupChatReceiptResponse _$GroupChatReceiptResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('GroupChatReceiptResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = GroupChatReceiptResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => GroupChatReceipt.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert(
      'code',
      (v) => $enumDecode(
        _$GroupChatReceiptResponseCodeEnumEnumMap,
        v,
        unknownValue: GroupChatReceiptResponseCodeEnum.unknownDefaultOpenApi,
      ),
    ),
  );
  return val;
});

Map<String, dynamic> _$GroupChatReceiptResponseToJson(
  GroupChatReceiptResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': _$GroupChatReceiptResponseCodeEnumEnumMap[instance.code]!,
};

const _$GroupChatReceiptResponseCodeEnumEnumMap = {
  GroupChatReceiptResponseCodeEnum.number200: 200,
  GroupChatReceiptResponseCodeEnum.unknownDefaultOpenApi: 11184809,
};
