// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_payment_issue_write.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletPaymentIssueWrite _$WalletPaymentIssueWriteFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('WalletPaymentIssueWrite', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['operation_id', 'quote', 'confirmed']);
  final val = WalletPaymentIssueWrite(
    operationId: $checkedConvert('operation_id', (v) => v as String),
    quote: $checkedConvert('quote', (v) => v as String),
    confirmed: $checkedConvert('confirmed', (v) => v as bool),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$WalletPaymentIssueWriteToJson(
  WalletPaymentIssueWrite instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'quote': instance.quote,
  'confirmed': instance.confirmed,
};
