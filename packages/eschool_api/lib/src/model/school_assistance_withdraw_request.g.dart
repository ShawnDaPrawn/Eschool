// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'school_assistance_withdraw_request.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

SchoolAssistanceWithdrawRequest _$SchoolAssistanceWithdrawRequestFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('SchoolAssistanceWithdrawRequest', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['operation_id']);
  final val = SchoolAssistanceWithdrawRequest(
    operationId: $checkedConvert('operation_id', (v) => v as String),
  );
  return val;
}, fieldKeyMap: const {'operationId': 'operation_id'});

Map<String, dynamic> _$SchoolAssistanceWithdrawRequestToJson(
  SchoolAssistanceWithdrawRequest instance,
) => <String, dynamic>{'operation_id': instance.operationId};
