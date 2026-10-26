// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'group_chat_receipt.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

GroupChatReceipt _$GroupChatReceiptFromJson(Map<String, dynamic> json) =>
    $checkedCreate('GroupChatReceipt', json, ($checkedConvert) {
      $checkKeys(
        json,
        requiredKeys: const ['recorded', 'operation_id', 'receipt'],
      );
      final val = GroupChatReceipt(
        recorded: $checkedConvert('recorded', (v) => v as bool),
        operationId: $checkedConvert('operation_id', (v) => v as String),
        receipt: $checkedConvert(
          'receipt',
          (v) => GroupChatMutation.fromJson(v as Map<String, dynamic>),
        ),
      );
      return val;
    }, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$GroupChatReceiptToJson(GroupChatReceipt instance) =>
    <String, dynamic>{
      'recorded': instance.recorded,
      'operation_id': instance.operationId,
      'receipt': instance.receipt.toJson(),
    };
