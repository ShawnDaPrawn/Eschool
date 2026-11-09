// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'remind_reenrolment_contract_request.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

RemindReenrolmentContractRequest _$RemindReenrolmentContractRequestFromJson(
  Map<String, dynamic> json,
) =>
    $checkedCreate('RemindReenrolmentContractRequest', json, ($checkedConvert) {
      $checkKeys(json, requiredKeys: const ['operation_id']);
      final val = RemindReenrolmentContractRequest(
        operationId: $checkedConvert('operation_id', (v) => v as String),
      );
      return val;
    }, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$RemindReenrolmentContractRequestToJson(
  RemindReenrolmentContractRequest instance,
) => <String, dynamic>{'operation_id': instance.operationId};
