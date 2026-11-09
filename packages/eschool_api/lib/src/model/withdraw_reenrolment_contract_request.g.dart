// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'withdraw_reenrolment_contract_request.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WithdrawReenrolmentContractRequest _$WithdrawReenrolmentContractRequestFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('WithdrawReenrolmentContractRequest', json, (
  $checkedConvert,
) {
  $checkKeys(json, requiredKeys: const ['operation_id', 'revision']);
  final val = WithdrawReenrolmentContractRequest(
    operationId: $checkedConvert('operation_id', (v) => v as String),
    revision: $checkedConvert('revision', (v) => (v as num).toInt()),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$WithdrawReenrolmentContractRequestToJson(
  WithdrawReenrolmentContractRequest instance,
) => <String, dynamic>{
  'operation_id': instance.operationId,
  'revision': instance.revision,
};
